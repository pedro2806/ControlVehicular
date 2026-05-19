<?php
/**
 * Geocodificador batch de clientes (Nominatim / OpenStreetMap).
 *
 * Recorre clientes donde lat IS NULL e intenta:
 *   1) CALLE + CP + CIUDAD + ESTADO   -> geo_estatus = 'ok'
 *   2) Si 1 falla: solo CP + ESTADO    -> geo_estatus = 'cp_centroide'
 *   3) Si 2 falla: marcar 'fallido'
 *
 * Respeta 1 req/seg (TOS de Nominatim). Es seguro re-ejecutar: solo procesa
 * clientes con lat IS NULL, los demás se saltan.
 *
 * Uso:  php reportes/geocodificar_clientes.php
 */

include __DIR__ . '/../conn.php';
mysqli_set_charset($conn, "utf8mb4");

$NOMINATIM_URL = 'https://nominatim.openstreetmap.org/search';
$USER_AGENT    = 'ControlVehicular-MESS/1.0 (contacto@mess-metrologicos.com)';
$DELAY_US      = 1100 * 1000; // 1.1 seg entre requests

function nominatim(array $params, string $url, string $ua): ?array {
    $params['format']       = 'json';
    $params['limit']        = 1;
    $params['countrycodes'] = 'mx';

    $ch = curl_init($url . '?' . http_build_query($params));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['User-Agent: ' . $ua],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) return null;
    $json = json_decode($resp, true);
    if (!is_array($json) || empty($json[0]['lat'])) return null;

    return [
        'lat' => floatval($json[0]['lat']),
        'lng' => floatval($json[0]['lon']),
    ];
}

$res = $conn->query("SELECT COUNT(*) AS c FROM clientes WHERE lat IS NULL AND (geo_estatus IS NULL OR geo_estatus = '')");
$total = intval($res->fetch_assoc()['c']);
echo "Clientes pendientes: $total\n";

if ($total === 0) {
    echo "Nada por geocodificar.\n";
    exit(0);
}

$stmtSelect = $conn->prepare("SELECT IDCLTE, CALLE, CP, CIUDAD, MUNICIPIO, ESTADO
    FROM clientes WHERE lat IS NULL AND (geo_estatus IS NULL OR geo_estatus = '')");
$stmtUpdate = $conn->prepare("UPDATE clientes
    SET lat = ?, lng = ?, geo_estatus = ?
    WHERE IDCLTE = ?");

$stmtSelect->execute();
$result = $stmtSelect->get_result();

$ok = 0; $cp = 0; $err = 0; $i = 0;

while ($cli = $result->fetch_assoc()) {
    $i++;
    $id     = intval($cli['IDCLTE']);
    $calle  = trim((string)($cli['CALLE']    ?? ''));
    $cpVal  = trim((string)($cli['CP']       ?? ''));
    $ciudad = trim((string)($cli['CIUDAD']   ?? '')) ?: trim((string)($cli['MUNICIPIO'] ?? ''));
    $estado = trim((string)($cli['ESTADO']   ?? ''));

    $coord   = null;
    $estatus = null;

    if ($calle !== '' && $cpVal !== '') {
        $coord = nominatim([
            'street'     => $calle,
            'postalcode' => $cpVal,
            'city'       => $ciudad,
            'state'      => $estado,
        ], $NOMINATIM_URL, $USER_AGENT);
        if ($coord !== null) $estatus = 'ok';
        usleep($DELAY_US);
    }

    if ($coord === null && $cpVal !== '') {
        $coord = nominatim([
            'postalcode' => $cpVal,
            'state'      => $estado,
        ], $NOMINATIM_URL, $USER_AGENT);
        if ($coord !== null) $estatus = 'cp_centroide';
        usleep($DELAY_US);
    }

    if ($coord !== null) {
        $stmtUpdate->bind_param("ddsi", $coord['lat'], $coord['lng'], $estatus, $id);
        $stmtUpdate->execute();
        if ($estatus === 'ok') $ok++; else $cp++;
    } else {
        $estatus = 'fallido';
        $lat = null; $lng = null;
        $stmtUpdate->bind_param("ddsi", $lat, $lng, $estatus, $id);
        $stmtUpdate->execute();
        $err++;
    }

    $porc = round(($i / $total) * 100);
    echo "\rProgreso: [$porc%] - OK: $ok - CP: $cp - Fall: $err - ID: $id    ";
}

echo "\n\nResumen final:\n";
echo "  Geocodificados (dirección exacta): $ok\n";
echo "  Centroide de CP:                   $cp\n";
echo "  Fallidos:                          $err\n";
echo "\nLos 'fallidos' tienen lat/lng NULL — el endpoint cae a Haversine para esos.\n";

$stmtSelect->close();
$stmtUpdate->close();
$conn->close();
