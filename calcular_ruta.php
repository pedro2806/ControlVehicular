<?php
/**
 * Cálculo de km recorridos (origen check-in -> destino cliente/actividad).
 *
 * Doble uso:
 *   - Endpoint AJAX: POST {ov_ot, lat, lng, [id_actividad]} desde el check-in QR.
 *   - Librería incluible: define las funciones SIN ejecutar el endpoint, para
 *     que el batch de reconciliación (al importar Actividades) reutilice la
 *     misma lógica de cálculo. El bloque del endpoint solo corre cuando este
 *     archivo es el script invocado directamente.
 */

// Cuando la API INEGI falla, usamos haversine (línea recta) y la multiplicamos
// por este factor para aproximar la ruta real por carretera.
const HAVERSINE_FACTOR = 1.45;

// INEGI Sakbé tiene una red vial por escala (cada escala es un grafo distinto).
// Ningún escala cubre todos los puntos: los urbanos resuelven en escalas finas,
// los remotos/industriales solo en las gruesas. Se prueba de fino a grueso y se
// usa el primer escala donde TANTO origen como destino resuelven (ruta más
// detallada posible). Origen y destino deben salir del MISMO escala para rutear.
const ESCALAS_INEGI = [20000, 50000, 250000, 1000000];

function tokenInegi(): string {
    static $key = null;
    if ($key === null) {
        $cfg = @parse_ini_file(__DIR__ . '/token.ini');
        $key = $cfg['INEGI_TOKEN'] ?? '';
    }
    return $key;
}

function resolverCliente(mysqli $conn, string $ov_ot): ?int {
    $stmt = $conn->prepare("SELECT id_cliente FROM OT WHERE OT = ? LIMIT 1");
    $stmt->bind_param("s", $ov_ot);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!empty($r['id_cliente'])) return intval($r['id_cliente']);

    $stmt = $conn->prepare("SELECT id_cliente FROM OV WHERE OV = ? LIMIT 1");
    $stmt->bind_param("s", $ov_ot);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return !empty($r['id_cliente']) ? intval($r['id_cliente']) : null;
}

/**
 * Fallback OTROS: actividad del usuario en la tabla `otros` (poblada al importar
 * el CSV de Actividades) cuya fecha coincide con el día de referencia del
 * check-in. Excluye actividades ya consumidas por otro check-in (su order_code
 * ya quedó registrado en actividad_vehiculo).
 *
 * `marca` es el identificador que se graba en actividad_vehiculo.order_code para
 * marcar la actividad como consumida: el order_code del CSV, o 'OTROS-{id}' si
 * la fila no trajo order_code (siempre no-nulo, así nunca se procesa dos veces).
 *
 * @param string $fechaRef fecha/hora del check-in ('Y-m-d H:i:s'); se compara por día.
 * @return array{id_cliente:int,actividad:string,lat:?float,lng:?float,marca:string}|null
 */
function resolverClienteOtros(mysqli $conn, int $id_usuario, string $fechaRef): ?array {
    try {
        // COLLATE explícito: otros.order_code y actividad_vehiculo.order_code
        // tienen collations distintas (tabla nueva vs vieja); sin esto el NOT IN
        // lanza "Illegal mix of collations".
        $sql = "SELECT id_cliente, actividad, latitude, longitude,
                       COALESCE(order_code, CONCAT('OTROS-', id)) AS marca
                FROM otros
                WHERE id_usuario = ?
                  AND fecha IS NOT NULL
                  AND DATE(fecha) = DATE(?)
                  AND COALESCE(order_code, CONCAT('OTROS-', id)) COLLATE utf8mb4_general_ci NOT IN (
                        SELECT order_code COLLATE utf8mb4_general_ci
                        FROM actividad_vehiculo WHERE order_code IS NOT NULL)
                ORDER BY fecha DESC, id DESC
                LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) return null; // la tabla aún no existe (sin importación previa)
        $stmt->bind_param("is", $id_usuario, $fechaRef);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$r || empty($r['id_cliente'])) return null;
        return [
            'id_cliente' => intval($r['id_cliente']),
            'actividad'  => (string) $r['actividad'],
            'lat'        => $r['latitude']  !== null ? floatval($r['latitude'])  : null,
            'lng'        => $r['longitude'] !== null ? floatval($r['longitude']) : null,
            'marca'      => (string) $r['marca'],
        ];
    } catch (Throwable $e) {
        return null;
    }
}

function obtenerCoordsCliente(mysqli $conn, int $id_cliente): ?array {
    $stmt = $conn->prepare("SELECT lat, lng FROM clientes WHERE IDCLTE = ? LIMIT 1");
    $stmt->bind_param("i", $id_cliente);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$r || $r['lat'] === null || $r['lng'] === null) return null;
    return ['lat' => floatval($r['lat']), 'lng' => floatval($r['lng'])];
}

/** Sedes MESS (cacheadas). Cada una con su radio en km (default 3). */
function obtenerSedes(mysqli $conn): array {
    static $sedes = null;
    if ($sedes !== null) return $sedes;
    $sedes = [];
    $r = @$conn->query("SELECT id, nombre, lat, lng, radio_km FROM sedes");
    if ($r) {
        while ($s = $r->fetch_assoc()) {
            $sedes[] = [
                'id'     => intval($s['id']),
                'nombre' => $s['nombre'],
                'lat'    => floatval($s['lat']),
                'lng'    => floatval($s['lng']),
                'radio'  => floatval($s['radio_km']) > 0 ? floatval($s['radio_km']) : 3.0,
            ];
        }
    }
    return $sedes;
}

/**
 * Nave de RRHH (mess_rrhh.nave.idNave) -> sede con coordenadas (sedes.id).
 *
 * RRHH tiene 17 naves y solo 7 tienen sede con coordenadas. Las que no están aquí
 * (Messen, Conmed, Nave 26, Valeo, CMA, SJR, CDMX, León, DCM) y los usuarios sin nave
 * usan la sede más cercana al punto del check-in. Si se da de alta una sede nueva en
 * `sedes`, agregar aquí su nave.
 */
const NAVE_A_SEDE = [
    1  => 1,   // Mess            -> Nave Principal Qro
    6  => 2,   // SFG             -> Nave SFG Qro
    5  => 3,   // San Luis Potosí -> Nave SLP
    4  => 4,   // Zona Norte      -> Nave MTY
    8  => 5,   // Aguascalientes  -> Nave AGS
    16 => 6,   // Guadalajara     -> Nave Guadalajara
    13 => 6,   // Jalisco         -> Nave Guadalajara
    14 => 7,   // Nave 6          -> Nave 6 (dada de alta el 27/10/2026)
];

/**
 * Sede a la que "vuelve" un usuario con el botón "Volver a la sede": la de su nave de
 * RRHH o, si su nave no tiene sede, la más cercana al punto del check-in.
 *
 * La nave se busca por noEmpleado y no por id_usuario, que no es único (hay ids
 * duplicados entre personas distintas en mess_rrhh.usuarios).
 *
 * @return array{id:int,nombre:string,lat:float,lng:float,fuente:string}|null
 */
function sedeDeRegreso(mysqli $conn, int $noEmpleado, float $lat, float $lng): ?array {
    $sedes = obtenerSedes($conn);
    if (!$sedes) return null;

    $idNave = 0;
    $stmt = $conn->prepare("SELECT nave FROM mess_rrhh.usuarios WHERE noEmpleado = ? AND estatus = 1 LIMIT 1");
    if ($stmt) {
        $ne = (string) $noEmpleado;
        $stmt->bind_param("s", $ne);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $idNave = intval($row['nave'] ?? 0);
    }

    if (isset(NAVE_A_SEDE[$idNave])) {
        foreach ($sedes as $s) {
            if ($s['id'] === NAVE_A_SEDE[$idNave]) {
                return ['id' => $s['id'], 'nombre' => $s['nombre'], 'lat' => $s['lat'], 'lng' => $s['lng'], 'fuente' => 'nave'];
            }
        }
    }

    $cercana = null;
    $distMin = INF;
    foreach ($sedes as $s) {
        $d = haversineRectaKm($lat, $lng, $s['lat'], $s['lng']);
        if ($d < $distMin) { $distMin = $d; $cercana = $s; }
    }
    return ['id' => $cercana['id'], 'nombre' => $cercana['nombre'], 'lat' => $cercana['lat'], 'lng' => $cercana['lng'], 'fuente' => 'cercana'];
}

/** Distancia en línea recta (sin factor), para el chequeo de radio de sede. */
function haversineRectaKm(float $lat1, float $lng1, float $lat2, float $lng2): float {
    $R = 6371.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    return 2 * $R * asin(sqrt($a));
}

/** True si (lat,lng) cae dentro del radio de alguna sede MESS. */
function dentroDeSede(mysqli $conn, float $lat, float $lng): bool {
    foreach (obtenerSedes($conn) as $s) {
        if (haversineRectaKm($lat, $lng, $s['lat'], $s['lng']) <= $s['radio']) return true;
    }
    return false;
}

/**
 * Destino del km para una actividad OTROS: las coordenadas de la propia
 * actividad, salvo que sean inválidas (0,0) o caigan dentro del radio de una
 * sede MESS, en cuyo caso se usan las coordenadas registradas del cliente.
 * @return array{lat:float,lng:float,fuente:string}|null
 */
function resolverDestino(mysqli $conn, ?float $actLat, ?float $actLng, int $id_cliente): ?array {
    if ($actLat !== null && $actLng !== null && !($actLat == 0.0 && $actLng == 0.0)) {
        if (!dentroDeSede($conn, $actLat, $actLng)) {
            return ['lat' => $actLat, 'lng' => $actLng, 'fuente' => 'actividad'];
        }
    }
    $c = obtenerCoordsCliente($conn, $id_cliente);
    if ($c !== null) $c['fuente'] = 'cliente';
    return $c;
}

function buscarLineaInegi(float $lng, float $lat, string $key, int $escala = 250000): ?array {
    $ch = curl_init('https://gaia.inegi.org.mx/sakbe_v3.1/buscalinea');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'x' => $lng, 'y' => $lat, 'escala' => $escala,
            'type' => 'json', 'proj' => 'GRS80', 'key' => $key,
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_ENCODING       => '',
        CURLOPT_TIMEOUT        => 15,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    $json = json_decode($resp, true);
    return is_array($json) ? $json : null;
}

function calcularRutaInegi(array $origen, array $destino, string $key): ?array {
    $ch = curl_init('https://gaia.inegi.org.mx/sakbe_v3.1/libre');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'id_i'     => $origen['id_routing_net'],
            'source_i' => $origen['source'],
            'target_i' => $origen['target'],
            'id_f'     => $destino['id_routing_net'],
            'source_f' => $destino['source'],
            'target_f' => $destino['target'],
            'v'        => 1,
            'type'     => 'json',
            'key'      => $key,
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_ENCODING       => '',
        CURLOPT_TIMEOUT        => 20,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    $json = json_decode($resp, true);
    return is_array($json) ? $json : null;
}

function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float {
    return round(haversineRectaKm($lat1, $lng1, $lat2, $lng2) * HAVERSINE_FACTOR, 3);
}

/**
 * Ruta INEGI probando escalas de fina a gruesa hasta que origen y destino
 * resuelvan en el MISMO escala y /libre devuelva distancia. Devuelve
 * ['km','tiempo','escala'] o null si ningún escala funciona.
 */
function rutaInegiCascada(float $oLat, float $oLng, float $dLat, float $dLng, string $key): ?array {
    foreach (ESCALAS_INEGI as $e) {
        $lo = buscarLineaInegi($oLng, $oLat, $key, $e);
        if (!isset($lo['data']['id_routing_net'])) continue;
        $ld = buscarLineaInegi($dLng, $dLat, $key, $e);
        if (!isset($ld['data']['id_routing_net'])) continue;
        $ruta = calcularRutaInegi($lo['data'], $ld['data'], $key);
        if (isset($ruta['data']['long_km'])) {
            return [
                'km'     => floatval($ruta['data']['long_km']),
                'tiempo' => isset($ruta['data']['tiempo_min']) ? floatval($ruta['data']['tiempo_min']) : null,
                'escala' => $e,
            ];
        }
    }
    return null;
}

function registrarKm(mysqli $conn, $ov_ot, $id_cliente, $oLat, $oLng, $dLat, $dLng, $km, $min, $metodo, $estatus, $error, $id_usuario, int $id_actividad = 0): void {
    // Si este check-in ya dejó un placeholder 'sin_actividad', se RELLENA esa misma
    // fila (no se duplica) — es lo que pasa cuando el batch encuentra la actividad.
    if ($id_actividad > 0) {
        $q = $conn->prepare("SELECT id FROM km_calculados WHERE id_actividad = ? AND estatus = 'sin_actividad' ORDER BY id DESC LIMIT 1");
        $q->bind_param("i", $id_actividad);
        $q->execute();
        $row = $q->get_result()->fetch_assoc();
        $q->close();
        if ($row) {
            $u = $conn->prepare("UPDATE km_calculados
                SET ov_ot = ?, id_cliente = ?, destino_lat = ?, destino_lng = ?, long_km = ?, tiempo_min = ?, metodo = ?, estatus = ?, error_msg = ?
                WHERE id = ?");
            $u->bind_param("siddddsssi", $ov_ot, $id_cliente, $dLat, $dLng, $km, $min, $metodo, $estatus, $error, $row['id']);
            $u->execute();
            $u->close();
            refrescarKpiKm($conn, (int) $row['id']);   // KPI Power BI
            return;
        }
    }

    $stmt = $conn->prepare("INSERT INTO km_calculados
        (ov_ot, id_cliente, origen_lat, origen_lng, destino_lat, destino_lng, long_km, tiempo_min, metodo, estatus, error_msg, id_usuario, id_actividad)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "siddddddsssii",
        $ov_ot, $id_cliente, $oLat, $oLng, $dLat, $dLng, $km, $min, $metodo, $estatus, $error, $id_usuario, $id_actividad
    );
    $stmt->execute();
    $nuevoId = $conn->insert_id;
    $stmt->close();
    refrescarKpiKm($conn, (int) $nuevoId);   // KPI Power BI
}

// ---------------------------------------------------------------------------
// reporte_km — tabla denormalizada (fact table) para el KPI de Power BI.
//
// Grano: una fila por trayecto calculado (= una fila de km_calculados),
// enriquecida con dimensiones de vehículo, usuario y cliente. Power BI se
// conecta directo a esta tabla. Se mantiene viva por dos vías:
//   - upsert por fila tras cada cálculo (hook en registrarKm, arriba);
//   - reconstrucción total con reportes/refrescar_reporte_km.php (cron/manual).
// ---------------------------------------------------------------------------

// Columnas destino, en el MISMO orden que las expresiones de sqlSelectReporteKm().
const COLS_REPORTE_KM = 'id_km, id_actividad, fecha, anio, mes, id_vehiculo, placa, marca, modelo, anio_vehiculo, area, id_usuario, nombre_usuario, no_empleado, region, departamento, ov_ot, tipo_ref, id_cliente, cliente, estado, municipio, km, tiempo_min, costo_ov, metodo, estatus, km_valido';

function crearTablaReporteKm(mysqli $conn): void {
    $conn->query("CREATE TABLE IF NOT EXISTS reporte_km (
        id_reporte     INT AUTO_INCREMENT PRIMARY KEY,
        id_km          INT NOT NULL,
        id_actividad   INT NULL,
        fecha          DATETIME NULL,
        anio           SMALLINT NULL,
        mes            TINYINT NULL,
        id_vehiculo    INT NULL,
        placa          VARCHAR(9) NULL,
        marca          VARCHAR(50) NULL,
        modelo         VARCHAR(50) NULL,
        anio_vehiculo  INT NULL,
        area           VARCHAR(100) NULL,
        id_usuario     INT NULL,
        nombre_usuario VARCHAR(150) NULL,
        no_empleado    VARCHAR(11) NULL,
        region         INT NULL,
        departamento   INT NULL,
        ov_ot          VARCHAR(100) NULL,
        tipo_ref       ENUM('OV','OT','OTROS','SIN') NULL,
        id_cliente     INT NULL,
        cliente        VARCHAR(255) NULL,
        estado         VARCHAR(100) NULL,
        municipio      VARCHAR(100) NULL,
        km             DECIMAL(10,3) NULL,
        tiempo_min     DECIMAL(10,2) NULL,
        costo_ov       VARCHAR(50) NULL,
        metodo         ENUM('inegi','haversine') NULL,
        estatus        ENUM('ok','sin_destino','error_api','sin_actividad') NULL,
        km_valido      TINYINT(1) NOT NULL DEFAULT 0,
        actualizado    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_id_km (id_km),
        KEY idx_fecha (fecha),
        KEY idx_vehiculo (id_vehiculo),
        KEY idx_usuario (id_usuario),
        KEY idx_cliente (id_cliente),
        KEY idx_tipo (tipo_ref),
        KEY idx_km_valido (km_valido)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/**
 * Tope de km por trayecto (03/11/2026).
 *
 * Tabla derivada: por cada lectura de odómetro (id_actividad), lo que avanzó hasta la
 * lectura siguiente del mismo vehículo. Mismo LEAD y mismo descarte (negativos o más de
 * KM_VIAJE_MAX) que el odómetro de sqlAggReporteKmVehiculo, así que los km de trabajo
 * topados nunca suman más que el odómetro. km_tramo es NULL cuando aún no hay lectura
 * siguiente (el último check-in del vehículo).
 *
 * Los km de trabajo de un check-in = LEAST(ruta calculada, km_tramo): la ruta es solo de
 * ida y el tramo incluye la vuelta, así que un viaje real casi nunca topa; lo que se
 * recorta son destinos mal ubicados (clientes con domicilio fiscal en otra ciudad, la OT
 * ZEISSI26-06I-769 con cliente MESS Querétaro usada en Saltillo: 655 km por check-in). La
 * ruta original sigue en km_calculados.long_km.
 */
function sqlTramoOdometro(): string {
    $max = KM_VIAJE_MAX;
    return "(SELECT id_actividad,
                    CASE WHEN delta IS NULL THEN NULL
                         WHEN delta BETWEEN 0 AND $max THEN delta ELSE 0 END AS km_tramo
             FROM (SELECT id_actividad,
                          LEAD(km_actual) OVER (PARTITION BY id_vehiculo ORDER BY fecha_actividad, id_actividad) - km_actual AS delta
                   FROM actividad_vehiculo
                   WHERE km_actual > 0) d)";
}

// Km de trabajo topados de un trayecto. Sin lectura siguiente cuenta 0 hasta que llegue.
// Requiere `km_calculados k` y `LEFT JOIN sqlTramoOdometro() tr ON tr.id_actividad = k.id_actividad`.
const SQL_KM_TOPADO = 'LEAST(k.long_km, COALESCE(tr.km_tramo, 0))';

// SELECT denormalizado reutilizado por el upsert y el rebuild. $where es código
// fijo interno ('WHERE k.id = ?'), nunca datos del usuario.
//
// usuarios.id_usuario NO es único (hay duplicados y 0/NULL): se empareja con la
// fila de mayor id por id_usuario para no multiplicar el grano del fact table. Va como
// tabla derivada y no como `u.id = (SELECT MAX(u2.id) ...)` en el ON, que se reevalúa por
// cada pareja de filas (en acciones_mapa.php pasó de 17.7 s a 2.2 s).
//
// `km` es el topado por el odómetro (SQL_KM_TOPADO), no la ruta calculada.
function sqlSelectReporteKm(string $where = ''): string {
    return "SELECT
            k.id, k.id_actividad,
            COALESCE(av.fecha_actividad, k.fecha),
            YEAR(COALESCE(av.fecha_actividad, k.fecha)),
            MONTH(COALESCE(av.fecha_actividad, k.fecha)),
            av.id_vehiculo, inv.placa, inv.marca, inv.modelo, inv.anio, inv.area,
            k.id_usuario, u.nombre, u.noEmpleado, u.region, u.departamento,
            k.ov_ot,
            CASE WHEN ot.OT IS NOT NULL THEN 'OT'
                 WHEN ov.OV IS NOT NULL THEN 'OV'
                 WHEN k.estatus = 'sin_actividad' OR k.ov_ot IS NULL OR k.ov_ot = '' THEN 'SIN'
                 ELSE 'OTROS' END,
            k.id_cliente, c.CLIENTE, c.ESTADO, c.MUNICIPIO,
            " . SQL_KM_TOPADO . ", k.tiempo_min, av.costoOv, k.metodo, k.estatus,
            CASE WHEN k.estatus = 'ok' AND k.long_km IS NOT NULL THEN 1 ELSE 0 END
        FROM km_calculados k
        LEFT JOIN actividad_vehiculo av ON av.id_actividad = k.id_actividad
        LEFT JOIN " . sqlTramoOdometro() . " tr ON tr.id_actividad = k.id_actividad
        LEFT JOIN inventario inv        ON inv.id_vehiculo = av.id_vehiculo
        LEFT JOIN (SELECT id_usuario, MAX(id) AS id FROM usuarios GROUP BY id_usuario) um ON um.id_usuario = k.id_usuario
        LEFT JOIN usuarios u            ON u.id = um.id
        LEFT JOIN clientes c            ON c.IDCLTE = k.id_cliente
        LEFT JOIN OT ot                 ON ot.OT = k.ov_ot
        LEFT JOIN OV ov                 ON ov.OV = k.ov_ot
        $where";
}

// Refresca reporte_km. Con $idVehiculo: reemplaza los trayectos de ese vehículo (hook por
// check-in: la lectura nueva cambia el tope del trayecto anterior). Con $idKm: solo esa
// fila. Sin ninguno: reconstrucción total. Nunca lanza: el KPI jamás debe tumbar el flujo
// de km (check-in / importación).
function refrescarReporteKm(mysqli $conn, int $idKm = 0, int $idVehiculo = 0): void {
    try {
        crearTablaReporteKm($conn);
        $cols = COLS_REPORTE_KM;
        if ($idVehiculo > 0) {
            $d = $conn->prepare("DELETE FROM reporte_km WHERE id_vehiculo = ?");
            $d->bind_param("i", $idVehiculo);
            $d->execute();
            $d->close();
            $ins = $conn->prepare("INSERT INTO reporte_km ($cols) " . sqlSelectReporteKm("WHERE av.id_vehiculo = ?"));
            $ins->bind_param("i", $idVehiculo);
            $ins->execute();
            $ins->close();
        } elseif ($idKm > 0) {
            $d = $conn->prepare("DELETE FROM reporte_km WHERE id_km = ?");
            $d->bind_param("i", $idKm);
            $d->execute();
            $d->close();
            $ins = $conn->prepare("INSERT INTO reporte_km ($cols) " . sqlSelectReporteKm("WHERE k.id = ?"));
            $ins->bind_param("i", $idKm);
            $ins->execute();
            $ins->close();
        } else {
            $conn->query("DELETE FROM reporte_km");
            $conn->query("INSERT INTO reporte_km ($cols) " . sqlSelectReporteKm());
        }
    } catch (Throwable $e) { /* el KPI nunca debe tumbar el flujo de km */ }
}

// ---------------------------------------------------------------------------
// reporte_km_vehiculo — resumen por VEHÍCULO × SEMANA (ISO 8601) para el KPI
// de uso (Power BI).
//
// El QR es el registro único; NO se depende del par INICIO/FINALIZACION.
//  Por vehículo y semana:
//   - km_registrado:         km reales de ODÓMETRO = Σ diferencias entre lecturas
//                            de km_actual CONSECUTIVAS del vehículo (por tiempo,
//                            sin importar el tipo de actividad).
//   - km_trabajo_calculado:  km calculados por INEGI/API de las actividades de
//                            trabajo de la semana (de reporte_km, km_valido=1), cada
//                            uno topado por lo que avanzó el odómetro hasta la lectura
//                            siguiente (sqlTramoOdometro). Por eso nunca pasa de
//                            km_registrado.
//   - porcentaje_uso_trabajo = km_trabajo_calculado / km_registrado * 100.
//
// El delta de odómetro se descarta si es negativo o > KM_VIAJE_MAX (reseteos de
// tablero, p. ej. 192k→25k, y typos de captura, p. ej. 95152 entre 29k). Cada
// delta se asigna a la semana de su lectura inicial.
//
// La semana es ISO 8601 (lunes-domingo, vía YEARWEEK(fecha, 3)): `anio` es el
// año ISO de esa semana, que puede diferir del año calendario en los bordes de
// diciembre/enero — por eso siempre se decompone YEARWEEK() combinado en vez de
// usar YEAR()+WEEK() por separado. `semana_inicio` es el lunes de esa semana.
// ---------------------------------------------------------------------------

const COLS_REPORTE_KM_VEHICULO = 'id_vehiculo, anio, semana, semana_inicio, placa, marca, modelo, area, segmentos_odo, km_registrado, actividades_trabajo, km_trabajo_calculado, porcentaje_uso_trabajo';

// Tope de km por tramo (lectura a lectura) para considerar válido el delta de
// odómetro: descarta reseteos de tablero y errores de captura.
const KM_VIAJE_MAX = 2000;

function crearTablaReporteKmVehiculo(mysqli $conn): void {
    $conn->query("CREATE TABLE IF NOT EXISTS reporte_km_vehiculo (
        id_resumen             INT AUTO_INCREMENT PRIMARY KEY,
        id_vehiculo            INT NOT NULL,
        anio                   SMALLINT NOT NULL,
        semana                 TINYINT NOT NULL,
        semana_inicio          DATE NULL,
        placa                  VARCHAR(9) NULL,
        marca                  VARCHAR(50) NULL,
        modelo                 VARCHAR(50) NULL,
        area                   VARCHAR(100) NULL,
        segmentos_odo          INT NOT NULL DEFAULT 0,
        km_registrado          DECIMAL(12,3) NOT NULL DEFAULT 0,
        actividades_trabajo    INT NOT NULL DEFAULT 0,
        km_trabajo_calculado   DECIMAL(12,3) NOT NULL DEFAULT 0,
        porcentaje_uso_trabajo DECIMAL(6,2) NULL,
        actualizado            TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_veh_periodo (id_vehiculo, anio, semana),
        KEY idx_periodo (anio, semana)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// Agregación vehículo×semana (reconstrucción total). Combina dos fuentes por (vehículo,
// año ISO, semana ISO): el odómetro real (lecturas consecutivas de actividad_vehiculo) y
// el km de trabajo calculado (reporte_km). Se unen por la UNIÓN de sus llaves, porque una
// semana puede tener movimiento sin trabajo calculado o viceversa.
function sqlAggReporteKmVehiculo(): string {
    $max = KM_VIAJE_MAX;
    return "
        WITH odo AS (
            SELECT id_vehiculo, anio, semana, MIN(semana_inicio) AS semana_inicio,
                   SUM(CASE WHEN delta BETWEEN 0 AND $max THEN delta ELSE 0 END) AS km_total,
                   SUM(CASE WHEN delta BETWEEN 0 AND $max THEN 1    ELSE 0 END) AS segmentos
            FROM (
                SELECT av.id_vehiculo,
                       FLOOR(YEARWEEK(av.fecha_actividad, 3) / 100) AS anio,
                       MOD(YEARWEEK(av.fecha_actividad, 3), 100)    AS semana,
                       DATE_SUB(DATE(av.fecha_actividad), INTERVAL WEEKDAY(av.fecha_actividad) DAY) AS semana_inicio,
                       (LEAD(av.km_actual) OVER (PARTITION BY av.id_vehiculo ORDER BY av.fecha_actividad, av.id_actividad) - av.km_actual) AS delta
                FROM actividad_vehiculo av
                WHERE av.km_actual > 0
            ) d
            GROUP BY id_vehiculo, anio, semana
        ),
        trab AS (
            SELECT id_vehiculo,
                   FLOOR(YEARWEEK(fecha, 3) / 100) AS anio,
                   MOD(YEARWEEK(fecha, 3), 100)    AS semana,
                   MIN(DATE_SUB(DATE(fecha), INTERVAL WEEKDAY(fecha) DAY)) AS semana_inicio,
                   COUNT(*)              AS actividades,
                   ROUND(SUM(km), 3)     AS km_trabajo
            FROM reporte_km
            WHERE km_valido = 1 AND id_vehiculo IS NOT NULL AND fecha IS NOT NULL
            GROUP BY id_vehiculo, anio, semana
        ),
        base AS (
            SELECT id_vehiculo, anio, semana FROM odo
            UNION
            SELECT id_vehiculo, anio, semana FROM trab
        )
        SELECT base.id_vehiculo, base.anio, base.semana,
               COALESCE(odo.semana_inicio, trab.semana_inicio) AS semana_inicio,
               i.placa, i.marca, i.modelo, i.area,
               COALESCE(odo.segmentos, 0)   AS segmentos_odo,
               COALESCE(odo.km_total, 0)    AS km_registrado,
               COALESCE(trab.actividades, 0) AS actividades_trabajo,
               COALESCE(trab.km_trabajo, 0)  AS km_trabajo_calculado,
               ROUND(COALESCE(trab.km_trabajo, 0) / NULLIF(odo.km_total, 0) * 100, 2) AS porcentaje_uso_trabajo
        FROM base
        LEFT JOIN odo  USING (id_vehiculo, anio, semana)
        LEFT JOIN trab USING (id_vehiculo, anio, semana)
        LEFT JOIN inventario i ON i.id_vehiculo = base.id_vehiculo";
}

// Refresca reporte_km_vehiculo (reconstrucción total — es un agregado pequeño).
// Nunca lanza: el KPI no debe tumbar el flujo de km.
function refrescarReporteKmVehiculo(mysqli $conn): void {
    try {
        crearTablaReporteKmVehiculo($conn);
        $conn->query("DELETE FROM reporte_km_vehiculo");
        $conn->query("INSERT INTO reporte_km_vehiculo (" . COLS_REPORTE_KM_VEHICULO . ") " . sqlAggReporteKmVehiculo());
    } catch (Throwable $e) { /* el KPI nunca debe tumbar el flujo de km */ }
}

// Refresca ambos KPIs tras un cambio de km. Lo llama registrarKm en sus dos
// rutas (rellenar placeholder / insertar): el fact por trayecto (esa fila) y el
// resumen vehículo×semana (reconstrucción completa, es pequeño).
//
// Con el tope por odómetro (sqlTramoOdometro), el check-in que acaba de llegar es la
// "lectura siguiente" del trayecto anterior del mismo vehículo, que hasta ahora contaba 0:
// por eso se recalculan todos los trayectos del vehículo y no solo esta fila.
function refrescarKpiKm(mysqli $conn, int $idKm): void {
    $idVehiculo = 0;
    try {
        $q = $conn->prepare("SELECT av.id_vehiculo FROM km_calculados k
                             INNER JOIN actividad_vehiculo av ON av.id_actividad = k.id_actividad
                             WHERE k.id = ?");
        $q->bind_param("i", $idKm);
        $q->execute();
        $idVehiculo = (int) ($q->get_result()->fetch_assoc()['id_vehiculo'] ?? 0);
        $q->close();
    } catch (Throwable $e) { /* sin vehículo: se refresca solo la fila */ }

    if ($idVehiculo > 0) refrescarReporteKm($conn, 0, $idVehiculo);
    else                 refrescarReporteKm($conn, $idKm);
    refrescarReporteKmVehiculo($conn);
}

/**
 * Calcula la ruta INEGI (fallback haversine) entre origen y destino y registra
 * la fila en km_calculados. Reutilizable por el endpoint y por el batch.
 * @return array{km:?float,tiempo_min:?float,metodo:string,estatus:string,aviso:?string}
 */
// $id_cliente es null en "Volver a la sede": el destino es una nave, no un cliente.
function calcularYRegistrar(mysqli $conn, $ov_ot, ?int $id_cliente, float $oLat, float $oLng, array $destino, ?int $id_usuario, int $id_actividad = 0): array {
    $r = calcularKmRuta($oLat, $oLng, $destino['lat'], $destino['lng']);
    registrarKm($conn, $ov_ot, $id_cliente, $oLat, $oLng, $destino['lat'], $destino['lng'],
        $r['km'], $r['tiempo_min'], $r['metodo'], $r['estatus'], $r['aviso'], $id_usuario, $id_actividad);
    return $r;
}

/**
 * Km por carretera entre dos puntos: INEGI y, si no hay ruta, haversine. No escribe
 * nada; lo comparten calcularYRegistrar() y la vista previa de "Volver a la sede".
 * @return array{km:float,tiempo_min:?float,metodo:string,estatus:string,aviso:?string}
 */
function calcularKmRuta(float $oLat, float $oLng, float $dLat, float $dLng): array {
    $key  = tokenInegi();
    $ruta = $key ? rutaInegiCascada($oLat, $oLng, $dLat, $dLng, $key) : null;

    if ($ruta !== null) {
        return ['km' => $ruta['km'], 'tiempo_min' => $ruta['tiempo'], 'metodo' => 'inegi', 'estatus' => 'ok', 'aviso' => null];
    }
    return [
        'km'         => haversineKm($oLat, $oLng, $dLat, $dLng),
        'tiempo_min' => null,
        'metodo'     => 'haversine',
        'estatus'    => 'error_api',
        'aviso'      => $key === '' ? 'Token INEGI ausente' : 'INEGI sin ruta en ninguna escala',
    ];
}

/**
 * Reconciliación batch (se llama al importar el CSV de Actividades).
 *
 * Busca check-ins "incompletos" — registros de actividad_vehiculo tipo INICIO,
 * con GPS, cuyo km aún no se calculó (order_code IS NULL) — y los empareja con
 * la actividad OTROS del MISMO DÍA del usuario que acaba de llegar en el CSV.
 * Cubre el caso en que el chofer sube su registro de actividad después del
 * check-in. Los check-ins de OV/OT se ignoran (ya se calculan en vivo).
 *
 * Por lotes: cada check-in que empareja hace varias llamadas a INEGI, y en el
 * hosting una sola petición que tarde minutos se corta (ERR_CONNECTION_RESET).
 * Con $limiteSeg > 0 se detiene al agotar el tiempo y devuelve 'terminado' => false
 * y los cursores (último id_actividad revisado de cada fase) para que la siguiente
 * llamada continúe donde quedó. Sin límite (CLI) procesa todo de una vez.
 *
 * @return array contadores: revisados, calculados, sin_actividad, sin_destino, ovot,
 *               regresos, terminado, desde_checkin, desde_regreso
 */
function reconciliarKmPendientes(mysqli $conn, int $diasAtras = 30, int $desdeCheckin = 0, int $desdeRegreso = 0, float $limiteSeg = 0): array {
    $res = ['revisados' => 0, 'calculados' => 0, 'sin_actividad' => 0, 'sin_destino' => 0, 'ovot' => 0,
            'regresos' => 0, 'terminado' => false, 'desde_checkin' => $desdeCheckin, 'desde_regreso' => $desdeRegreso];
    $vence = $limiteSeg > 0 ? microtime(true) + $limiteSeg : INF;
    $procesados = 0;   // al menos uno por llamada, para que siempre avance
    $agotado = function () use (&$procesados, $vence) { return $procesados > 0 && microtime(true) > $vence; };

    $stmt = $conn->prepare("SELECT id_actividad, id_usuario, coordenadas, ot, fecha_actividad
        FROM actividad_vehiculo
        WHERE order_code IS NULL
          AND tipo_actividad = 'INICIO'
          AND coordenadas IS NOT NULL AND coordenadas <> ''
          AND fecha_actividad >= (NOW() - INTERVAL ? DAY)
          AND id_actividad > ?
        ORDER BY id_actividad");
    if (!$stmt) { $res['terminado'] = true; return $res; }
    $stmt->bind_param("ii", $diasAtras, $desdeCheckin);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($rows as $row) {
        if ($agotado()) return $res;
        $procesados++;
        $res['desde_checkin'] = (int) $row['id_actividad'];
        $res['revisados']++;

        $partes = explode(',', (string) $row['coordenadas']);
        if (count($partes) < 2) continue;
        $oLat = floatval(trim($partes[0]));
        $oLng = floatval(trim($partes[1]));
        if ($oLat == 0.0 && $oLng == 0.0) continue;

        // Si el ot resuelve como OV/OT, ya se manejó en vivo: saltar.
        $ot = trim((string) $row['ot']);
        if ($ot !== '' && resolverCliente($conn, $ot) !== null) { $res['ovot']++; continue; }

        $id_usuario = intval($row['id_usuario']);
        $otros = resolverClienteOtros($conn, $id_usuario, (string) $row['fecha_actividad']);
        if ($otros === null) { $res['sin_actividad']++; continue; }

        $destino = resolverDestino($conn, $otros['lat'], $otros['lng'], $otros['id_cliente']);
        if ($destino === null) { $res['sin_destino']++; continue; }

        calcularYRegistrar($conn, $otros['actividad'], $otros['id_cliente'], $oLat, $oLng, $destino, $id_usuario, (int) $row['id_actividad']);

        // Marca el check-in: tipo de actividad, order_code de SCOT y limpia `ot`.
        marcarCheckin($conn, (int) $row['id_actividad'], [
            'detalle'    => $otros['actividad'],
            'order_code' => $otros['marca'],
            'limpiar_ot' => true,
        ]);
        $res['calculados']++;
    }

    // Red de seguridad de "Volver a la sede": su km lo pide la vista en segundo plano
    // (calcular_ruta.php con regreso=1). Si esa llamada no llegó (se cerró la página,
    // se cayó la red), el regreso quedaría sin km; aquí se calcula. Los que ya tienen su
    // fila en km_calculados no se tocan. El noEmpleado sale de usuarios con MAX(id)
    // porque id_usuario no es único.
    // Fase de check-ins terminada: el cursor queda al tope para que la siguiente
    // llamada vaya directo a los regresos.
    $res['desde_checkin'] = 2147483647;   // tope de id_actividad (INT)
    $stmtR = $conn->prepare("SELECT av.id_actividad, av.id_usuario, av.coordenadas,
            (SELECT u.noEmpleado FROM usuarios u WHERE u.id = (SELECT MAX(u2.id) FROM usuarios u2 WHERE u2.id_usuario = av.id_usuario)) AS noEmpleado
        FROM actividad_vehiculo av
        WHERE av.detalle_tipo_uso = 'REGRESO_SEDE' AND av.tipo_actividad = 'INICIO'
          AND av.fecha_actividad >= (NOW() - INTERVAL ? DAY)
          AND av.id_actividad > ?
          AND NOT EXISTS (SELECT 1 FROM km_calculados k WHERE k.id_actividad = av.id_actividad)
        ORDER BY av.id_actividad");
    if ($stmtR) {
        $stmtR->bind_param("ii", $diasAtras, $desdeRegreso);
        $stmtR->execute();
        $regresos = $stmtR->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmtR->close();
        foreach ($regresos as $r) {
            if ($agotado()) return $res;
            $procesados++;
            $res['desde_regreso'] = (int) $r['id_actividad'];
            $c = array_map('trim', explode(',', (string) $r['coordenadas']));
            if (count($c) < 2 || !is_numeric($c[0]) || !is_numeric($c[1])) continue;
            procesarKmRegresoSede($conn, (float) $c[0], (float) $c[1], intval($r['id_usuario']) ?: null,
                intval($r['noEmpleado']), (int) $r['id_actividad']);
            $res['regresos']++;
        }
    }
    $res['terminado'] = true;
    return $res;
}

/**
 * Orquesta el cálculo de km de UN check-in: resuelve cliente (OV/OT u OTROS del
 * día), resuelve destino (coords de actividad validadas vs sedes, o del cliente),
 * registra km_calculados y marca order_code en el check-in. Reutilizable por el
 * endpoint QR y por el check-in del modal global (acciones_kilometraje.php).
 *
 * @param string $fechaRef fecha/hora del check-in ('Y-m-d H:i:s') para el match OTROS.
 * @return array resultado con 'status' => 'success'|'error'.
 */
/** Tipos de actividad SCOT (no son OV/OT). Si llegan en `ot`, NO son códigos
 *  válidos: se limpian de `ot` y el tipo va a detalle_tipo_uso. */
const TIPOS_ACTIVIDAD = ['VISITA', 'APPVISITA', 'OTRO'];

function esTipoActividad(string $v): bool {
    return in_array(strtoupper(trim($v)), TIPOS_ACTIVIDAD, true);
}

/**
 * Marca el check-in (actividad_vehiculo) tras resolver su km:
 *  - 'order_code': el de la actividad OTROS (link a SCOT + "ya calculado").
 *  - 'limpiar_ot': deja `ot` vacío cuando NO es OV/OT (ot SOLO guarda OV/OT).
 *  - 'detalle'   : tipo de actividad (VISITA/APPVISITA/OTRO); va a detalle_tipo_uso
 *    SOLO si está vacío (no pisa el tipoServicio real del modal global).
 */
function marcarCheckin(mysqli $conn, int $idActividad, array $c): void {
    if ($idActividad <= 0) return;

    if (!empty($c['order_code'])) {
        $s = $conn->prepare("UPDATE actividad_vehiculo SET order_code = ? WHERE id_actividad = ?");
        $s->bind_param("si", $c['order_code'], $idActividad);
        $s->execute(); $s->close();
    }
    if (!empty($c['limpiar_ot'])) {
        $s = $conn->prepare("UPDATE actividad_vehiculo SET ot = '' WHERE id_actividad = ?");
        $s->bind_param("i", $idActividad);
        $s->execute(); $s->close();
    }
    if (!empty($c['detalle'])) {
        $s = $conn->prepare("UPDATE actividad_vehiculo SET detalle_tipo_uso = ?
            WHERE id_actividad = ? AND (detalle_tipo_uso IS NULL OR detalle_tipo_uso = '')");
        $s->bind_param("si", $c['detalle'], $idActividad);
        $s->execute(); $s->close();
    }
}

function procesarKmCheckin(mysqli $conn, string $ov_ot, ?float $oLat, ?float $oLng, ?int $id_usuario, int $id_actividad, string $fechaRef): array {
    if ($oLat === null || $oLng === null) {
        return ['status' => 'error', 'message' => 'Sin coordenadas de origen'];
    }

    // Si `ot` trae un TIPO de actividad (VISITA/APPVISITA/OTRO), no es un OV/OT:
    // lo capturamos para detalle_tipo_uso y lo limpiamos de `ot`.
    $tipoActividad = esTipoActividad($ov_ot) ? strtoupper(trim($ov_ot)) : null;
    if ($tipoActividad !== null) $ov_ot = '';

    $id_cliente = $ov_ot !== '' ? resolverCliente($conn, $ov_ot) : null;
    $esOvOt     = $id_cliente !== null;      // el ot capturado SÍ es un OV/OT válido
    $otros      = null;

    // Fallback OTROS: si no es OV/OT, tomar la actividad del usuario del día.
    if ($id_cliente === null && $id_usuario) {
        $otros = resolverClienteOtros($conn, $id_usuario, $fechaRef);
        if ($otros !== null) $id_cliente = $otros['id_cliente'];
    }

    // Sin OV/OT y sin actividad OTROS: no hay destino. Igual:
    //  - guardamos el TIPO de actividad en detalle_tipo_uso y limpiamos `ot`;
    //  - dejamos un registro/indicador en km_calculados con estatus 'sin_actividad'
    //    (origen sí, destino/km no) para que ningún check-in quede sin huella.
    if ($id_cliente === null) {
        marcarCheckin($conn, $id_actividad, ['detalle' => $tipoActividad, 'limpiar_ot' => $tipoActividad !== null]);
        registrarKm($conn, (string) ($tipoActividad ?? ''), null, $oLat, $oLng,
            null, null, null, null, 'haversine', 'sin_actividad',
            'Sin OV/OT ni actividad OTROS del día', $id_usuario, $id_actividad);
        return ['status' => 'error', 'message' => $ov_ot !== '' ? "OV/OT '$ov_ot' no encontrada" : 'Sin actividad OTROS para el usuario'];
    }

    // En km_calculados.ov_ot va el código OV/OT, o el tipo de actividad si es OTROS.
    $ovOtKm = $esOvOt ? $ov_ot : (string) $otros['actividad'];

    // Destino: OTROS usa coords de la actividad (validadas vs sedes) o del
    // cliente; OV/OT usa siempre las del cliente.
    $destino = $otros !== null
        ? resolverDestino($conn, $otros['lat'], $otros['lng'], $id_cliente)
        : obtenerCoordsCliente($conn, $id_cliente);

    if ($destino === null) {
        registrarKm($conn, $ovOtKm, $id_cliente, $oLat, $oLng,
            null, null, null, null, 'haversine', 'sin_destino',
            'Cliente sin lat/lng en BD', $id_usuario, $id_actividad);
        if ($otros !== null) {
            marcarCheckin($conn, $id_actividad, ['detalle' => $otros['actividad'], 'order_code' => $otros['marca'], 'limpiar_ot' => true]);
        }
        return ['status' => 'error', 'message' => 'El cliente no tiene coordenadas registradas', 'id_cliente' => $id_cliente];
    }

    $r = calcularYRegistrar($conn, $ovOtKm, $id_cliente, $oLat, $oLng, $destino, $id_usuario, $id_actividad);

    // OTROS: tipo de actividad -> detalle_tipo_uso, order_code de SCOT, y se limpia
    // `ot` (solo guarda OV/OT). OV/OT: se respeta el `ot` capturado.
    if ($otros !== null) {
        marcarCheckin($conn, $id_actividad, ['detalle' => $otros['actividad'], 'order_code' => $otros['marca'], 'limpiar_ot' => true]);
    }

    return [
        'status'     => 'success',
        'km'         => $r['km'],
        'tiempo_min' => $r['tiempo_min'],
        'metodo'     => $r['metodo'],
        'aviso'      => $r['aviso'],
        'id_cliente' => $id_cliente,
    ];
}

/**
 * Km de "Volver a la sede": del punto del check-in a la nave del usuario.
 *
 * Al terminar las visitas la gente se va directo a su casa, y ese regreso caía como km
 * personal porque el check-in no tenía destino. Con este botón el trayecto hasta la nave
 * cuenta como trabajo aunque no pasen por ella (petición del 26/10/2026). Se puede usar
 * en cualquier check-in, sin límite por día.
 *
 * El check-in se marca con order_code = 'REGRESO_SEDE': reconciliarKmPendientes() vuelve
 * a calcular todo INICIO con order_code NULL y, sin la marca, contaría este trayecto dos
 * veces al encontrar la actividad OTROS del día.
 *
 * @return array{status:string,km?:?float,sede?:string,fuente?:string,message?:string}
 */
function procesarKmRegresoSede(mysqli $conn, float $oLat, float $oLng, ?int $id_usuario, int $noEmpleado, int $id_actividad): array {
    // La marca va PRIMERO: si el cálculo falla (INEGI caído, sin sedes), el check-in
    // igual queda fuera de la reconciliación y no se le asigna una actividad OTROS.
    marcarCheckin($conn, $id_actividad, ['order_code' => 'REGRESO_SEDE', 'detalle' => 'REGRESO_SEDE']);

    $sede = sedeDeRegreso($conn, $noEmpleado, $oLat, $oLng);
    if ($sede === null) {
        return ['status' => 'error', 'message' => 'No hay sedes registradas'];
    }

    $r = calcularYRegistrar($conn, 'REGRESO SEDE', null, $oLat, $oLng, $sede, $id_usuario, $id_actividad);

    return [
        'status' => 'success',
        'km'     => $r['km'] !== null ? round((float) $r['km'], 1) : null,
        'sede'   => $sede['nombre'],
        'fuente' => $sede['fuente'],
    ];
}

// ---------------------------------------------------------------------------
// Endpoint AJAX (solo cuando se invoca este archivo directamente)
// ---------------------------------------------------------------------------
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
    include __DIR__ . '/conn.php';
    header('Content-Type: application/json');
    mysqli_set_charset($conn, "utf8mb4");
    date_default_timezone_set('America/Mexico_City');

    $ov_ot        = isset($_POST['ov_ot']) ? trim($_POST['ov_ot']) : '';
    $origen_lat   = isset($_POST['lat'])   ? floatval($_POST['lat']) : null;
    $origen_lng   = isset($_POST['lng'])   ? floatval($_POST['lng']) : null;
    $id_actividad = isset($_POST['id_actividad']) ? intval($_POST['id_actividad']) : 0;
    $id_usuario   = intval($_COOKIE['id_usuarioL'] ?? $_COOKIE['id_usuario'] ?? 0) ?: null;

    // Vista previa de "Volver a la sede": a qué nave y cuántos km, para el aviso del modal
    // de check-in antes de que el usuario presione el botón. Solo lectura.
    if (($_POST['vista_regreso'] ?? '') === '1') {
        $noEmpleado = intval($_COOKIE['noEmpleado'] ?? 0);
        if ($noEmpleado <= 0 || $origen_lat === null || $origen_lng === null) {
            echo json_encode(['status' => 'error', 'message' => 'Parámetros incompletos']);
            exit;
        }
        $sede = sedeDeRegreso($conn, $noEmpleado, $origen_lat, $origen_lng);
        if ($sede === null) {
            echo json_encode(['status' => 'error', 'message' => 'No hay sedes registradas']);
            exit;
        }
        $r = calcularKmRuta($origen_lat, $origen_lng, $sede['lat'], $sede['lng']);
        echo json_encode([
            'status' => 'success',
            'sede'   => $sede['nombre'],
            'km'     => round((float) $r['km'], 1),
        ]);
        exit;
    }

    // "Volver a la sede": el km se calcula aquí, en segundo plano, después de que
    // checkInQR ya respondió. El origen sale del registro guardado (no del POST) y solo
    // se procesa un check-in REGRESO_SEDE de este mismo usuario que aún no tenga su km,
    // así que repetir la llamada no duplica nada.
    if (($_POST['regreso'] ?? '') === '1') {
        $stmt = $conn->prepare(
            "SELECT av.coordenadas,
                    EXISTS (SELECT 1 FROM km_calculados k WHERE k.id_actividad = av.id_actividad) AS ya_calculado
             FROM actividad_vehiculo av
             WHERE av.id_actividad = ? AND av.id_usuario = ? AND av.detalle_tipo_uso = 'REGRESO_SEDE'
             LIMIT 1"
        );
        $idUsr = intval($id_usuario);
        $stmt->bind_param("ii", $id_actividad, $idUsr);
        $stmt->execute();
        $act = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$act) {
            echo json_encode(['status' => 'error', 'message' => 'Check-in de regreso no encontrado']);
            exit;
        }
        if ($act['ya_calculado']) {
            echo json_encode(['status' => 'success', 'message' => 'Ya calculado']);
            exit;
        }
        $c = array_map('trim', explode(',', (string) $act['coordenadas']));
        if (count($c) < 2 || !is_numeric($c[0]) || !is_numeric($c[1])) {
            echo json_encode(['status' => 'error', 'message' => 'El check-in no tiene coordenadas']);
            exit;
        }
        echo json_encode(procesarKmRegresoSede($conn, (float) $c[0], (float) $c[1], $id_usuario,
            intval($_COOKIE['noEmpleado'] ?? 0), $id_actividad));
        exit;
    }

    if ($origen_lat === null || $origen_lng === null) {
        echo json_encode(['status' => 'error', 'message' => 'Parámetros incompletos']);
        exit;
    }

    echo json_encode(procesarKmCheckin($conn, $ov_ot, $origen_lat, $origen_lng, $id_usuario, $id_actividad, date('Y-m-d H:i:s')));
}
