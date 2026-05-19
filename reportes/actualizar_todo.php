<?php
/**
 * Wrapper: corre el importador SCOT seguido del geocodificador de clientes.
 *
 * Uso:  php reportes/actualizar_todo.php
 *
 * Equivalente a correr secuencialmente:
 *   1) importar_scot.php          (segundos a minutos)
 *   2) geocodificar_clientes.php  (1.1 seg/cliente nuevo)
 */

$php          = PHP_BINARY;
$scriptImport = __DIR__ . '/importar_scot.php';
$scriptGeo    = __DIR__ . '/geocodificar_clientes.php';

$inicio = microtime(true);

echo "============================================\n";
echo " PASO 1/2 — Importar SCOT\n";
echo "============================================\n";
passthru(escapeshellarg($php) . ' ' . escapeshellarg($scriptImport), $rcImport);

if ($rcImport !== 0) {
    echo "\nERROR: el importador terminó con código $rcImport. Abortando.\n";
    exit($rcImport);
}

echo "\n============================================\n";
echo " PASO 2/2 — Geocodificar clientes\n";
echo "============================================\n";
passthru(escapeshellarg($php) . ' ' . escapeshellarg($scriptGeo), $rcGeo);

$total = round(microtime(true) - $inicio, 1);
echo "\n============================================\n";
echo " Proceso completo en {$total} seg.\n";
echo "============================================\n";

exit($rcGeo);
