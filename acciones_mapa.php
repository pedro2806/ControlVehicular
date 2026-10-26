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

/**
 * Opciones de los filtros: quién ha hecho check-in y los vehículos activos.
 */
if ($accion === 'filtros') {
    $usuarios = [];
    $res = $conn->query(
        "SELECT t.id_usuario, $sqlNombre AS nombre
         FROM (SELECT DISTINCT id_usuario FROM actividad_vehiculo
               WHERE tipo_actividad = 'INICIO' AND id_usuario > 0) t
         LEFT JOIN usuarios u ON u.id = (SELECT MAX(u2.id) FROM usuarios u2 WHERE u2.id_usuario = t.id_usuario)
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
    $desde = $_POST['desde'] ?? '';
    $hasta = $_POST['hasta'] ?? '';
    $idUsr = intval($_POST['id_usuario'] ?? 0);
    $idVeh = intval($_POST['id_vehiculo'] ?? 0);

    // Por defecto los últimos 30 días, igual que la vista de Check-ins.
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) $desde = date('Y-m-d', strtotime('-30 days'));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) $hasta = date('Y-m-d');
    if ($desde > $hasta) { $tmp = $desde; $desde = $hasta; $hasta = $tmp; }

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
         LEFT JOIN usuarios u ON u.id = (SELECT MAX(u2.id) FROM usuarios u2 WHERE u2.id_usuario = av.id_usuario)
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

echo json_encode(['status' => 'error', 'message' => 'Acción no reconocida.']);
