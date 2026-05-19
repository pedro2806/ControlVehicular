<?php
include __DIR__ . '/conn.php';
header('Content-Type: application/json');
mysqli_set_charset($conn, "utf8mb4");
date_default_timezone_set('America/Mexico_City');

$config    = @parse_ini_file(__DIR__ . '/token.ini');
$INEGI_KEY = $config['INEGI_TOKEN'] ?? '';

// Cuando la API INEGI falla, usamos haversine (distancia en linea recta) y
// la multiplicamos por este factor para aproximar la ruta real por carretera.
const HAVERSINE_FACTOR = 1.45;

$ov_ot      = isset($_POST['ov_ot']) ? trim($_POST['ov_ot']) : '';
$origen_lat = isset($_POST['lat'])   ? floatval($_POST['lat']) : null;
$origen_lng = isset($_POST['lng'])   ? floatval($_POST['lng']) : null;
$id_usuario = isset($_COOKIE['id_usuarioL']) ? intval($_COOKIE['id_usuarioL']) : null;

if ($ov_ot === '' || $origen_lat === null || $origen_lng === null) {
    echo json_encode(['status' => 'error', 'message' => 'Parámetros incompletos']);
    exit;
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

function obtenerCoordsCliente(mysqli $conn, int $id_cliente): ?array {
    $stmt = $conn->prepare("SELECT lat, lng FROM clientes WHERE IDCLTE = ? LIMIT 1");
    $stmt->bind_param("i", $id_cliente);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$r || $r['lat'] === null || $r['lng'] === null) return null;
    return ['lat' => floatval($r['lat']), 'lng' => floatval($r['lng'])];
}

function buscarLineaInegi(float $lng, float $lat, string $key): ?array {
    $ch = curl_init('https://gaia.inegi.org.mx/sakbe_v3.1/buscalinea');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'x' => $lng, 'y' => $lat, 'escala' => 250000,
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
    $R = 6371.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    $kmRecta = 2 * $R * asin(sqrt($a));
    return round($kmRecta * HAVERSINE_FACTOR, 3);
}

function registrarKm(mysqli $conn, $ov_ot, $id_cliente, $oLat, $oLng, $dLat, $dLng, $km, $min, $metodo, $estatus, $error, $id_usuario): void {
    $stmt = $conn->prepare("INSERT INTO km_calculados
        (ov_ot, id_cliente, origen_lat, origen_lng, destino_lat, destino_lng, long_km, tiempo_min, metodo, estatus, error_msg, id_usuario)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "siddddddsssi",
        $ov_ot, $id_cliente, $oLat, $oLng, $dLat, $dLng, $km, $min, $metodo, $estatus, $error, $id_usuario
    );
    $stmt->execute();
    $stmt->close();
}

$id_cliente = resolverCliente($conn, $ov_ot);
if ($id_cliente === null) {
    echo json_encode(['status' => 'error', 'message' => "OV/OT '$ov_ot' no encontrada"]);
    exit;
}

$destino = obtenerCoordsCliente($conn, $id_cliente);
if ($destino === null) {
    registrarKm($conn, $ov_ot, $id_cliente, $origen_lat, $origen_lng,
        null, null, null, null, 'haversine', 'sin_destino',
        'Cliente sin lat/lng en BD', $id_usuario);
    echo json_encode([
        'status'  => 'error',
        'message' => 'El cliente no tiene coordenadas registradas',
        'id_cliente' => $id_cliente,
    ]);
    exit;
}

$destino_lat = $destino['lat'];
$destino_lng = $destino['lng'];

$metodo    = 'inegi';
$estatus   = 'ok';
$error_msg = null;
$km        = null;
$tiempo    = null;

$linea_o = $INEGI_KEY ? buscarLineaInegi($origen_lng, $origen_lat, $INEGI_KEY) : null;
$linea_d = $INEGI_KEY ? buscarLineaInegi($destino_lng, $destino_lat, $INEGI_KEY) : null;

if (isset($linea_o['data']['id_routing_net'], $linea_d['data']['id_routing_net'])) {
    $ruta = calcularRutaInegi($linea_o['data'], $linea_d['data'], $INEGI_KEY);
    if (isset($ruta['data']['long_km'])) {
        $km     = floatval($ruta['data']['long_km']);
        $tiempo = isset($ruta['data']['tiempo_min']) ? floatval($ruta['data']['tiempo_min']) : null;
    } else {
        $metodo    = 'haversine';
        $estatus   = 'error_api';
        $error_msg = 'INEGI /libre sin long_km';
        $km        = haversineKm($origen_lat, $origen_lng, $destino_lat, $destino_lng);
    }
} else {
    $metodo    = 'haversine';
    $estatus   = 'error_api';
    $error_msg = $INEGI_KEY === '' ? 'Token INEGI ausente' : 'INEGI /buscalinea sin id_routing_net';
    $km        = haversineKm($origen_lat, $origen_lng, $destino_lat, $destino_lng);
}

registrarKm($conn, $ov_ot, $id_cliente, $origen_lat, $origen_lng,
    $destino_lat, $destino_lng, $km, $tiempo, $metodo, $estatus, $error_msg, $id_usuario);

echo json_encode([
    'status'     => 'success',
    'km'         => $km,
    'tiempo_min' => $tiempo,
    'metodo'     => $metodo,
    'aviso'      => $error_msg,
    'id_cliente' => $id_cliente,
]);
