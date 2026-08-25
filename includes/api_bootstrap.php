<?php
include __DIR__ . '/../conn.php';
header('Content-Type: application/json');
mysqli_set_charset($conn, "utf8mb4");
date_default_timezone_set('America/Mexico_City');

// Normalización de $_COOKIE['id_usuario'] (ver includes/sesion_cookies.php). Está en un
// archivo aparte porque las VISTAS también la necesitan y no pueden incluir este
// bootstrap, que manda cabeceras JSON.
require_once __DIR__ . '/sesion_cookies.php';

/**
 * Consulta un acceso especial de ctrlVehicular para un empleado.
 *
 * Se resuelve SIEMPRE en el servidor. Ojo: la cookie `rol` la escribe el cliente sin
 * firma (index.php / validaLoginMaster.php la ponen con document.cookie), así que no
 * sirve como barrera de permisos — cualquiera puede editarla en el navegador. Todo
 * permiso nuevo debe pasar por aquí.
 *
 * @return array{tiene: bool, inf_adicional: ?string}
 *         inf_adicional se normaliza a null cuando viene vacío o '-' (= sin filtro).
 */
function accesoEspecial($conn, $noEmpleado, $opcion) {
    $vacio = ['tiene' => false, 'inf_adicional' => null];
    $ne = intval($noEmpleado);
    if ($ne <= 0) return $vacio;

    $stmt = $conn->prepare(
        "SELECT inf_adicional FROM mess_rrhh.accesos_especiales
         WHERE noEmpleado = ? AND sistema = 'ctrlVehicular' AND opcion = ? AND estatus = 1
         LIMIT 1"
    );
    if (!$stmt) { error_log("accesoEspecial: prepare falló - " . $conn->error); return $vacio; }
    $stmt->bind_param("is", $ne, $opcion);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) return $vacio;

    $inf = trim((string) ($row['inf_adicional'] ?? ''));
    return ['tiene' => true, 'inf_adicional' => ($inf === '' || $inf === '-') ? null : $inf];
}

/** Atajo cuando solo importa si tiene el acceso, sin el filtro de inf_adicional. */
function tieneAccesoEspecial($conn, $noEmpleado, $opcion) {
    return accesoEspecial($conn, $noEmpleado, $opcion)['tiene'];
}

/**
 * Última lectura de odómetro del vehículo, mirando TODAS las fuentes.
 *
 * El kilometraje se captura en tres lados distintos y antes cada consumidor miraba un
 * subconjunto diferente: el modal de gasolina solo veía actividad_vehiculo, el del QR
 * veía kilometrajes + actividad_vehiculo, y el de gas solo carga_gasolina. Resultado:
 * el mismo vehículo mostraba kilometrajes distintos según por dónde entraras, y en los
 * vehículos que solo tienen cargas de gasolina el campo salía vacío.
 *
 * Devuelve la lectura MÁS RECIENTE, no la mayor. Con MAX un dedazo de captura (hay un
 * 1681614 entre lecturas de ~169000) se quedaba pegado para siempre y además bloqueaba
 * la validación de "el km no puede ser menor al último".
 *
 * kilometrajes hoy está vacía; se incluye para el día que se empiece a poblar.
 *
 * @return int 0 si el vehículo no tiene ninguna lectura.
 */
function obtenerUltimoKM($conn, $id_vehiculo) {
    $id = intval($id_vehiculo);
    if ($id <= 0) return 0;

    // fecha_carga y no fecha_registro para carga_gasolina: la captura en lote deja
    // varias cargas registradas el mismo minuto, y la fecha de carga es cuándo se leyó
    // de verdad el odómetro.
    $stmt = $conn->prepare(
        "SELECT km FROM (
             SELECT km_actual AS km, fecha_actividad AS f
               FROM actividad_vehiculo WHERE id_vehiculo = ? AND km_actual > 0
             UNION ALL
             SELECT km_actual AS km, fecha_carga AS f
               FROM carga_gasolina    WHERE id_vehiculo = ? AND km_actual > 0
             UNION ALL
             SELECT km AS km, fecha AS f
               FROM kilometrajes      WHERE id_vehiculo = ? AND km > 0
         ) t
         ORDER BY t.f DESC, t.km DESC
         LIMIT 1"
    );
    if (!$stmt) { error_log("obtenerUltimoKM: prepare falló - " . $conn->error); return 0; }
    $stmt->bind_param("iii", $id, $id, $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return intval($row['km'] ?? 0);
}

$tieneVehiculo = false;
if (!empty($_COOKIE['noEmpleadoL'])) {
    $connCV = new mysqli("localhost", "mess_incidencias", "Pipmytrade123", "mess_control_vehicular");
    if (!$connCV->connect_error) {
        $noEmpVeh = intval($_COOKIE['noEmpleadoL']);
        $stmtVeh = $connCV->prepare(
            "SELECT 1 FROM usuarios u
             WHERE u.noEmpleado = ?
               AND (
                 EXISTS (SELECT 1 FROM inventario i WHERE i.id_usuario = u.id_usuario OR i.id_us_asignado = u.id_usuario)
                 OR EXISTS (SELECT 1 FROM prestamos p WHERE p.id_usuario = u.id_usuario AND p.estatus IN ('AUTORIZADO','EN CURSO'))
               )
             LIMIT 1"
        );
        $stmtVeh->bind_param("i", $noEmpVeh);
        $stmtVeh->execute();
        $tieneVehiculo = (bool) $stmtVeh->get_result()->fetch_assoc();
        $stmtVeh->close();
        $connCV->close();
    }
}