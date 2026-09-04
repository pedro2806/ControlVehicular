<?php
include __DIR__ . '/../conn.php';
header('Content-Type: application/json');
mysqli_set_charset($conn, "utf8mb4");
date_default_timezone_set('America/Mexico_City');

/**
 * Un error fatal aquí debe salir como JSON, no como un 500 vacío.
 *
 * Estos endpoints prometen JSON, pero ante un fatal (función inexistente, memoria
 * agotada, excepción sin capturar) PHP responde 500 con cuerpo HTML —o vacío, porque en
 * producción display_errors está apagado—. Del lado del cliente eso solo se veía como
 * "No se pudo completar la solicitud", sin ninguna pista: así se perdieron días
 * persiguiendo el fallo de las fotos del checklist.
 *
 * Con esto, el motivo real viaja al navegador y además queda en el log del servidor.
 */
function responderFatalComoJson($mensaje, $detalle = '') {
    if (headers_sent()) return;
    http_response_code(200);   // el cliente ya distingue el fallo por el JSON, no por el código
    header('Content-Type: application/json');
    // 'message' va además de 'error' porque cada endpoint tiene su propia convención y
    // varias vistas leen respuesta.message: así el aviso se ve venga de donde venga.
    echo json_encode([
        'success' => false,
        'error'   => $mensaje,
        'message' => $mensaje,
        'detalle' => $detalle
    ]);
}

set_exception_handler(function ($e) {
    error_log('API fatal (excepción): ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    responderFatalComoJson(
        'El servidor no pudo procesar la petición.',
        $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')'
    );
});

register_shutdown_function(function () {
    $err = error_get_last();
    if (!$err || !in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        return;
    }
    error_log('API fatal: ' . $err['message'] . ' en ' . $err['file'] . ':' . $err['line']);
    responderFatalComoJson(
        'El servidor no pudo procesar la petición.',
        $err['message'] . ' (' . basename($err['file']) . ':' . $err['line'] . ')'
    );
});

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
    //
    // fecha_carga ya es DATETIME, así que las cargas NUEVAS se ordenan por su hora real.
    // Pero las que existían antes de la migración quedaron todas en 00:00:00, porque esa
    // hora nunca se guardó (el formulario la capturaba y el servidor la recortaba). Para
    // ésas, cualquier check-in del mismo día les ganaba aunque hubiera ocurrido antes:
    // se registraban dos cargas seguidas (km 1000 y luego 1010) y la siguiente captura
    // seguía proponiendo 1000, el km del check-in de esa mañana.
    //
    // El CASE cubre los dos mundos y por eso se conserva tras la migración:
    //   fila vieja  -> fecha_carga es medianoche, así que DATE(fecha_registro) coincide
    //                  con ella y se usa fecha_registro, que sí trae hora
    //   fila nueva  -> fecha_carga trae hora propia, no coincide con la medianoche que
    //                  resulta de DATE(fecha_registro), y se usa fecha_carga: la buena
    //   carga vieja en lote -> fecha_registro es de otro día, se queda fecha_carga
    $stmt = $conn->prepare(
        "SELECT km FROM (
             SELECT km_actual AS km, fecha_actividad AS f
               FROM actividad_vehiculo WHERE id_vehiculo = ? AND km_actual > 0
             UNION ALL
             SELECT km_actual AS km,
                    CASE WHEN DATE(fecha_registro) = fecha_carga THEN fecha_registro
                         ELSE CAST(fecha_carga AS DATETIME) END AS f
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

/**
 * Saldo vigente de la tarjeta de gasolina de un vehículo.
 *
 * El saldo NO se puede leer de la última fila de carga_gasolina: una reposición de
 * crédito aprobada abre un ciclo nuevo en ciclos_tarjeta y NO inserta carga (no hubo
 * carga física de combustible). Quien mire solo carga_gasolina sigue viendo el saldo
 * agotado del ciclo anterior, que es justo lo que se reportó en pruebas: se aprueba la
 * reposición y la pantalla —y el monto que se prellena en la siguiente carga— siguen
 * mostrando el crédito viejo.
 *
 * Por eso el saldo sale del ciclo ABIERTO: la última carga de ESE ciclo, o su
 * saldo_inicial si todavía no tiene cargas. Es la misma regla que ya aplicaba
 * resolverCicloParaCarga() en acciones_gas.php para decidir a qué ciclo pertenece una
 * carga nueva; aquí se centraliza para que lectura y escritura no diverjan.
 *
 * @return float|null null si el vehículo no tiene ningún ciclo abierto (nunca se le ha
 *                    registrado crédito); quien llama decide el valor por defecto.
 */
function saldoActualTarjeta($conn, $id_vehiculo) {
    $id = intval($id_vehiculo);
    if ($id <= 0) return null;

    $stmtCiclo = $conn->prepare(
        "SELECT id_ciclo, saldo_inicial FROM ciclos_tarjeta
         WHERE id_vehiculo = ? AND estatus = 'ABIERTO'
         ORDER BY fecha_inicio DESC, id_ciclo DESC LIMIT 1"
    );
    if (!$stmtCiclo) { error_log("saldoActualTarjeta: prepare ciclo falló - " . $conn->error); return null; }
    $stmtCiclo->bind_param("i", $id);
    $stmtCiclo->execute();
    $ciclo = $stmtCiclo->get_result()->fetch_assoc();
    $stmtCiclo->close();

    if (!$ciclo) return null;

    $stmtCarga = $conn->prepare(
        "SELECT saldo FROM carga_gasolina WHERE id_ciclo = ?
         ORDER BY fecha_carga DESC, id DESC LIMIT 1"
    );
    if (!$stmtCarga) { error_log("saldoActualTarjeta: prepare carga falló - " . $conn->error); return (float) $ciclo['saldo_inicial']; }
    $stmtCarga->bind_param("i", $ciclo['id_ciclo']);
    $stmtCarga->execute();
    $ultima = $stmtCarga->get_result()->fetch_assoc();
    $stmtCarga->close();

    return $ultima ? (float) $ultima['saldo'] : (float) $ciclo['saldo_inicial'];
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