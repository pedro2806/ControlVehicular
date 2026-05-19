<?php
/**
 * Importador SCOT -> mess_control_vehicular
 *
 * Lee 4 CSV exportados de SCOT y carga 2 tablas:
 *   - OV  (ventas)         <- VENTAS_*.csv
 *   - OT  (órdenes de trab.) <- unión de los 3 DETALLE-*.csv
 *
 * Uso:
 *   1. Colocar los 4 CSV en la misma carpeta que este script
 *      (los nombres pueden traer fechas variables, se detecta por patrón).
 *   2. Ejecutar:   php importar_scot.php
 */

include __DIR__ . '/../conn.php';
mysqli_set_charset($conn, "utf8mb4");

// ===========================================================
// CONFIGURACIÓN — ajustar si SCOT cambia el separador o nombres
// ===========================================================
$SEPARADOR = ',';     // si SCOT exporta con ';' o "\t", cambiar aquí
$DIR_CSV   = __DIR__;

$PATRONES = [
    'ventas'       => 'VENTAS_*.csv',
    'tiempo_sitio' => 'DETALLE-TIEMPO-SITIO_*.csv',
    'sin_tiempo'   => 'DETALLE-SIN-TIEMPO_*.csv',
];
// ===========================================================

function archivoMasReciente($dir, $patron) {
    $matches = glob("$dir/$patron");
    if (!$matches) return null;
    usort($matches, function ($a, $b) { return filemtime($b) - filemtime($a); });
    return $matches[0];
}

function importarCsv($archivo, $separador, $callback) {
    $totalLineas = max(0, count(file($archivo)) - 1);
    $gestor = fopen($archivo, "r");
    fgetcsv($gestor, 0, $separador); // omitir encabezado

    $ok = 0; $err = 0;
    while (($datos = fgetcsv($gestor, 0, $separador)) !== FALSE) {
        try {
            if ($callback($datos)) $ok++; else $err++;
        } catch (Throwable $e) {
            $err++;
        }
        $porc = $totalLineas > 0 ? round(($ok + $err) / $totalLineas * 100) : 100;
        echo "\rProgreso: [{$porc}%] - OK: $ok - Err: $err  ";
    }
    fclose($gestor);
    echo "\n";
    return ['ok' => $ok, 'err' => $err];
}

// 1. Localizar archivos
$archivos = [];
foreach ($PATRONES as $key => $patron) {
    $a = archivoMasReciente($DIR_CSV, $patron);
    if (!$a) die("ERROR: no se encontró archivo para patrón '$patron' en $DIR_CSV\n");
    $archivos[$key] = $a;
    echo "Encontrado [$key]: " . basename($a) . "\n";
}

// 2. Crear tablas si no existen
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

// 3. Sentencias preparadas
$stmtOV = $conn->prepare("INSERT IGNORE INTO OV (OV, id_cliente) VALUES (?, ?)");
$stmtOT = $conn->prepare("INSERT INTO OT (OT, id_cliente) VALUES (?, ?)
    ON DUPLICATE KEY UPDATE id_cliente = VALUES(id_cliente)");

// 4. Importar
try {
    // --- Archivo 1: VENTAS -> tabla OV ---
    echo "\nImportando " . basename($archivos['ventas']) . " -> OV\n";
    $conn->begin_transaction();
    $r = importarCsv($archivos['ventas'], $SEPARADOR, function ($datos) use ($stmtOV) {
        $ov         = isset($datos[11]) ? trim($datos[11]) : '';  // col L
        $id_cliente = isset($datos[1])  ? trim($datos[1])  : '';  // col B
        if ($ov === '') return false;
        $stmtOV->bind_param("ss", $ov, $id_cliente);
        return $stmtOV->execute();
    });
    $conn->commit();
    echo "Resumen OV: {$r['ok']} insertados, {$r['err']} errores\n";

    // --- Archivos 2/3/4: DETALLE-* -> tabla OT (unidos) ---
    foreach (['tiempo_sitio', 'sin_tiempo'] as $key) {
        echo "\nImportando " . basename($archivos[$key]) . " -> OT\n";
        $conn->begin_transaction();
        $r = importarCsv($archivos[$key], $SEPARADOR, function ($datos) use ($stmtOT) {
            $id_cliente = isset($datos[12]) ? trim($datos[12]) : '';  // col M
            $oType      = isset($datos[13]) ? trim($datos[13]) : '';  // col N
            // "EL21-01P-215_SERVICE" -> "EL21-01P-215"
            $pos = strpos($oType, '_');
            $ot  = $pos !== false ? substr($oType, 0, $pos) : $oType;
            if ($ot === '') return false;
            $stmtOT->bind_param("ss", $ot, $id_cliente);
            return $stmtOT->execute();
        });
        $conn->commit();
        echo "Resumen OT [$key]: {$r['ok']} insertados, {$r['err']} errores\n";
    }
} catch (Throwable $e) {
    $conn->rollback();
    echo "\nError crítico: " . $e->getMessage() . "\n";
    exit(1);
}

$stmtOV->close();
$stmtOT->close();
$conn->close();
echo "\nImportación finalizada.\n";
