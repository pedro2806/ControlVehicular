<?php
include 'conn.php';
header('Content-Type: application/json');
mysqli_set_charset($conn, "utf8mb4");
date_default_timezone_set('America/Mexico_City');

// Detectar POST overflow: si Content-Length > 0 pero $_POST/$_FILES estan
// vacios significa que el body excedio post_max_size o upload_max_filesize.
// PHP en ese caso descarta ambos arrays silenciosamente.
$contentLength = intval($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($contentLength > 0 && empty($_POST) && empty($_FILES)) {
    $postMax   = ini_get('post_max_size');
    $uploadMax = ini_get('upload_max_filesize');
    echo json_encode([
        'success' => false,
        'error'   => "El total enviado (" . round($contentLength / 1048576, 1) . " MB) excede el limite de PHP. "
                   . "Pide al administrador subir post_max_size (actual: $postMax) y upload_max_filesize (actual: $uploadMax) a por lo menos 64M en php.ini y reiniciar Apache."
    ]);
    exit;
}

$accion     = $_POST['accion'] ?? '';
$noEmpleado = intval($_COOKIE['noEmpleado'] ?? 0);

// =====================================================================
// 1) VERIFICAR ACCESO
// =====================================================================
function tieneAccesoCargarReportes(mysqli $conn, int $noEmpleado): bool {
    if ($noEmpleado <= 0) return false;
    $stmt = $conn->prepare(
        "SELECT 1 FROM mess_rrhh.accesos_especiales
         WHERE noEmpleado = ? AND sistema = 'ctrlVehicular' AND opcion = 'cargarReportes' AND estatus = 1
         LIMIT 1"
    );
    if (!$stmt) return false;
    $stmt->bind_param("i", $noEmpleado);
    $stmt->execute();
    $tiene = (bool) $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $tiene;
}

if ($accion === 'verificarAcceso') {
    echo json_encode(['tieneAcceso' => tieneAccesoCargarReportes($conn, $noEmpleado)]);
    exit;
}

// =====================================================================
// 2) EJECUTAR IMPORTACION
// =====================================================================
if ($accion === 'ejecutar') {
    if (!tieneAccesoCargarReportes($conn, $noEmpleado)) {
        echo json_encode(['success' => false, 'error' => 'Sin permisos para esta accion.']);
        exit;
    }

    @set_time_limit(0);
    @ini_set('memory_limit', '512M');

    $inicio = microtime(true);

    $campos = ['ventas', 'tiempo_sitio', 'sin_tiempo'];
    foreach ($campos as $c) {
        if (!isset($_FILES[$c]) || $_FILES[$c]['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => "Falta archivo: $c"]);
            exit;
        }
        $ext = strtolower(pathinfo($_FILES[$c]['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            echo json_encode(['success' => false, 'error' => "Archivo $c no es .csv"]);
            exit;
        }
    }

    // Guardar archivos con nombre canonico (sobreescribe la version anterior)
    $dirReportes = __DIR__ . DIRECTORY_SEPARATOR . 'reportes';
    if (!is_dir($dirReportes)) mkdir($dirReportes, 0777, true);

    $stamp = date('Ymd_His');
    $destinos = [
        'ventas'       => "$dirReportes/VENTAS_$stamp.csv",
        'tiempo_sitio' => "$dirReportes/DETALLE-TIEMPO-SITIO_$stamp.csv",
        'sin_tiempo'   => "$dirReportes/DETALLE-SIN-TIEMPO_$stamp.csv",
    ];

    foreach ($destinos as $key => $ruta) {
        if (!move_uploaded_file($_FILES[$key]['tmp_name'], $ruta)) {
            echo json_encode(['success' => false, 'error' => "No se pudo guardar $key"]);
            exit;
        }
    }

    $separador = ',';

    // Crear tablas si no existen (igual que el script CLI)
    $conn->query("CREATE TABLE IF NOT EXISTS OV (
        id INT AUTO_INCREMENT PRIMARY KEY,
        OV VARCHAR(50) NOT NULL,
        id_cliente VARCHAR(50) NULL,
        UNIQUE KEY uk_OV (OV)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS OT (
        id INT AUTO_INCREMENT PRIMARY KEY,
        OT VARCHAR(100) NOT NULL,
        id_cliente VARCHAR(50) NULL,
        UNIQUE KEY uk_OT (OT)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    try {
        $resumen = [
            'ov_ok' => 0, 'ov_err' => 0,
            'ot_ts_ok' => 0, 'ot_ts_err' => 0,
            'ot_st_ok' => 0, 'ot_st_err' => 0,
        ];

        // --- OV (VENTAS) ---
        $stmtOV = $conn->prepare("INSERT IGNORE INTO OV (OV, id_cliente) VALUES (?, ?)");
        $r = importarCsvWeb($destinos['ventas'], $separador, function ($datos) use ($stmtOV) {
            $ov         = isset($datos[11]) ? trim($datos[11]) : '';
            $id_cliente = isset($datos[1])  ? trim($datos[1])  : '';
            if ($ov === '') return false;
            $stmtOV->bind_param("ss", $ov, $id_cliente);
            return $stmtOV->execute();
        }, $conn);
        $stmtOV->close();
        $resumen['ov_ok']  = $r['ok'];
        $resumen['ov_err'] = $r['err'];

        // --- OT (DETALLE-TIEMPO-SITIO) ---
        $stmtOT = $conn->prepare("INSERT INTO OT (OT, id_cliente) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE id_cliente = VALUES(id_cliente)");
        $r = importarCsvWeb($destinos['tiempo_sitio'], $separador, function ($datos) use ($stmtOT) {
            $id_cliente = isset($datos[12]) ? trim($datos[12]) : '';
            $oType      = isset($datos[13]) ? trim($datos[13]) : '';
            $pos = strpos($oType, '_');
            $ot  = $pos !== false ? substr($oType, 0, $pos) : $oType;
            if ($ot === '') return false;
            $stmtOT->bind_param("ss", $ot, $id_cliente);
            return $stmtOT->execute();
        }, $conn);
        $resumen['ot_ts_ok']  = $r['ok'];
        $resumen['ot_ts_err'] = $r['err'];

        // --- OT (DETALLE-SIN-TIEMPO) ---
        $r = importarCsvWeb($destinos['sin_tiempo'], $separador, function ($datos) use ($stmtOT) {
            $id_cliente = isset($datos[12]) ? trim($datos[12]) : '';
            $oType      = isset($datos[13]) ? trim($datos[13]) : '';
            $pos = strpos($oType, '_');
            $ot  = $pos !== false ? substr($oType, 0, $pos) : $oType;
            if ($ot === '') return false;
            $stmtOT->bind_param("ss", $ot, $id_cliente);
            return $stmtOT->execute();
        }, $conn);
        $stmtOT->close();
        $resumen['ot_st_ok']  = $r['ok'];
        $resumen['ot_st_err'] = $r['err'];

    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => 'Error en importacion: ' . $e->getMessage()]);
        exit;
    }

    // --- Geocodificacion (opcional) ---
    $resumen['geo'] = null;
    if (($_POST['geocodificar'] ?? '0') === '1') {
        try {
            $resumen['geo'] = geocodificarClientesNuevos($conn);
        } catch (Throwable $e) {
            $resumen['geo'] = ['error' => $e->getMessage(), 'ok' => 0, 'cp' => 0, 'err' => 0];
        }
    }

    $resumen['success']  = true;
    $resumen['duracion'] = round(microtime(true) - $inicio, 1);
    echo json_encode($resumen);
    exit;
}

echo json_encode(['error' => 'Accion no reconocida.']);

// =====================================================================
// HELPERS
// =====================================================================
function importarCsvWeb(string $archivo, string $separador, callable $callback, mysqli $conn): array {
    $gestor = fopen($archivo, "r");
    if (!$gestor) throw new RuntimeException("No se pudo abrir $archivo");
    fgetcsv($gestor, 0, $separador); // omitir encabezado

    $ok = 0; $err = 0;
    $conn->begin_transaction();
    while (($datos = fgetcsv($gestor, 0, $separador)) !== FALSE) {
        try {
            if ($callback($datos)) $ok++; else $err++;
        } catch (Throwable $e) {
            $err++;
        }
    }
    $conn->commit();
    fclose($gestor);
    return ['ok' => $ok, 'err' => $err];
}

function geocodificarClientesNuevos(mysqli $conn): array {
    $NOMINATIM_URL = 'https://nominatim.openstreetmap.org/search';
    $USER_AGENT    = 'ControlVehicular-MESS/1.0 (contacto@mess-metrologicos.com)';
    $DELAY_US      = 1100 * 1000;

    $stmtSelect = $conn->prepare("SELECT IDCLTE, CALLE, CP, CIUDAD, MUNICIPIO, ESTADO
        FROM clientes WHERE lat IS NULL AND (geo_estatus IS NULL OR geo_estatus = '')");
    $stmtUpdate = $conn->prepare("UPDATE clientes
        SET lat = ?, lng = ?, geo_estatus = ?
        WHERE IDCLTE = ?");

    $stmtSelect->execute();
    $result = $stmtSelect->get_result();

    $ok = 0; $cp = 0; $err = 0;

    while ($cli = $result->fetch_assoc()) {
        $id     = intval($cli['IDCLTE']);
        $calle  = trim((string)($cli['CALLE']    ?? ''));
        $cpVal  = trim((string)($cli['CP']       ?? ''));
        $ciudad = trim((string)($cli['CIUDAD']   ?? '')) ?: trim((string)($cli['MUNICIPIO'] ?? ''));
        $estado = trim((string)($cli['ESTADO']   ?? ''));

        $coord = null; $estatus = null;

        if ($calle !== '' && $cpVal !== '') {
            $coord = nominatimQuery([
                'street' => $calle, 'postalcode' => $cpVal,
                'city' => $ciudad, 'state' => $estado,
            ], $NOMINATIM_URL, $USER_AGENT);
            if ($coord !== null) $estatus = 'ok';
            usleep($DELAY_US);
        }

        if ($coord === null && $cpVal !== '') {
            $coord = nominatimQuery([
                'postalcode' => $cpVal, 'state' => $estado,
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
    }

    $stmtSelect->close();
    $stmtUpdate->close();
    return ['ok' => $ok, 'cp' => $cp, 'err' => $err];
}

function nominatimQuery(array $params, string $url, string $ua): ?array {
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
