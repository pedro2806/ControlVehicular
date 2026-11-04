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

// ---------------------------------------------------------------------------
// OT/OV escritas a mano. El catálogo guarda la OT como 'FZ26-01E-691' y la OV como
// '8068-2026', pero en el check-in llegan con texto pegado ('FZ26-01E-691; Salida a
// CDMX', 'PT26-01N-227 Salida ZF TRW') o con prefijo ('MESS-OV-8068-2026',
// 'Mess ov 5592 2026', 'MESS-OV-5845/2025'). Sin normalizar, en octubre de 2026 no
// empataban 112 OV ni las OT con texto. Las mismas expresiones se usan en PHP
// (resolverOvOt) y en SQL (sqlCheckinOvOt) para que el check-in y los reportes
// reconozcan lo mismo.
// ---------------------------------------------------------------------------

// Formato de OT del catálogo (99.6 % de las OT). Sin barras: sirve tal cual en REGEXP.
const RE_OT = '[A-Za-z]+[0-9]{2}-[0-9]{2}[A-Za-z]+-[0-9]+';
// Una OV solo se busca si el texto trae 'ov' como palabra ('MESS-OV', 'Mess ov', 'OV 4474');
// así 'MESS-CT-11243-2025-140' no se toma por la OV 11243-2025.
const RE_OV_MARCA = '(^|[^A-Za-z])[Oo][Vv]';
// Número y año de la OV, separados por guion, diagonal o espacio. Se normaliza a 'numero-año'.
const RE_OV_NUM = '[0-9]+ *[-/ ] *20[0-9]{2}';
const RE_OV_SEP = ' *[-/ ] *';

/**
 * Busca en el catálogo la OT/OV de lo que se capturó en el check-in: primero el texto
 * exacto (OT y luego OV), después la OT que venga dentro del texto y al final la OV
 * normalizada. Solo cuenta si la OT/OV tiene cliente.
 * @return array{tipo:string,codigo:string,id_cliente:int}|null codigo = valor del catálogo
 */
function resolverOvOt(mysqli $conn, string $texto): ?array {
    $texto = trim($texto);
    if ($texto === '') return null;

    $candidatos = [['OT', $texto], ['OV', $texto]];
    if (preg_match('~' . RE_OT . '~', $texto, $m)) $candidatos[] = ['OT', $m[0]];
    if (preg_match('~' . RE_OV_MARCA . '~', $texto) && preg_match('~' . RE_OV_NUM . '~', $texto, $m)) {
        $candidatos[] = ['OV', preg_replace('~' . RE_OV_SEP . '~', '-', $m[0])];
    }

    foreach ($candidatos as [$tabla, $codigo]) {
        // $tabla viene de la lista fija de arriba ('OT'/'OV'), nunca del usuario.
        $stmt = $conn->prepare("SELECT $tabla AS codigo, id_cliente FROM $tabla WHERE $tabla = ? LIMIT 1");
        $stmt->bind_param("s", $codigo);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!empty($r['id_cliente'])) {
            return ['tipo' => $tabla, 'codigo' => (string) $r['codigo'], 'id_cliente' => intval($r['id_cliente'])];
        }
    }
    return null;
}

function resolverCliente(mysqli $conn, string $ov_ot): ?int {
    return resolverOvOt($conn, $ov_ot)['id_cliente'] ?? null;
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
// Grano: una fila por fila de km_calculados (= un check-in: cada check-in con coordenadas
// deja la suya, aunque sea el placeholder 'sin_actividad'), enriquecida con dimensiones de
// vehículo, usuario y cliente. `km` es el KM Mess del check-in (sqlKmMessCheckin) desde el
// 04/11/2026; antes era la ruta INEGI. Power BI se
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
 * KM Mess y KM personales (regla del negocio, 04/11/2026: "todo lo registrado en QRide es
 * trabajo Mess; los días sin check-in son uso personal").
 *
 * km_tramo de un check-in = lo que avanzó el odómetro desde el check-in anterior del mismo
 * vehículo (km_actual - km_actual anterior). Se descarta (0) si es negativo o pasa de
 * KM_VIAJE_MAX (reseteos de tablero, dedazos); es NULL en la primera lectura del vehículo.
 * Solo cuentan los INICIO: las "Salidas" (FINALIZACION) son copias automáticas del check-in
 * siguiente (misma hora y mismo km en 1,163 de 1,173 desde septiembre).
 *
 *   - Si entre el check-in anterior y este hubo al menos un día completo sin check-ins
 *     (DATEDIFF >= 2: fin de semana, días sin usar QRide), esos km son PERSONALES.
 *   - Si no (mismo día o día siguiente), son KM MESS, traiga lo que traiga en `ot`.
 *
 * Ej. UNF455E: check-in el viernes 30/10 en 351,345 y el siguiente el martes 03/11 en
 * 351,574: los 229 km son personales (31/10, 01/11 y 02/11 sin check-in). El del jueves
 * 29/10 al viernes 30/10 (410 km) es Mess. No se puede partir un hueco: el viernes
 * después del check-in y el martes antes del siguiente también caen en personal.
 *
 * KM odómetro = KM Mess + KM personales, siempre. La ruta INEGI (km_calculados.long_km)
 * ya no entra en el cálculo; queda como referencia del destino.
 */

// Por cada check-in con lectura de km: km_tramo desde el check-in anterior y cuántos días
// hay entre las dos fechas (1 = día siguiente).
function sqlTramoOdometro(): string {
    $max = KM_VIAJE_MAX;
    return "(SELECT id_actividad, id_vehiculo, fecha_actividad,
                    CASE WHEN km_ant IS NULL THEN NULL
                         WHEN km_actual - km_ant BETWEEN 0 AND $max THEN km_actual - km_ant
                         ELSE 0 END AS km_tramo,
                    DATEDIFF(DATE(fecha_actividad), DATE(fecha_ant)) AS dias
             FROM (SELECT id_actividad, id_vehiculo, fecha_actividad, km_actual,
                          LAG(km_actual)       OVER w AS km_ant,
                          LAG(fecha_actividad) OVER w AS fecha_ant
                   FROM actividad_vehiculo
                   WHERE km_actual > 0 AND tipo_actividad = 'INICIO'
                   WINDOW w AS (PARTITION BY id_vehiculo ORDER BY fecha_actividad, id_actividad)) d)";
}

// Por cada check-in (INICIO) con algo en `ot`: 'OT', 'OV' o NULL según el catálogo, con la
// misma normalización que resolverOvOt() (texto exacto, OT dentro del texto, OV con
// prefijo). A diferencia de resolverOvOt(), basta con que exista en el catálogo aunque no
// tenga cliente. Sirve para contar OT / OV / Otros y para reporte_km.tipo_ref; no cambia los km.
// COLLATE: actividad_vehiculo.ot es utf8mb4_general_ci y OT/OV utf8mb4_0900_ai_ci.
function sqlCheckinOvOt(): string {
    $c = 'COLLATE utf8mb4_0900_ai_ci';
    $reOt = RE_OT; $marca = RE_OV_MARCA; $num = RE_OV_NUM; $sep = RE_OV_SEP;
    return "(SELECT av.id_actividad,
                    CASE WHEN ot1.OT IS NOT NULL OR ot2.OT IS NOT NULL THEN 'OT'
                         WHEN ov1.OV IS NOT NULL OR ov2.OV IS NOT NULL THEN 'OV' END AS tipo
             FROM actividad_vehiculo av
             LEFT JOIN OT ot1 ON ot1.OT = TRIM(av.ot) $c
             LEFT JOIN OV ov1 ON ov1.OV = TRIM(av.ot) $c
             LEFT JOIN OT ot2 ON ot2.OT = REGEXP_SUBSTR(av.ot, '$reOt') $c
             LEFT JOIN OV ov2 ON av.ot REGEXP '$marca'
                             AND ov2.OV = REGEXP_REPLACE(REGEXP_SUBSTR(av.ot, '$num'), '$sep', '-') $c
             WHERE av.tipo_actividad = 'INICIO' AND av.ot IS NOT NULL AND av.ot <> '')";
}

// KM Mess y personales por check-in. Única definición: la usan reporte_km,
// reporte_km_vehiculo y el panel "Uso del vehículo" (acciones_mapa.php).
// Columnas: id_actividad, id_vehiculo, fecha_actividad, km_tramo, dias, tipo_ref, km_mess, km_personal.
function sqlKmMessCheckin(): string {
    return "(SELECT tr.id_actividad, tr.id_vehiculo, tr.fecha_actividad, tr.km_tramo, tr.dias,
                    r.tipo AS tipo_ref,
                    CASE WHEN tr.km_tramo IS NULL OR tr.dias >= 2 THEN 0 ELSE tr.km_tramo END AS km_mess,
                    CASE WHEN tr.km_tramo IS NOT NULL AND tr.dias >= 2 THEN tr.km_tramo ELSE 0 END AS km_personal
             FROM " . sqlTramoOdometro() . " tr
             LEFT JOIN " . sqlCheckinOvOt() . " r ON r.id_actividad = tr.id_actividad)";
}

// SELECT denormalizado reutilizado por el upsert y el rebuild. $where es código
// fijo interno ('WHERE k.id = ?'), nunca datos del usuario.
//
// usuarios.id_usuario NO es único (hay duplicados y 0/NULL): se empareja con la
// fila de mayor id por id_usuario para no multiplicar el grano del fact table. Va como
// tabla derivada y no como `u.id = (SELECT MAX(u2.id) ...)` en el ON, que se reevalúa por
// cada pareja de filas (en acciones_mapa.php pasó de 17.7 s a 2.2 s).
//
// `km` es el KM Mess del check-in (sqlKmMessCheckin), no la ruta calculada; km_valido = km > 0.
// COLLATE en OT/OV: en producción km_calculados.ov_ot quedó utf8mb4_general_ci (respaldo del
// 04/11/2026) y OT/OV son utf8mb4_0900_ai_ci; sin él el INSERT falla y, como refrescarReporteKm
// no lanza, reporte_km dejaba de actualizarse sin ningún aviso.
// tipo_ref sale de lo capturado en el check-in (sqlCheckinOvOt) y, si no, de km_calculados.
function sqlSelectReporteKm(string $where = ''): string {
    return "SELECT
            k.id, k.id_actividad,
            COALESCE(av.fecha_actividad, k.fecha),
            YEAR(COALESCE(av.fecha_actividad, k.fecha)),
            MONTH(COALESCE(av.fecha_actividad, k.fecha)),
            av.id_vehiculo, inv.placa, inv.marca, inv.modelo, inv.anio, inv.area,
            k.id_usuario, u.nombre, u.noEmpleado, u.region, u.departamento,
            k.ov_ot,
            CASE WHEN m.tipo_ref IS NOT NULL THEN m.tipo_ref
                 WHEN ot.OT IS NOT NULL THEN 'OT'
                 WHEN ov.OV IS NOT NULL THEN 'OV'
                 WHEN k.estatus = 'sin_actividad' OR k.ov_ot IS NULL OR k.ov_ot = '' THEN 'SIN'
                 ELSE 'OTROS' END,
            k.id_cliente, c.CLIENTE, c.ESTADO, c.MUNICIPIO,
            COALESCE(m.km_mess, 0), k.tiempo_min, av.costoOv, k.metodo, k.estatus,
            CASE WHEN COALESCE(m.km_mess, 0) > 0 THEN 1 ELSE 0 END
        FROM km_calculados k
        LEFT JOIN actividad_vehiculo av ON av.id_actividad = k.id_actividad
        LEFT JOIN " . sqlKmMessCheckin() . " m ON m.id_actividad = k.id_actividad
        LEFT JOIN inventario inv        ON inv.id_vehiculo = av.id_vehiculo
        LEFT JOIN (SELECT id_usuario, MAX(id) AS id FROM usuarios GROUP BY id_usuario) um ON um.id_usuario = k.id_usuario
        LEFT JOIN usuarios u            ON u.id = um.id
        LEFT JOIN clientes c            ON c.IDCLTE = k.id_cliente
        LEFT JOIN OT ot                 ON ot.OT = k.ov_ot COLLATE utf8mb4_0900_ai_ci
        LEFT JOIN OV ov                 ON ov.OV = k.ov_ot COLLATE utf8mb4_0900_ai_ci
        $where";
}

// Refresca reporte_km. Con $idVehiculo: reemplaza los check-ins de ese vehículo (hook por
// check-in; ver refrescarKpiKm). Con $idKm: solo esa
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
// Todo sale de sqlKmMessCheckin (un renglón por check-in), así que cuadra con el panel:
//   - km_registrado:         km del ODÓMETRO = Σ km_tramo de los check-ins de la semana
//                            (lo avanzado desde el check-in anterior).
//   - segmentos_odo:         check-ins de la semana con check-in anterior.
//   - km_trabajo_calculado:  KM Mess (tramos sin un día completo sin check-ins en medio).
//   - actividades_trabajo:   check-ins de la semana con KM Mess > 0.
//   - porcentaje_uso_trabajo = km_trabajo_calculado / km_registrado * 100. Lo que falta
//                            para 100 son los km personales (días sin check-in).
//
// Cada tramo va a la semana del check-in que lo cierra. Incluye los check-ins sin fila en
// km_calculados (sin coordenadas), que no aparecen en reporte_km.
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

// Agregación vehículo×semana (reconstrucción total).
function sqlAggReporteKmVehiculo(): string {
    return "
        SELECT m.id_vehiculo,
               FLOOR(YEARWEEK(m.fecha_actividad, 3) / 100) AS anio,
               MOD(YEARWEEK(m.fecha_actividad, 3), 100)    AS semana,
               MIN(DATE_SUB(DATE(m.fecha_actividad), INTERVAL WEEKDAY(m.fecha_actividad) DAY)) AS semana_inicio,
               MAX(i.placa), MAX(i.marca), MAX(i.modelo), MAX(i.area),
               SUM(CASE WHEN m.km_tramo IS NOT NULL THEN 1 ELSE 0 END) AS segmentos_odo,
               COALESCE(SUM(m.km_tramo), 0)                           AS km_registrado,
               SUM(CASE WHEN m.km_mess > 0 THEN 1 ELSE 0 END)         AS actividades_trabajo,
               SUM(m.km_mess)                                         AS km_trabajo_calculado,
               ROUND(SUM(m.km_mess) / NULLIF(SUM(m.km_tramo), 0) * 100, 2) AS porcentaje_uso_trabajo
        FROM " . sqlKmMessCheckin() . " m
        LEFT JOIN inventario i ON i.id_vehiculo = m.id_vehiculo
        GROUP BY m.id_vehiculo, anio, semana";
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
// El km de un check-in depende del check-in anterior del vehículo (sqlKmMessCheckin). Se
// recalculan todos los check-ins del vehículo y no solo esta fila: la reconciliación llena
// check-ins viejos y un km corregido cambia también el tramo del check-in siguiente.
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
 * check-in. Los check-ins de OV/OT ya se calculan en vivo; aquí solo se recalculan los que
 * quedaron sin km porque la OT/OV no se encontró al hacer el check-in (entró tarde al
 * catálogo o venía escrita como 'MESS-OV-…').
 *
 * Por lotes: cada check-in que empareja hace varias llamadas a INEGI, y en el
 * hosting una sola petición que tarde minutos se corta (ERR_CONNECTION_RESET).
 * Con $limiteSeg > 0 se detiene al agotar el tiempo y devuelve 'terminado' => false
 * y los cursores (último id_actividad revisado de cada fase) para que la siguiente
 * llamada continúe donde quedó. Sin límite (CLI) procesa todo de una vez.
 *
 * @return array contadores: revisados, calculados, sin_actividad, sin_destino, ovot,
 *               ovot_recalculados, regresos, terminado, desde_checkin, desde_regreso
 */
function reconciliarKmPendientes(mysqli $conn, int $diasAtras = 30, int $desdeCheckin = 0, int $desdeRegreso = 0, float $limiteSeg = 0): array {
    $res = ['revisados' => 0, 'calculados' => 0, 'sin_actividad' => 0, 'sin_destino' => 0, 'ovot' => 0,
            'ovot_recalculados' => 0, 'regresos' => 0, 'terminado' => false, 'desde_checkin' => $desdeCheckin, 'desde_regreso' => $desdeRegreso];
    $vence = $limiteSeg > 0 ? microtime(true) + $limiteSeg : INF;
    $procesados = 0;   // al menos uno por llamada, para que siempre avance
    $agotado = function () use (&$procesados, $vence) { return $procesados > 0 && microtime(true) > $vence; };

    // sin_km: el check-in no tiene km real todavía (sin fila o solo el placeholder 'sin_actividad').
    $stmt = $conn->prepare("SELECT av.id_actividad, av.id_usuario, av.coordenadas, av.ot, av.fecha_actividad,
               NOT EXISTS (SELECT 1 FROM km_calculados k
                           WHERE k.id_actividad = av.id_actividad AND k.estatus <> 'sin_actividad') AS sin_km
        FROM actividad_vehiculo av
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

        // OV/OT: si ya tiene su km (se calculó en vivo), se salta. Si quedó solo el placeholder
        // 'sin_actividad' porque la OT entró al catálogo después del check-in, o porque la OV
        // venía como 'MESS-OV-…' antes de normalizarla (04/11/2026), se calcula ahora y
        // registrarKm() rellena esa misma fila.
        $ot = trim((string) $row['ot']);
        if ($ot !== '' && resolverOvOt($conn, $ot) !== null) {
            if (!$row['sin_km']) { $res['ovot']++; continue; }
            procesarKmCheckin($conn, $ot, $oLat, $oLng, intval($row['id_usuario']) ?: null,
                (int) $row['id_actividad'], (string) $row['fecha_actividad']);
            $res['ovot_recalculados']++;
            continue;
        }

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

    $ovot       = $ov_ot !== '' ? resolverOvOt($conn, $ov_ot) : null;
    $id_cliente = $ovot['id_cliente'] ?? null;
    $esOvOt     = $ovot !== null;            // el ot capturado SÍ es un OV/OT válido
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

    // En km_calculados.ov_ot va el código OV/OT tal como está en el catálogo (no el texto
    // capturado, que puede traer 'MESS-OV-' o texto pegado), o el tipo de actividad si es OTROS.
    $ovOtKm = $esOvOt ? $ovot['codigo'] : (string) $otros['actividad'];

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
