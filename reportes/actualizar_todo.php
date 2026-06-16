<?php
/**
 * Wrapper: corre el importador SCOT seguido del geocodificador de clientes.
 *
 * Uso:  php reportes/actualizar_todo.php
 *
 * Equivalente a correr secuencialmente:
 *   1) importar_scot.php           (segundos a minutos)
 *   2) geocodificar_clientes.php   (1.1 seg/cliente nuevo)
 *   3) importar_otros.php          (carga Actividades sin OV/OT a la tabla `otros`, si hay CSV)
 *   4) refrescar_reporte_km.php    (reconstruye el fact table reporte_km del KPI de Power BI)
 */

$php          = PHP_BINARY;
$scriptImport  = __DIR__ . '/importar_scot.php';
$scriptGeo     = __DIR__ . '/geocodificar_clientes.php';
$scriptOtros   = __DIR__ . '/importar_otros.php';
$scriptReporte = __DIR__ . '/refrescar_reporte_km.php';

$inicio = microtime(true);

echo "============================================\n";
echo " PASO 1/4 — Importar SCOT\n";
echo "============================================\n";
passthru(escapeshellarg($php) . ' ' . escapeshellarg($scriptImport), $rcImport);

if ($rcImport !== 0) {
    echo "\nERROR: el importador terminó con código $rcImport. Abortando.\n";
    exit($rcImport);
}

echo "\n============================================\n";
echo " PASO 2/4 — Geocodificar clientes\n";
echo "============================================\n";
passthru(escapeshellarg($php) . ' ' . escapeshellarg($scriptGeo), $rcGeo);

echo "\n============================================\n";
echo " PASO 3/4 — Importar Actividades (OTROS)\n";
echo "============================================\n";
if (glob(__DIR__ . '/DETALLE-OTROS_*.csv')) {
    passthru(escapeshellarg($php) . ' ' . escapeshellarg($scriptOtros), $rcOtros);
} else {
    echo "Sin archivo DETALLE-OTROS_*.csv, se omite.\n";
    $rcOtros = 0;
}

echo "\n============================================\n";
echo " PASO 4/4 — Refrescar reporte_km (KPI Power BI)\n";
echo "============================================\n";
passthru(escapeshellarg($php) . ' ' . escapeshellarg($scriptReporte), $rcReporte);

$total = round(microtime(true) - $inicio, 1);
echo "\n============================================\n";
echo " Proceso completo en {$total} seg.\n";
echo "============================================\n";

exit($rcGeo !== 0 ? $rcGeo : ($rcOtros !== 0 ? $rcOtros : $rcReporte));
