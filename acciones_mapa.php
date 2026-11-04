<?php
/**
 * Endpoint del Mapa de ubicaciones (mapa_ubicaciones.php).
 *
 * Reemplaza a obtener_ubicaciones.php y obtener_vehiculos_usuario.php, que respondían a
 * cualquiera con la URL (incluso sin sesión) con nombres, placas y coordenadas de los
 * empleados, y cuyo filtro de fechas no aplicaba a los check-ins: un solo día devolvía
 * todo el historial.
 *
 * Solo lo usan las personas con el acceso especial verMapaGPS (retro 26/10/2026: 19, 183,
 * 191, 276 y 523). Se valida aquí en cada petición, nunca con la cookie `rol`.
 */
include 'includes/api_bootstrap.php';

if (!tieneAccesoEspecial($conn, $_COOKIE['noEmpleado'] ?? 0, 'verMapaGPS')) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'No tienes acceso al mapa de ubicaciones.']);
    exit;
}

$accion = $_POST['accion'] ?? '';

// Nombre de quien hizo el registro: el de RRHH y, si no hay, el de CV. El JOIN a usuarios
// usa MAX(id) porque usuarios.id_usuario no es único (mismo criterio que consultarCheckins
// en acciones_kilometraje.php); un JOIN directo multiplicaría filas.
// RRHH guarda nombres con espacios dobles ('Victor  Acosta'): se normalizan para que el
// filtro, el popup y el Excel no los muestren así.
function nombreLimpio($nombre, $idUsuario) {
    $n = trim(preg_replace('/\s+/u', ' ', (string) $nombre));
    return $n !== '' ? $n : 'Usuario #' . $idUsuario;
}

$sqlNombre = "IFNULL(NULLIF(TRIM(CONCAT(IFNULL(rrhh.nombres,''),' ',IFNULL(rrhh.apellidos,''))),''), u.nombre)";

// La fila de usuarios con mayor id por id_usuario, como tabla derivada para hacer
// `LEFT JOIN $sqlUltimoUsuario um ON um.id_usuario = x.id_usuario LEFT JOIN usuarios u ON u.id = um.id`.
// Mismo resultado que `u.id = (SELECT MAX(u2.id) ... WHERE u2.id_usuario = x.id_usuario)`,
// pero esa subconsulta en el ON se reevalúa por cada pareja de filas: con los ~100
// vehículos tardaba 2.5 s, así tarda milisegundos.
$sqlUltimoUsuario = "(SELECT id_usuario, MAX(id) AS id FROM usuarios GROUP BY id_usuario)";

/**
 * Rango de fechas de los filtros. Sin fechas, el mes en curso (del día 1 a hoy): lo pidieron
 * así el 03/11/2026, porque el panel de uso se lee por mes. Antes eran los últimos 30 días.
 * @return array{0:string,1:string} [desde, hasta] en 'Y-m-d'
 */
function rangoFechas($desde, $hasta) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) $desde = date('Y-m-01');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) $hasta = date('Y-m-d');
    return $desde > $hasta ? [$hasta, $desde] : [$desde, $hasta];
}

/**
 * Opciones de los filtros: quién ha hecho check-in y los vehículos activos.
 */
if ($accion === 'filtros') {
    $usuarios = [];
    $res = $conn->query(
        "SELECT t.id_usuario, $sqlNombre AS nombre
         FROM (SELECT DISTINCT id_usuario FROM actividad_vehiculo
               WHERE tipo_actividad = 'INICIO' AND id_usuario > 0) t
         LEFT JOIN $sqlUltimoUsuario um ON um.id_usuario = t.id_usuario
         LEFT JOIN usuarios u ON u.id = um.id
         LEFT JOIN mess_rrhh.usuarios rrhh ON rrhh.noEmpleado = u.noEmpleado
         ORDER BY nombre"
    );
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $usuarios[] = [
                'id_usuario' => intval($r['id_usuario']),
                'nombre'     => nombreLimpio($r['nombre'], $r['id_usuario'])
            ];
        }
    }

    // Solo activos: los dados de baja solo se consultan en la pantalla de Vehículos.
    $vehiculos = [];
    $res = $conn->query(
        "SELECT id_vehiculo, placa, marca, modelo, TRIM(usuario) AS usuario
         FROM inventario WHERE estatus = 'Activo' ORDER BY placa"
    );
    if ($res) { while ($r = $res->fetch_assoc()) $vehiculos[] = $r; }

    echo json_encode(['status' => 'success', 'usuarios' => $usuarios, 'vehiculos' => $vehiculos]);
    exit;
}

/**
 * Check-ins con ubicación según los filtros, con la última carga de gasolina anterior.
 *
 * Solo INICIO: la "Salida" automática que se inserta al volver a hacer check-in repite el
 * mismo punto y la misma hora, y duplicaría los marcadores.
 *
 * "Gasolina" es la última carga del vehículo ANTES del check-in: el check-in por QR no
 * captura el nivel del tanque (0 de 458 check-ins en 60 días lo traen). Las cargas
 * anteriores al paso de fecha_carga a DATETIME quedaron a las 00:00, así que cuentan como
 * anteriores a cualquier check-in de ese mismo día.
 */
if ($accion === 'ubicaciones') {
    list($desde, $hasta) = rangoFechas($_POST['desde'] ?? '', $_POST['hasta'] ?? '');
    $idUsr = intval($_POST['id_usuario'] ?? 0);
    $idVeh = intval($_POST['id_vehiculo'] ?? 0);

    // Rango por comparación directa (y no DATE(fecha_actividad)) para poder usar índice.
    $where  = "av.tipo_actividad = 'INICIO'
               AND av.fecha_actividad >= ? AND av.fecha_actividad < DATE_ADD(?, INTERVAL 1 DAY)";
    $tipos  = "ss";
    $params = [$desde, $hasta];
    if ($idUsr > 0) { $where .= " AND av.id_usuario = ?";  $tipos .= "i"; $params[] = $idUsr; }
    if ($idVeh > 0) { $where .= " AND av.id_vehiculo = ?"; $tipos .= "i"; $params[] = $idVeh; }

    $stmt = $conn->prepare(
        "SELECT av.id_actividad, av.id_vehiculo, av.id_usuario, av.fecha_actividad,
                av.km_actual, av.ot, av.coordenadas,
                inv.placa, inv.marca, inv.modelo,
                $sqlNombre AS usuario,
                cg.fecha_carga AS carga_fecha, cg.pagos AS carga_pagos, cg.saldo AS carga_saldo
         FROM actividad_vehiculo av
         INNER JOIN inventario inv ON inv.id_vehiculo = av.id_vehiculo AND inv.estatus = 'Activo'
         LEFT JOIN $sqlUltimoUsuario um ON um.id_usuario = av.id_usuario
         LEFT JOIN usuarios u ON u.id = um.id
         LEFT JOIN mess_rrhh.usuarios rrhh ON rrhh.noEmpleado = u.noEmpleado
         LEFT JOIN carga_gasolina cg ON cg.id = (
             SELECT cg2.id FROM carga_gasolina cg2
             WHERE cg2.id_vehiculo = av.id_vehiculo AND cg2.fecha_carga <= av.fecha_actividad
             ORDER BY cg2.fecha_carga DESC, cg2.id DESC LIMIT 1
         )
         WHERE $where
         ORDER BY av.fecha_actividad DESC, av.id_actividad DESC"
    );
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Error al preparar la consulta.']);
        exit;
    }
    $stmt->bind_param($tipos, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();

    $ubicaciones = [];
    $sinCoordenadas = 0;
    while ($r = $res->fetch_assoc()) {
        // Las coordenadas se guardan como texto "lat, lng". Se descartan las que no se
        // pueden ubicar (vacías, no numéricas, fuera de rango o 0,0) en vez de mandar
        // marcadores en medio del océano.
        $p = array_map('trim', explode(',', (string) $r['coordenadas']));
        $lat = isset($p[0]) && is_numeric($p[0]) ? (float) $p[0] : null;
        $lng = isset($p[1]) && is_numeric($p[1]) ? (float) $p[1] : null;
        if ($lat === null || $lng === null || abs($lat) > 90 || abs($lng) > 180 || ($lat == 0 && $lng == 0)) {
            $sinCoordenadas++;
            continue;
        }

        $ubicaciones[] = [
            'id'          => intval($r['id_actividad']),
            'fecha'       => $r['fecha_actividad'],
            'usuario'     => nombreLimpio($r['usuario'], $r['id_usuario']),
            'id_vehiculo' => intval($r['id_vehiculo']),
            'placa'       => $r['placa'],
            'marca'       => $r['marca'],
            'modelo'      => $r['modelo'],
            'km'          => intval($r['km_actual']),
            'ot'          => $r['ot'],
            'lat'         => $lat,
            'lng'         => $lng,
            // null cuando el vehículo no tiene ninguna carga anterior al check-in
            'carga'       => $r['carga_fecha'] === null ? null : [
                'fecha' => $r['carga_fecha'],
                'pagos' => (float) $r['carga_pagos'],
                'saldo' => (float) $r['carga_saldo']
            ]
        ];
    }
    $stmt->close();

    echo json_encode([
        'status'          => 'success',
        'desde'           => $desde,
        'hasta'           => $hasta,
        'ubicaciones'     => $ubicaciones,
        'sin_coordenadas' => $sinCoordenadas
    ]);
    exit;
}

/**
 * Panel "Uso del vehículo" (retro 03/11/2026, img/Reporte Integral QRide.pptx).
 *
 * Por vehículo y en el rango de fechas, sin importar quién manejó. Mismo criterio que el
 * KPI de Power BI (sqlAggReporteKmVehiculo en calcular_ruta.php) para que los números
 * cuadren con el reporte:
 *   - km_odometro: suma de las diferencias entre lecturas consecutivas de
 *     actividad_vehiculo, cada una en la fecha de su lectura inicial; se descartan las
 *     negativas y las de más de KM_VIAJE_MAX (reseteos de tablero, dedazos).
 *   - km_mess: km calculados (INEGI) de los check-ins del rango con estatus 'ok', cada uno
 *     topado por lo que avanzó el odómetro hasta el check-in siguiente (03/11/2026; ver
 *     sqlTramoOdometro). Incluye "Volver a la sede", que cuenta como trabajo desde el 26/10.
 *   - pct_uso: km_mess / km_odometro; null sin odómetro (semáforo gris).
 *   - rendimiento: $ de gasolina / km_mess (lo pidió así la retro: costo por km de trabajo).
 *   - ots / ovs / otros: check-ins del rango según si su `ot` es una OT, una OV o ninguna.
 *
 * Vehículos: todos los activos (la barra del panel los muestra todos). Primero los que se
 * movieron en el rango, de más a menos km; después los demás, por placa.
 */
if ($accion === 'resumen') {
    require_once __DIR__ . '/calcular_ruta.php';   // KM_VIAJE_MAX; incluirlo no dispara su endpoint

    list($desde, $hasta) = rangoFechas($_POST['desde'] ?? '', $_POST['hasta'] ?? '');

    // Una consulta por métrica agrupada por vehículo: con toda la flota (~100 vehículos),
    // ir uno por uno serían cientos de consultas en cada búsqueda.
    $porVehiculo = function ($sql, $tipos = '', ...$params) use ($conn) {
        $stmt = $conn->prepare($sql);
        if ($tipos !== '') $stmt->bind_param($tipos, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        $filas = [];
        while ($r = $res->fetch_assoc()) $filas[intval($r['id_vehiculo'])] = $r;
        $stmt->close();
        return $filas;
    };

    // Datos fijos del vehículo. Asignado: el nombre de id_us_asignado (MAX(id) porque
    // usuarios.id_usuario no es único) y, si no resuelve, el texto de inventario.asignado.
    // Región: la del asignado en RRHH; si no hay, el área del vehículo.
    $info = $porVehiculo(
        "SELECT inv.id_vehiculo, inv.placa, TRIM(inv.marca) AS marca, TRIM(inv.modelo) AS modelo, inv.anio, inv.foto_general,
                TRIM(inv.asignado) AS asignado_txt, TRIM(inv.area) AS area,
                $sqlNombre AS asignado, reg.region
         FROM inventario inv
         LEFT JOIN $sqlUltimoUsuario um ON um.id_usuario = inv.id_us_asignado AND inv.id_us_asignado > 0
         LEFT JOIN usuarios u ON u.id = um.id
         LEFT JOIN mess_rrhh.usuarios rrhh ON rrhh.noEmpleado = u.noEmpleado
         LEFT JOIN mess_rrhh.region reg ON reg.id = u.region
         WHERE inv.estatus = 'Activo'"
    );

    // El LEAD corre sobre todo el historial del vehículo: la lectura siguiente a la última
    // del rango puede caer después de `hasta`, y ese tramo es del rango.
    $kmMax = KM_VIAJE_MAX;
    $odo = $porVehiculo(
        "SELECT id_vehiculo, SUM(delta) AS km
         FROM (SELECT id_vehiculo, fecha_actividad,
                      LEAD(km_actual) OVER (PARTITION BY id_vehiculo ORDER BY fecha_actividad, id_actividad) - km_actual AS delta
               FROM actividad_vehiculo
               WHERE km_actual > 0) d
         WHERE fecha_actividad >= ? AND fecha_actividad < DATE_ADD(?, INTERVAL 1 DAY)
           AND delta BETWEEN 0 AND $kmMax
         GROUP BY id_vehiculo",
        "ss", $desde, $hasta
    );
    // KM Mess topados por el odómetro, como en reporte_km (SQL_KM_TOPADO en calcular_ruta.php).
    // Además se separa lo que se descontó: `pendiente` es la ruta del último check-in, que
    // cuenta 0 hasta que llegue la lectura siguiente; `recortado` es ruta que el odómetro no
    // registró (destino mal ubicado).
    $mess = $porVehiculo(
        "SELECT av.id_vehiculo,
                SUM(" . SQL_KM_TOPADO . ") AS km,
                SUM(k.long_km) AS km_ruta,
                SUM(CASE WHEN tr.id_actividad IS NOT NULL AND tr.km_tramo IS NULL THEN k.long_km ELSE 0 END) AS km_pendiente
         FROM km_calculados k
         INNER JOIN actividad_vehiculo av ON av.id_actividad = k.id_actividad
         LEFT JOIN " . sqlTramoOdometro() . " tr ON tr.id_actividad = k.id_actividad
         WHERE av.fecha_actividad >= ? AND av.fecha_actividad < DATE_ADD(?, INTERVAL 1 DAY)
           AND k.estatus = 'ok' AND k.long_km IS NOT NULL
         GROUP BY av.id_vehiculo",
        "ss", $desde, $hasta
    );
    $gas = $porVehiculo(
        "SELECT id_vehiculo, SUM(pagos) AS total
         FROM carga_gasolina
         WHERE fecha_carga >= ? AND fecha_carga < DATE_ADD(?, INTERVAL 1 DAY)
         GROUP BY id_vehiculo",
        "ss", $desde, $hasta
    );
    // COLLATE: actividad_vehiculo.ot es utf8mb4_general_ci y OT/OV son utf8mb4_0900_ai_ci;
    // sin él MySQL lanza "Illegal mix of collations". OT.OT y OV.OV son UNIQUE: el JOIN no
    // multiplica filas.
    $tipos = $porVehiculo(
        "SELECT av.id_vehiculo,
                SUM(ot_.OT IS NOT NULL) AS ots,
                SUM(ot_.OT IS NULL AND ov_.OV IS NOT NULL) AS ovs,
                SUM(ot_.OT IS NULL AND ov_.OV IS NULL) AS otros
         FROM actividad_vehiculo av
         LEFT JOIN OT ot_ ON ot_.OT = TRIM(av.ot) COLLATE utf8mb4_0900_ai_ci
         LEFT JOIN OV ov_ ON ov_.OV = TRIM(av.ot) COLLATE utf8mb4_0900_ai_ci
         WHERE av.tipo_actividad = 'INICIO'
           AND av.fecha_actividad >= ? AND av.fecha_actividad < DATE_ADD(?, INTERVAL 1 DAY)
         GROUP BY av.id_vehiculo",
        "ss", $desde, $hasta
    );

    $vehiculos = [];
    foreach ($info as $idVeh => $v) {
        $kmOdo  = (float) ($odo[$idVeh]['km'] ?? 0);
        $kmMess = round((float) ($mess[$idVeh]['km'] ?? 0), 1);
        $kmPendiente = round((float) ($mess[$idVeh]['km_pendiente'] ?? 0), 1);
        $kmRecortado = max(0, round((float) ($mess[$idVeh]['km_ruta'] ?? 0) - $kmMess - $kmPendiente, 1));
        $gasto  = (float) ($gas[$idVeh]['total'] ?? 0);

        $asignado = trim(preg_replace('/\s+/u', ' ', (string) $v['asignado']));
        if ($asignado === '') $asignado = (string) $v['asignado_txt'];

        $vehiculos[] = [
            'id_vehiculo'      => $idVeh,
            'placa'            => $v['placa'],
            'marca'            => $v['marca'],
            'modelo'           => $v['modelo'],
            'anio'             => $v['anio'] ? intval($v['anio']) : null,
            'foto'             => $v['foto_general'] ?: null,
            'asignado'         => $asignado !== '' ? $asignado : null,
            'region'           => $v['region'] ?: ($v['area'] ?: null),
            'km_odometro'      => $kmOdo,
            'km_mess'          => $kmMess,
            'km_no_reportados' => max(0, round($kmOdo - $kmMess, 1)),
            // Con el tope por trayecto km_mess ya no pasa del odómetro; el min() queda por
            // si el redondeo deja 100.x.
            'pct_uso'          => $kmOdo > 0 ? min(100, round($kmMess / $kmOdo * 100)) : null,
            // La vista avisa de ambos: recortado apunta a un destino mal ubicado (p. ej. la
            // OT ZEISSI26-06I-769, cliente MESS Querétaro usada en Saltillo).
            'km_recortado'     => $kmRecortado,
            'km_pendiente'     => $kmPendiente,
            'gasto_gas'        => $gasto,
            // null sin cargas o sin km Mess: un $0.00/km parecería un vehículo que no gasta.
            'rendimiento'      => ($kmMess > 0 && $gasto > 0) ? round($gasto / $kmMess, 2) : null,
            'ots'              => intval($tipos[$idVeh]['ots'] ?? 0),
            'ovs'              => intval($tipos[$idVeh]['ovs'] ?? 0),
            'otros'            => intval($tipos[$idVeh]['otros'] ?? 0)
        ];
    }

    usort($vehiculos, function ($a, $b) {
        return [$b['km_odometro'], $a['placa']] <=> [$a['km_odometro'], $b['placa']];
    });

    echo json_encode(['status' => 'success', 'desde' => $desde, 'hasta' => $hasta, 'vehiculos' => $vehiculos]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Acción no reconocida.']);
