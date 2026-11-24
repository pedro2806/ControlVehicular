<?php
/**
 * Resumen semanal de un vehículo para la pestaña Vehículo del portal (loginMaster/inicio.php,
 * renderCardVehiculo). Pedido del 24/11/2026.
 *
 * Devuelve dos cosas de la semana ISO pedida (sin `lunes`, la semana pasada):
 *   - datos:    los números de la semana (km, check-ins, cargas), calculados al momento con la
 *               regla única de KM Mess (includes/semana_vehiculos.php → sqlKmMessCheckin).
 *   - analisis: las alertas y el comentario que publicó el agente b4 (api_analisis_semanal.php),
 *               o null si todavía no lo publica.
 *
 * Solo lectura. Quién puede verlo: los vehículos que lista la pestaña (getPlaca en
 * incidencias/validaLoginMaster.php: asignados o con préstamo vigente) o quien tenga
 * verTodosVehiculo.
 */
include 'includes/api_bootstrap.php';
require_once __DIR__ . '/calcular_ruta.php';            // sqlKmMessCheckin(); incluirlo no dispara su endpoint
require_once __DIR__ . '/includes/semana_vehiculos.php';

$accion     = $_POST['accion'] ?? '';
$noEmpleado = intval($_COOKIE['noEmpleado'] ?? 0);

if ($noEmpleado <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Sesión expirada. Inicia sesión nuevamente.']);
    exit;
}

/** ¿El vehículo está en la lista de quien consulta? Ver encabezado. */
function puedeVerVehiculo(mysqli $conn, int $noEmpleado, int $idVeh): bool {
    if (tieneAccesoEspecial($conn, $noEmpleado, 'verTodosVehiculo')) return true;

    // id_usuario de RRHH por noEmpleado (único en RRHH) y el de la cookie de sesión.
    $ids = [intval($_COOKIE['id_usuario'] ?? 0)];
    foreach (filasPreparadas($conn, "SELECT id_usuario FROM mess_rrhh.usuarios WHERE noEmpleado = ? AND id_usuario > 0",
                             "s", [(string) $noEmpleado]) as $f) {
        $ids[] = intval($f['id_usuario']);
    }
    $ids = array_values(array_unique(array_filter($ids)));
    if (!$ids) return false;

    list($in, $t) = inEnteros($ids);
    $filas = filasPreparadas($conn,
        "SELECT 1 FROM inventario i
         WHERE i.id_vehiculo = ? AND i.estatus = 'Activo'
           AND (i.id_usuario IN ($in) OR i.id_us_asignado IN ($in)
                OR EXISTS (SELECT 1 FROM prestamos p
                           WHERE p.id_vehiculo = i.id_vehiculo AND p.estatus IN ('AUTORIZADO','EN CURSO')
                             AND p.id_usuario IN ($in)))
         LIMIT 1",
        "i" . $t . $t . $t, array_merge([$idVeh], $ids, $ids, $ids));
    return (bool) $filas;
}

if ($accion === 'obtenerResumenSemanal') {
    $idVeh = intval($_POST['id_vehiculo'] ?? 0);
    if ($idVeh <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Vehículo inválido.']);
        exit;
    }
    if (!puedeVerVehiculo($conn, $noEmpleado, $idVeh)) {
        echo json_encode(['status' => 'error', 'message' => 'No tienes acceso a este vehículo.']);
        exit;
    }

    $semana = semanaPedida((string) ($_POST['lunes'] ?? ''));
    $datos = datosSemanaVehiculos($conn, $semana, [$idVeh]);
    if (!$datos) {
        echo json_encode(['status' => 'error', 'message' => 'Vehículo no encontrado o dado de baja.']);
        exit;
    }
    $analisis = analisisPublicado($conn, $semana['desde'], [$idVeh]);

    echo json_encode([
        'status' => 'success',
        'semana' => [
            'lunes'     => $semana['desde'],
            'domingo'   => $semana['domingo'],
            'numero'    => $semana['numero'],
            'anio'      => $semana['anio'],
            'en_curso'  => $semana['en_curso'],
            'anterior'  => $semana['lunes']->modify('-7 days')->format('Y-m-d'),
            'siguiente' => $semana['en_curso'] ? null : $semana['hasta'],
        ],
        'datos'    => $datos[0],
        'analisis' => $analisis[$idVeh] ?? null,
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Acción no reconocida.']);
