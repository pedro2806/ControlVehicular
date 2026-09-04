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
 * Uso por consola:   php reportes/backfill_ciclos_tarjeta.php [--dry-run]
 * Uso por navegador: /ControlVehicular/reportes/backfill_ciclos_tarjeta.php[?dry-run]
 *                    (requiere sesion con administrarVehiculos o cargarReportes)
 */

require_once __DIR__ . '/../conn.php';
mysqli_set_charset($conn, 'utf8mb4');

$esCli  = (php_sapi_name() === 'cli');
// --dry-run por CLI, ?dry-run por navegador: sin esto no se podia previsualizar desde
// el navegador, que es como se corre en los servidores sin acceso a consola.
$dryRun = $esCli
    ? in_array('--dry-run', $argv ?? [], true)
    : isset($_GET['dry-run']);

if (!$esCli) {
    header('Content-Type: text/plain; charset=utf-8');

    // Por CLI no se pide nada: llegar ahi ya implica acceso al servidor. Por navegador
    // SI, porque este script arranca borrando ciclos_tarjeta y sin esto bastaria conocer
    // la URL para dispararlo desde fuera.
    require_once __DIR__ . '/../includes/sesion_cookies.php';

    $noEmp = intval($_COOKIE['noEmpleado'] ?? 0);
    $permitido = false;
    if ($noEmp > 0) {
        $st = $conn->prepare(
            "SELECT 1 FROM mess_rrhh.accesos_especiales
              WHERE noEmpleado = ? AND sistema = 'ctrlVehicular'
                AND opcion IN ('administrarVehiculos','cargarReportes') AND estatus = 1
              LIMIT 1"
        );
        if ($st) {
            $st->bind_param("i", $noEmp);
            $st->execute();
            $permitido = (bool) $st->get_result()->fetch_assoc();
            $st->close();
        }
    }
    if (!$permitido) {
        http_response_code(403);
        exit("Sin acceso. Este script solo lo puede correr quien tenga administrarVehiculos o cargarReportes.\n");
    }
}

// Verificación previa: sin el esquema no tiene caso seguir.
if (!$conn->query("SHOW TABLES LIKE 'ciclos_tarjeta'")->num_rows) {
    exit("FALTA la tabla ciclos_tarjeta. Aplica primero el SQL del esquema.\n");
}
if (!$conn->query("SHOW COLUMNS FROM carga_gasolina LIKE 'id_ciclo'")->num_rows) {
    exit("FALTA la columna carga_gasolina.id_ciclo. Aplica primero el SQL del esquema.\n");
}

/*
 * CANDADO: este script borra TODOS los ciclos y los vuelve a deducir de las cargas,
 * pero solo sabe reconstruir los origenes INICIAL y DETECTADO.
 *
 * Un ciclo de origen APROBACION guarda de que solicitud vino y cuanto se abono, y eso
 * NO esta en carga_gasolina: no hay forma de deducirlo. Volver a correr el backfill con
 * aprobaciones ya registradas las destruiria, y con ellas el vinculo id_solicitud del
 * que sale el "abonado / falta" de las parcialidades.
 *
 * Por eso es una migracion de UNA SOLA VEZ: se corre al desplegar, antes de que se
 * apruebe la primera recarga. Despues se niega.
 */
$aprobaciones = $conn->query(
    "SELECT COUNT(*) n FROM ciclos_tarjeta WHERE origen = 'APROBACION'"
)->fetch_assoc()['n'];

if ($aprobaciones > 0 && !$dryRun) {
    exit(
        "DETENIDO: ya hay $aprobaciones ciclo(s) creado(s) por una aprobacion.\n\n" .
        "Este script los borraria y NO puede reconstruirlos: de que solicitud vinieron y\n" .
        "cuanto se abono no esta en las cargas de gasolina. Se perderia el seguimiento de\n" .
        "las parcialidades (cuanto se abono y cuanto falta).\n\n" .
        "El backfill es para correrse UNA sola vez, al desplegar. Si de verdad necesitas\n" .
        "reconstruir, respalda primero:\n" .
        "  CREATE TABLE ciclos_tarjeta_respaldo AS SELECT * FROM ciclos_tarjeta;\n"
    );
}

if ($dryRun) {
    echo "== SIMULACIÓN (--dry-run): no se escribe nada ==\n";
    if ($aprobaciones > 0) {
        echo "\n!! AVISO: hay $aprobaciones ciclo(s) de origen APROBACION.\n" .
             "   Fuera de simulacion este script se detendria para no borrarlos.\n";
    }
    echo "\n";
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
            // fecha_carga pasó de DATE a DATETIME. Antes había que pegarle la hora para que
            // ciclos_tarjeta.fecha_inicio (DATETIME) la aceptara; ahora ya la trae, y
            // concatenarla produciría "2026-09-04 13:20:00 00:00:00", una fecha inválida.
            // Se normaliza por si quedara alguna fila con formato viejo.
            $fecha = date('Y-m-d H:i:s', strtotime($c['fecha_carga']));

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
