<?php
/**
 * Genera la foto del mes pasado del resumen mensual (analisis_mensual_vehiculo.datos) para
 * toda la flota activa. Pensado para un cron del día 1 de cada mes, así la pestaña Vehículo y
 * el agente b4 ya encuentran el mes cargado.
 *
 * Uso:  php reportes/generar_analisis_mensual.php [YYYY-MM]
 *       Sin mes, el mes pasado. Un mes que ya tiene foto no se vuelve a calcular.
 *
 * Si el cron no corre, no pasa nada grave: la primera consulta del mes (b4 o la pestaña) la
 * genera igual. Solo terminal o cron: por URL responde 403.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Solo desde la terminal: php reportes/generar_analisis_mensual.php [YYYY-MM]');
}

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../calcular_ruta.php';          // sqlKmMessCheckin(); no dispara su endpoint
require_once __DIR__ . '/../includes/mes_vehiculos.php';
mysqli_set_charset($conn, "utf8mb4");
date_default_timezone_set('America/Mexico_City');

$mes = mesPedido((string) ($argv[1] ?? ''));
$inicio = microtime(true);
$generados = asegurarMesGenerado($conn, $mes);
$total = count(filasMes($conn, $mes));

printf("Mes %s: %d vehículos generados ahora, %d con foto en total. %.1f seg.\n",
    $mes['clave'], $generados, $total, microtime(true) - $inicio);
