<?php
/**
 * Recalcula el km de los check-ins que quedaron pendientes (reconciliarKmPendientes en
 * calcular_ruta.php) y al final reconstruye las tablas de Power BI.
 *
 * Uso:  php reportes/reconciliar_km.php [dias]      (dias = cuántos días atrás; 30 por omisión)
 *
 * La importación (importar_reportes.php) ya corre la reconciliación de los últimos 30 días.
 * Este script es para ventanas más largas. Al desplegar la normalización de OT/OV del
 * 04/11/2026 se corre una vez con 45 para alcanzar los check-ins desde el 29/09 que se
 * quedaron sin km: OT que entraron tarde al catálogo y OV escritas como 'MESS-OV-…'.
 *
 * Consulta INEGI por cada check-in que recalcula (1–3 s cada uno) y escribe en
 * km_calculados. Volver a correrlo no duplica: solo toma los que siguen sin km.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Solo desde la terminal: php reportes/reconciliar_km.php [dias]');
}

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../calcular_ruta.php'; // define las funciones sin disparar el endpoint
mysqli_set_charset($conn, "utf8mb4");
date_default_timezone_set('America/Mexico_City');

$dias = max(1, intval($argv[1] ?? 30));
$inicio = microtime(true);

$r = reconciliarKmPendientes($conn, $dias);   // sin límite de tiempo: procesa todo

echo "Reconciliación de los últimos $dias días:\n";
echo "  Revisados:          {$r['revisados']}\n";
echo "  OV/OT recalculados: {$r['ovot_recalculados']}\n";
echo "  OTROS calculados:   {$r['calculados']}\n";
echo "  Volver a la sede:   {$r['regresos']}\n";
echo "  Sin actividad:      {$r['sin_actividad']}\n";
echo "  Sin destino:        {$r['sin_destino']}\n";
echo "  OV/OT ya con km:    {$r['ovot']}\n";

refrescarReporteKm($conn);
refrescarReporteKmVehiculo($conn);
$kmMess = $conn->query("SELECT COALESCE(SUM(km), 0) s FROM reporte_km")->fetch_assoc()['s'];
printf("Power BI reconstruido (reporte_km: %.1f KM Mess). Tiempo total: %.1f seg.\n", $kmMess, microtime(true) - $inicio);

$conn->close();
