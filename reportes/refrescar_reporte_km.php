<?php
/**
 * Reconstruye por completo las dos tablas del KPI de kilometraje (Power BI):
 *   - reporte_km           — fact table, una fila por trayecto calculado.
 *   - reporte_km_vehiculo  — resumen vehículo×semana (km registrado vs calculado, % uso trabajo).
 * a partir de km_calculados + actividad_vehiculo + dimensiones.
 *
 * Uso:  php reportes/refrescar_reporte_km.php
 *
 * Idempotente: crea las tablas si no existen y reemplaza TODAS las filas. También
 * se mantienen vivas en tiempo real por el hook en registrarKm() (calcular_ruta.php);
 * este script es la red de seguridad para cron/manual.
 */

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../calcular_ruta.php'; // define las funciones de refresco sin disparar el endpoint
mysqli_set_charset($conn, "utf8mb4");
date_default_timezone_set('America/Mexico_City');

$inicio = microtime(true);
refrescarReporteKm($conn);          // fact por trayecto (sin $idKm = total)
refrescarReporteKmVehiculo($conn);  // resumen vehículo×mes (sin $idVehiculo = total)

$total   = $conn->query("SELECT COUNT(*) c FROM reporte_km")->fetch_assoc()['c'];
$validos = $conn->query("SELECT COUNT(*) c FROM reporte_km WHERE km_valido = 1")->fetch_assoc()['c'];
$km      = $conn->query("SELECT COALESCE(SUM(km),0) s FROM reporte_km WHERE km_valido = 1")->fetch_assoc()['s'];
$resumen = $conn->query("SELECT COUNT(*) c FROM reporte_km_vehiculo")->fetch_assoc()['c'];

printf(
    "reporte_km: %d filas (%d con km válido, %.1f km).\nreporte_km_vehiculo: %d filas (vehículo×semana).\nReconstruido en %.1f seg.\n",
    $total, $validos, $km, $resumen, microtime(true) - $inicio
);
