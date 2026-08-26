<?php
/**
 * Reconstruye los ciclos de tarjeta a partir del historial de cargas.
 *
 * Se corre UNA VEZ tras crear la tabla ciclos_tarjeta y la columna carga_gasolina.id_ciclo
 * (ver .claude/ciclos_tarjeta_2026-08-25.sql). Es idempotente: borra los ciclos y vuelve a
 * calcularlos desde cero, así que se puede repetir sin ensuciar nada.
 *
 * Regla de corte: en carga_gasolina `monto` es el saldo ANTES de la carga. Hay ciclo nuevo
 * cuando es la primera carga del vehículo (INICIAL) o cuando ese monto supera el saldo que
 * dejó la carga anterior, señal de que alguien abonó dinero en medio (DETECTADO). El abono
 * es la diferencia.
 *
 * NO se usa el criterio viejo de `monto >= 4000`: hay tarjetas que nunca se llenan al tope
 * (en la BD local había ciclos arrancando en 748.77 y 1493.92) y ese criterio las ignora.
 *
 * Uso:  php reportes/backfill_ciclos_tarjeta.php [--dry-run]
 */

require_once __DIR__ . '/../conn.php';
mysqli_set_charset($conn, 'utf8mb4');

$dryRun = in_array('--dry-run', $argv ?? [], true);
$esCli  = (php_sapi_name() === 'cli');
if (!$esCli) { header('Content-Type: text/plain; charset=utf-8'); }

// Verificación previa: sin el esquema no tiene caso seguir.
if (!$conn->query("SHOW TABLES LIKE 'ciclos_tarjeta'")->num_rows) {
    exit("FALTA la tabla ciclos_tarjeta. Aplica primero el SQL del esquema.\n");
}
if (!$conn->query("SHOW COLUMNS FROM carga_gasolina LIKE 'id_ciclo'")->num_rows) {
    exit("FALTA la columna carga_gasolina.id_ciclo. Aplica primero el SQL del esquema.\n");
}

if ($dryRun) {
    echo "== SIMULACIÓN (--dry-run): no se escribe nada ==\n\n";
} else {
    $conn->query("DELETE FROM ciclos_tarjeta");
    $conn->query("UPDATE carga_gasolina SET id_ciclo = NULL");
}

$insCiclo = $conn->prepare(
    "INSERT INTO ciclos_tarjeta
        (id_vehiculo, fecha_inicio, saldo_inicial, monto_abonado, id_usuario, origen, estatus)
     VALUES (?, ?, ?, ?, ?, ?, 'ABIERTO')"
);
$cerrar  = $conn->prepare("UPDATE ciclos_tarjeta SET fecha_fin = ?, estatus = 'CERRADO' WHERE id_ciclo = ?");
$asignar = $conn->prepare("UPDATE carga_gasolina SET id_ciclo = ? WHERE id = ?");

$vehiculos = $conn->query("SELECT DISTINCT id_vehiculo FROM carga_gasolina ORDER BY id_vehiculo");
$totCiclos = 0;
$totCargas = 0;

while ($v = $vehiculos->fetch_assoc()) {
    $idv = intval($v['id_vehiculo']);

    // Orden por fecha_carga y no por fecha_registro: capturar en lote deja varias cargas
    // con el mismo registro, y lo que ordena el consumo es cuándo se cargó combustible.
    $cargas = $conn->query(
        "SELECT id, id_usuario, monto, pagos, saldo, fecha_carga
         FROM carga_gasolina WHERE id_vehiculo = $idv
         ORDER BY fecha_carga, id"
    );

    $saldoPrevio = null;
    $cicloActual = 0;

    while ($c = $cargas->fetch_assoc()) {
        $monto = (float) $c['monto'];
        // El 0.01 es tolerancia de redondeo de DECIMAL(10,2), no un umbral de negocio.
        $esNuevo = ($saldoPrevio === null) || ($monto - $saldoPrevio > 0.01);

        if ($esNuevo) {
            $fecha = $c['fecha_carga'] . ' 00:00:00';

            if ($cicloActual && !$dryRun) {
                $cerrar->bind_param("si", $fecha, $cicloActual);
                $cerrar->execute();
            }

            $origen  = ($saldoPrevio === null) ? 'INICIAL' : 'DETECTADO';
            $abonado = ($saldoPrevio === null) ? null : round($monto - $saldoPrevio, 2);
            $idu     = intval($c['id_usuario']);

            if ($dryRun) {
                printf("  veh %-4d %s  %-9s saldo_inicial=%-9s abonado=%s\n",
                    $idv, substr($fecha, 0, 10), $origen, number_format($monto, 2), $abonado === null ? '-' : number_format($abonado, 2));
                $cicloActual = -1;
            } else {
                $insCiclo->bind_param("isddis", $idv, $fecha, $monto, $abonado, $idu, $origen);
                $insCiclo->execute();
                $cicloActual = $conn->insert_id;
            }
            $totCiclos++;
        }

        if (!$dryRun) {
            $asignar->bind_param("ii", $cicloActual, $c['id']);
            $asignar->execute();
        }
        $totCargas++;
        $saldoPrevio = (float) $c['saldo'];
    }
}

echo "\nciclos: $totCiclos   cargas: $totCargas\n";

if (!$dryRun) {
    $huerfanas = $conn->query("SELECT COUNT(*) n FROM carga_gasolina WHERE id_ciclo IS NULL")->fetch_assoc()['n'];
    echo "cargas sin ciclo: $huerfanas" . ($huerfanas > 0 ? "  <-- REVISAR\n" : "  (correcto)\n");
}
