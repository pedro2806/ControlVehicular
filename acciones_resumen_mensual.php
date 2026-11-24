<?php
/**
 * Resumen mensual de un vehículo para la pestaña Vehículo del portal (loginMaster/inicio.php,
 * renderCardVehiculo). Pedido del 24/11/2026.
 *
 * Devuelve, del mes pedido ('YYYY-MM'; sin mes, el mes pasado; solo meses cerrados):
 *   - datos:       los números del mes (km, check-ins, cargas). Se leen de la foto guardada en
 *                  analisis_mensual_vehiculo (includes/mes_vehiculos.php), no se recalculan.
 *   - actualizado: cuándo se calcularon esos números.
 *   - analisis:    las alertas y el comentario que publicó el agente b4
 *                  (api_analisis_mensual.php), o null si todavía no lo publica.
 *
 * Solo responde a quien tenga el acceso especial verResumenMensual (24/11/2026: antes de
 * liberarlo a todos lo revisa Sebastian); a los demás les devuelve status 'no_disponible' y el
 * portal no muestra el bloque. Dentro de eso, los vehículos que lista la pestaña (getPlaca en
 * incidencias/validaLoginMaster.php: asignados o con préstamo vigente) o todos con
 * verTodosVehiculo.
 *
 * Escribe solo la primera vez que se pide un mes que no tiene foto (la genera para toda la
 * flota, ver asegurarMesGenerado).
 */
include 'includes/api_bootstrap.php';
require_once __DIR__ . '/calcular_ruta.php';            // sqlKmMessCheckin(); incluirlo no dispara su endpoint
require_once __DIR__ . '/includes/mes_vehiculos.php';

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

if ($accion === 'obtenerResumenMensual') {
    // Sin el acceso especial, el portal no muestra el bloque.
    if (!tieneAccesoEspecial($conn, $noEmpleado, 'verResumenMensual')) {
        echo json_encode(['status' => 'no_disponible']);
        exit;
    }
    $idVeh = intval($_POST['id_vehiculo'] ?? 0);
    if ($idVeh <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Vehículo inválido.']);
        exit;
    }
    if (!puedeVerVehiculo($conn, $noEmpleado, $idVeh)) {
        echo json_encode(['status' => 'error', 'message' => 'No tienes acceso a este vehículo.']);
        exit;
    }

    $mes = mesPedido((string) ($_POST['mes'] ?? ''));
    asegurarMesGenerado($conn, $mes, [$idVeh]);
    $fila = filasMes($conn, $mes, [$idVeh])[$idVeh] ?? null;
    if (!$fila) {
        echo json_encode(['status' => 'error', 'message' => 'Vehículo no encontrado o dado de baja.']);
        exit;
    }

    echo json_encode([
        'status'      => 'success',
        'mes'         => [
            'mes'       => $mes['clave'],
            'inicio'    => $mes['desde'],
            'fin'       => $mes['fin'],
            'anterior'  => $mes['anterior'],
            'siguiente' => $mes['siguiente'],   // null en el mes pasado (el último cerrado)
        ],
        'actualizado' => $fila['actualizado'],
        'datos'       => $fila['datos'],
        'analisis'    => $fila['analisis'],
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Acción no reconocida.']);
