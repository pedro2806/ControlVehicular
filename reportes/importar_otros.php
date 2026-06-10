<?php
/**
 * Importador del archivo "Actividades" (OTROS) de SCOT -> tabla `otros`.
 *
 * Funciona como el importador OV/OT: lee el CSV y puebla una tabla. El cálculo
 * de km NO ocurre aquí, sino en calcular_ruta.php al hacer check-in por QR
 * (resuelve el cliente desde la tabla `otros` por usuario).
 *
 * Columnas del CSV (separador ','):
 *   [0] IDUSR  [1] FeActividad (dd/mm/YYYY H:i)  [2] Actividad  [3] IDCliente
 *   [7] DESCRIPCION  [8] latitude  [9] longitude  [12] order_code
 *
 * Se IGNORAN los renglones cuya Actividad sea CORREO o LLAMADA (no implican
 * traslado). Se importan VISITA, APPVISITA, OTRO, etc.
 *
 * Uso CLI:  php reportes/importar_otros.php
 * También es incluible: define las funciones sin ejecutar el bloque principal.
 */

if (!function_exists('archivoMasRecienteOtros')) {
    function archivoMasRecienteOtros(string $dir, string $patron): ?string {
        $matches = glob("$dir/$patron");
        if (!$matches) return null;
        usort($matches, function ($a, $b) { return filemtime($b) - filemtime($a); });
        return $matches[0];
    }
}

if (!function_exists('crearTablaOtros')) {
    function crearTablaOtros(mysqli $conn): void {
        $conn->query("CREATE TABLE IF NOT EXISTS otros (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_usuario INT NOT NULL,
            fecha DATETIME NULL,
            actividad VARCHAR(50) NOT NULL,
            id_cliente INT NOT NULL,
            descripcion VARCHAR(500) NULL,
            latitude DECIMAL(10,7) NULL,
            longitude DECIMAL(10,7) NULL,
            order_code VARCHAR(50) NULL,
            UNIQUE KEY uk_otros (id_usuario, fecha, actividad, id_cliente),
            KEY idx_usuario (id_usuario),
            KEY idx_order_code (order_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}

if (!function_exists('importarOtros')) {
    /**
     * Lee el CSV y puebla la tabla `otros`.
     * @return array contadores: ok, ignoradas, dup, err
     */
    function importarOtros(mysqli $conn, string $archivoCsv, string $separador = ','): array {
        $res = ['ok' => 0, 'ignoradas' => 0, 'dup' => 0, 'err' => 0];

        // Actividades que NO implican traslado (se ignoran al leer el CSV)
        $ACTIVIDADES_IGNORADAS = ['CORREO', 'LLAMADA'];

        $gestor = @fopen($archivoCsv, 'r');
        if (!$gestor) { $res['err']++; return $res; }
        fgetcsv($gestor, 0, $separador); // omitir encabezado

        crearTablaOtros($conn);
        $stmt = $conn->prepare("INSERT IGNORE INTO otros
            (id_usuario, fecha, actividad, id_cliente, descripcion, latitude, longitude, order_code)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        $conn->begin_transaction();
        while (($d = fgetcsv($gestor, 0, $separador)) !== false) {
            try {
                $idUsr     = isset($d[0])  ? intval(trim($d[0]))  : 0;
                $feRaw     = isset($d[1])  ? trim($d[1])          : '';
                $actividad = isset($d[2])  ? trim($d[2])          : '';
                $idCliente = isset($d[3])  ? intval(trim($d[3]))  : 0;
                $descrip   = isset($d[7])  ? trim($d[7])          : '';
                $latRaw    = isset($d[8])  ? trim($d[8])          : '';
                $lngRaw    = isset($d[9])  ? trim($d[9])          : '';
                $orderCode = isset($d[12]) ? trim($d[12])         : '';

                if ($idUsr <= 0 || $idCliente <= 0 || $actividad === '') { $res['err']++; continue; }

                // Ignorar actividades sin traslado (CORREO, LLAMADA)
                if (in_array(strtoupper($actividad), $ACTIVIDADES_IGNORADAS, true)) { $res['ignoradas']++; continue; }

                // Coordenadas de la actividad (pueden venir vacías) y metadatos
                $lat       = ($latRaw !== '' && is_numeric($latRaw)) ? floatval($latRaw) : null;
                $lng       = ($lngRaw !== '' && is_numeric($lngRaw)) ? floatval($lngRaw) : null;
                $descrip   = $descrip   !== '' ? mb_substr($descrip, 0, 500) : null;
                $orderCode = $orderCode !== '' ? $orderCode : null;

                // Fecha + hora. El CSV trae "d/m/Y H:i:s" (a veces con doble
                // espacio entre fecha y hora). Se normaliza el espaciado y se
                // intenta con hora; si no trae hora, se cae a solo fecha.
                $fechaSql = null;
                if ($feRaw !== '') {
                    $feNorm = preg_replace('/\s+/', ' ', $feRaw);
                    $dt = DateTime::createFromFormat('d/m/Y H:i:s', $feNorm)
                        ?: DateTime::createFromFormat('d/m/Y H:i', $feNorm)
                        ?: DateTime::createFromFormat('!d/m/Y', substr($feNorm, 0, 10));
                    if ($dt) $fechaSql = $dt->format('Y-m-d H:i:s');
                }

                $stmt->bind_param("issisdds", $idUsr, $fechaSql, $actividad, $idCliente, $descrip, $lat, $lng, $orderCode);
                $stmt->execute();
                if ($stmt->affected_rows > 0) $res['ok']++; else $res['dup']++;
            } catch (Throwable $e) {
                $res['err']++;
            }
        }
        $conn->commit();

        $stmt->close();
        fclose($gestor);
        return $res;
    }
}

// ---------------------------------------------------------------------------
// Bloque principal (solo cuando se invoca directamente por CLI)
// ---------------------------------------------------------------------------
if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
    include __DIR__ . '/../conn.php';
    mysqli_set_charset($conn, "utf8mb4");
    date_default_timezone_set('America/Mexico_City');

    $archivo = archivoMasRecienteOtros(__DIR__, 'DETALLE-OTROS_*.csv');
    if (!$archivo) {
        echo "ERROR: no se encontró DETALLE-OTROS_*.csv en " . __DIR__ . "\n";
        exit(1);
    }

    echo "Importando " . basename($archivo) . " -> tabla otros\n";

    $inicio = microtime(true);
    $r = importarOtros($conn, $archivo);
    $dur = round(microtime(true) - $inicio, 1);

    echo "\nResumen OTROS:\n";
    echo "  Importadas:            {$r['ok']}\n";
    echo "  Ignoradas (corr/llam): {$r['ignoradas']}\n";
    echo "  Duplicadas:            {$r['dup']}\n";
    echo "  Errores:               {$r['err']}\n";
    echo "  Tiempo:                {$dur} seg\n";

    // Reconciliar check-ins que quedaron sin km (actividad subida después).
    require_once __DIR__ . '/../calcular_ruta.php';
    $rc = reconciliarKmPendientes($conn);
    echo "\nReconciliación de check-ins pendientes:\n";
    echo "  Revisados:     {$rc['revisados']}\n";
    echo "  Calculados:    {$rc['calculados']}\n";
    echo "  Sin actividad: {$rc['sin_actividad']}\n";
    echo "  Sin destino:   {$rc['sin_destino']}\n";
    echo "  OV/OT (omit.): {$rc['ovot']}\n";

    $conn->close();
}
