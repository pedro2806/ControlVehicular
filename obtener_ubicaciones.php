<?php
header('Content-Type: application/json');
include 'conn.php';

$usuario = $_GET['usuario'] ?? '';
$vehiculo = $_GET['vehiculo'] ?? '';
$con_vehiculo = $_GET['con_vehiculo'] ?? ''; // filtro: 1=con vehículo, 0=sin vehículo, ''=todos
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';

// Escapar valores para seguridad
$usuario_esc = $conn->real_escape_string($usuario);
$vehiculo_esc = $conn->real_escape_string($vehiculo);
$con_vehiculo_esc = $conn->real_escape_string($con_vehiculo);
$fecha_inicio_esc = $conn->real_escape_string($fecha_inicio);
$fecha_fin_esc = $conn->real_escape_string($fecha_fin);

$filtros = [];
if ($fecha_inicio_esc) $filtros[] = "gt.fecha >= '$fecha_inicio_esc 00:00:00'";
if ($fecha_fin_esc) $filtros[] = "gt.fecha <= '$fecha_fin_esc 23:59:59'";
$whereFiltros = count($filtros) > 0 ? ' AND ' . implode(' AND ', $filtros) : '';

$whereVehiculo = '';
if ($vehiculo_esc !== '') {
    $whereVehiculo = " AND av1.id_vehiculo = '$vehiculo_esc' ";
}

$data = [
    'sinVehiculo' => ['icon' => 'persona', 'color' => '#0077cc', 'puntos' => []],
];

$colores = ['#cc3995', '#ff5733', '#33cc8c', '#3399ff', '#9933ff', '#ff9933'];
$colorIndex = 0;

// 1. Con vehículo agrupando por INICIO - FINALIZACION
if ($con_vehiculo_esc === '' || $con_vehiculo_esc === '1') {
    $queryAgrupados = "
    SELECT 
        av1.fecha_actividad AS inicio,
        (
            SELECT MIN(av2.fecha_actividad)
            FROM actividad_vehiculo av2
            WHERE av2.id_usuario = av1.id_usuario 
              AND av2.tipo_actividad = 'FINALIZACION' 
              AND av2.fecha_actividad > av1.fecha_actividad
        ) AS fin,
        av1.id_usuario, av1.id_vehiculo,
        u.nombre, inv.placa, inv.marca, inv.modelo,
        av1.coordenadas AS coordenadas_inicio,
        (
            SELECT av3.coordenadas
            FROM actividad_vehiculo av3
            WHERE av3.id_usuario = av1.id_usuario 
              AND av3.tipo_actividad = 'FINALIZACION' 
              AND av3.fecha_actividad > av1.fecha_actividad
            ORDER BY av3.fecha_actividad ASC
            LIMIT 1
        ) AS coordenadas_fin
    FROM actividad_vehiculo av1
    JOIN usuarios u ON u.id_usuario = av1.id_usuario
    JOIN inventario inv ON inv.id_vehiculo = av1.id_vehiculo
    WHERE av1.tipo_actividad = 'INICIO'
        " . ($usuario_esc ? " AND av1.id_usuario = '$usuario_esc' " : '') . "
        $whereVehiculo
    ORDER BY av1.fecha_actividad
    ";

    $result = $conn->query($queryAgrupados);

    while ($row = $result->fetch_assoc()) {
        $color = $colores[$colorIndex % count($colores)];
        $colorIndex++;

        $grupoKey = $row['id_usuario'] . '-' . $row['inicio'];

        $data[$grupoKey] = [
            'icon' => 'vehiculo',
            'color' => $color,
            'puntos' => []
        ];

        // Validar coordenadas inicio
        if (isset($row['coordenadas_inicio']) && strpos($row['coordenadas_inicio'], ',') !== false) {
            $inicioCoords = explode(',', $row['coordenadas_inicio']);
            $latInicio = floatval($inicioCoords[0]);
            $lonInicio = floatval($inicioCoords[1]);
        } else {
            $latInicio = null;
            $lonInicio = null;
        }

        // Validar coordenadas fin
        if (isset($row['coordenadas_fin']) && strpos($row['coordenadas_fin'], ',') !== false) {
            $finCoords = explode(',', $row['coordenadas_fin']);
            $latFin = floatval($finCoords[0]);
            $lonFin = floatval($finCoords[1]);
        } else {
            $latFin = null;
            $lonFin = null;
        }

        if ($latInicio !== null && $lonInicio !== null) {
            $data[$grupoKey]['puntos'][] = [
                'lat' => $latInicio,
                'lon' => $lonInicio,
                'nombre' => $row['nombre'],
                'fecha' => $row['inicio'],
                'placa' => $row['placa'],
                'marca' => $row['marca'],
                'modelo' => $row['modelo'],
                'tipo' => 'checkin',
            ];
        }

        if ($latFin !== null && $lonFin !== null) {
            $data[$grupoKey]['puntos'][] = [
                'lat' => $latFin,
                'lon' => $lonFin,
                'nombre' => $row['nombre'],
                'fecha' => $row['fin'],
                'placa' => $row['placa'],
                'marca' => $row['marca'],
                'modelo' => $row['modelo'],
                'tipo' => 'checkout',
            ];
        }

        // Agregar puntos normales entre inicio y fin
        if ($row['fin']) {
            $queryPuntos = "
            SELECT gt.lat, gt.lon, gt.fecha
            FROM gps_track gt
            JOIN usuarios u ON u.usuario = gt.tel
            WHERE u.id_usuario = '{$row['id_usuario']}'
            AND gt.lat IS NOT NULL AND gt.lon IS NOT NULL
            AND gt.fecha BETWEEN '{$row['inicio']}' AND '{$row['fin']}'
            $whereFiltros
            ";

            $resPuntos = $conn->query($queryPuntos);
            if ($resPuntos) {
                while ($p = $resPuntos->fetch_assoc()) {
                    $data[$grupoKey]['puntos'][] = [
                        'lat' => floatval($p['lat']),
                        'lon' => floatval($p['lon']),
                        'fecha' => $p['fecha'],
                        'nombre' => $row['nombre'],
                        'placa' => $row['placa'],
                        'marca' => $row['marca'],
                        'modelo' => $row['modelo'],
                        'tipo' => 'normal'
                    ];
                }
            }
        }
    }
}

// 2. Sin vehículo, puntos normales
if ($con_vehiculo_esc === '' || $con_vehiculo_esc === '0') {
    $whereUsuario = $usuario_esc ? " AND u.id_usuario = '$usuario_esc'" : '';
    $querySinVehiculo = "
    SELECT gt.lat, gt.lon, gt.fecha, u.nombre
    FROM gps_track gt
    JOIN usuarios u ON u.usuario = gt.tel
    WHERE NOT EXISTS (
        SELECT 1
        FROM actividad_vehiculo av1
        JOIN actividad_vehiculo av2 ON av2.id_usuario = av1.id_usuario
            AND av2.tipo_actividad = 'FINALIZACION' AND av2.fecha_actividad > av1.fecha_actividad
        WHERE av1.tipo_actividad = 'INICIO'
        AND av1.id_usuario = u.id_usuario
        AND gt.fecha BETWEEN av1.fecha_actividad AND av2.fecha_actividad
    )
    AND gt.lat IS NOT NULL AND gt.lon IS NOT NULL
    $whereFiltros
    $whereUsuario
    ORDER BY gt.fecha
    ";

    $resSin = $conn->query($querySinVehiculo);
    if ($resSin) {
        while ($row = $resSin->fetch_assoc()) {
            $data['sinVehiculo']['puntos'][] = [
                'lat' => floatval($row['lat']),
                'lon' => floatval($row['lon']),
                'fecha' => $row['fecha'],
                'nombre' => $row['nombre'],
                'tipo' => 'normal'
            ];
        }
    }
}

echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);