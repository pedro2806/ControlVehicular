<?php
include 'includes/api_bootstrap.php';

$accion      = $_POST['accion'] ?? '';
$id_usuario  = intval($_COOKIE['id_usuario'] ?? 0);
$noEmpleado  = $_COOKIE['noEmpleado'] ?? 0;

/**
 * Áreas válidas. Es un catálogo cerrado: la columna es texto libre y sin esta lista
 * cada alta podría inventar una variante ("BAJIO", "Bajío", "bajio") que rompería los
 * filtros por área que ya existen en mantenimiento y reportes.
 */
const AREAS_VEHICULO = ['AGS', 'BAJIO', 'NTE', 'OCC', 'SLP'];

/** ¿Puede dar de alta, editar y dar de baja vehículos? */
function puedeAdministrarVehiculos($conn, $noEmpleado) {
    return tieneAccesoEspecial($conn, $noEmpleado, 'administrarVehiculos');
}

/**
 * ¿Puede consultar el historial de este vehículo?
 *
 * Salvaguarda anti-IDOR: sin esto bastaría cambiar el ?v= de la URL para leer el
 * historial completo de cualquier auto de la empresa. Pasan quienes ven toda la flota
 * y quien trae el vehículo (dueño, asignado o con préstamo vigente).
 */
function puedeVerHistorialVehiculo($conn, $noEmpleado, $id_usuario, $id_vehiculo) {
    if (tieneAccesoEspecial($conn, $noEmpleado, 'verTodosVehiculo')) return true;
    if (puedeAdministrarVehiculos($conn, $noEmpleado)) return true;
    if ($id_usuario <= 0) return false;

    $stmt = $conn->prepare(
        "SELECT 1 FROM inventario i
          WHERE i.id_vehiculo = ? AND (i.id_usuario = ? OR i.id_us_asignado = ?)
          LIMIT 1"
    );
    $stmt->bind_param("iii", $id_vehiculo, $id_usuario, $id_usuario);
    $stmt->execute();
    $suyo = (bool) $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($suyo) return true;

    $stmt = $conn->prepare(
        "SELECT 1 FROM prestamos
          WHERE id_vehiculo = ? AND id_usuario = ? AND estatus IN ('AUTORIZADO','EN CURSO')
          LIMIT 1"
    );
    $stmt->bind_param("ii", $id_vehiculo, $id_usuario);
    $stmt->execute();
    $prestado = (bool) $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $prestado;
}

// ============================== LISTADO ==============================

/** Lista para la pantalla de administración. filtro: activos | inactivos | todos. */
if ($accion === 'listarVehiculos') {
    if (!puedeAdministrarVehiculos($conn, $noEmpleado)) {
        echo json_encode(['status' => 'error', 'message' => 'No tienes acceso a esta vista.']);
        exit;
    }

    $filtro = $_POST['filtro'] ?? 'activos';
    // MySQL ignora los espacios finales al comparar, así que 'Activo' empareja con los
    // registros viejos que traen 'Activo ' con espacio. No hay que replicar ese espacio.
    $where = $filtro === 'inactivos' ? "WHERE i.estatus = 'Inactivo'"
           : ($filtro === 'todos'    ? "" : "WHERE i.estatus = 'Activo'");

    // El JOIN a usuarios empareja con la fila de MAYOR id: usuarios.id_usuario NO es
    // único y un JOIN directo duplicaría vehículos en la lista.
    $sql = "SELECT i.id_vehiculo, i.placa, i.marca, i.modelo, i.anio, i.color, i.area,
                   i.estatus, i.efecticard, i.km_mantenimiento, i.fecha_registro,
                   i.id_usuario, i.id_us_asignado, i.usuario,
                   u.nombre AS nombre_usuario
            FROM inventario i
            LEFT JOIN usuarios u ON u.id = (SELECT MAX(u2.id) FROM usuarios u2 WHERE u2.id_usuario = i.id_usuario)
            $where
            ORDER BY i.placa";

    $res = $conn->query($sql);
    $lista = [];
    if ($res) { while ($row = $res->fetch_assoc()) { $lista[] = $row; } }

    echo json_encode(['status' => 'success', 'areas' => AREAS_VEHICULO, 'vehiculos' => $lista]);
    exit;
}

/**
 * Usuarios a los que se les puede asignar un vehículo.
 *
 * NO se reutiliza consultarUsuariosRecibe de préstamos: esa devuelve solo jefes
 * (rol = 3), 32 de casi 300, y a un vehículo se le asigna cualquier empleado. Con esa
 * lista el select no podía ni preseleccionar al dueño actual y mostraba "Sin asignar".
 *
 * Se agrupa por id_usuario tomando la fila de mayor id: usuarios.id_usuario NO es único
 * y sin esto la misma persona saldría repetida en el select.
 */
if ($accion === 'usuariosAsignables') {
    if (!puedeAdministrarVehiculos($conn, $noEmpleado)) {
        echo json_encode(['status' => 'error', 'message' => 'No tienes acceso a esta vista.']);
        exit;
    }

    $res = $conn->query(
        "SELECT u.id_usuario, u.nombre
         FROM usuarios u
         WHERE u.id = (SELECT MAX(u2.id) FROM usuarios u2 WHERE u2.id_usuario = u.id_usuario)
           AND u.id_usuario IS NOT NULL AND u.id_usuario > 0
           AND TRIM(IFNULL(u.nombre,'')) <> ''
         ORDER BY u.nombre"
    );
    $lista = [];
    if ($res) { while ($row = $res->fetch_assoc()) { $lista[] = $row; } }

    echo json_encode(['status' => 'success', 'usuarios' => $lista]);
    exit;
}

// ============================== ALTA / EDICIÓN ==============================

if ($accion === 'guardarVehiculo') {
    if (!puedeAdministrarVehiculos($conn, $noEmpleado)) {
        echo json_encode(['status' => 'error', 'message' => 'No tienes permiso para dar de alta vehículos.']);
        exit;
    }

    $idVeh  = intval($_POST['id_vehiculo'] ?? 0);   // 0 = alta nueva
    // La placa se normaliza a mayúsculas y sin espacios: el catálogo tiene registros con
    // saltos de línea y espacios metidos por copiar y pegar, y eso rompe las búsquedas.
    $placa  = strtoupper(preg_replace('/\s+/', '', $_POST['placa'] ?? ''));
    $marca  = trim($_POST['marca'] ?? '');
    $modelo = trim($_POST['modelo'] ?? '');
    $color  = trim($_POST['color'] ?? '');
    $area   = trim($_POST['area'] ?? '');
    $anio   = intval($_POST['anio'] ?? 0);
    $kmMant = intval($_POST['km_mantenimiento'] ?? 0);
    $idUsr  = intval($_POST['id_usuario'] ?? 0);
    $efecti = ($_POST['efecticard'] ?? '') !== '' ? intval($_POST['efecticard']) : null;

    if ($placa === '' || $marca === '' || $modelo === '') {
        echo json_encode(['status' => 'error', 'message' => 'Placa, marca y modelo son obligatorios.']);
        exit;
    }
    // El año se valida contra un rango real y no solo "que sea número": un dedazo de
    // 4 dígitos pasa cualquier validación de tipo.
    if ($anio < 1990 || $anio > intval(date('Y')) + 2) {
        echo json_encode(['status' => 'error', 'message' => 'El año no es válido.']);
        exit;
    }
    if ($area !== '' && !in_array($area, AREAS_VEHICULO, true)) {
        echo json_encode(['status' => 'error', 'message' => 'El área no es válida.']);
        exit;
    }

    // Placa única. Se revisa aquí porque la tabla no tiene índice único y dos vehículos
    // con la misma placa harían ambiguo todo el historial.
    $stmt = $conn->prepare("SELECT id_vehiculo FROM inventario
                            WHERE REPLACE(UPPER(TRIM(placa)),' ','') = ? AND id_vehiculo <> ? LIMIT 1");
    $stmt->bind_param("si", $placa, $idVeh);
    $stmt->execute();
    $dup = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($dup) {
        echo json_encode(['status' => 'error', 'message' => "La placa $placa ya está registrada en otro vehículo."]);
        exit;
    }

    // Nombre del usuario asignado: la tabla guarda el id Y el texto, y varias vistas
    // muestran el texto. Si solo se actualizara el id, quedarían desalineados.
    $nombreUsr = '';
    if ($idUsr > 0) {
        $stmt = $conn->prepare("SELECT nombre FROM usuarios WHERE id = (SELECT MAX(u2.id) FROM usuarios u2 WHERE u2.id_usuario = ?) LIMIT 1");
        $stmt->bind_param("i", $idUsr);
        $stmt->execute();
        $ru = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $nombreUsr = $ru['nombre'] ?? '';
    }

    if ($idVeh > 0) {
        $stmt = $conn->prepare(
            "UPDATE inventario
                SET placa = ?, marca = ?, modelo = ?, color = ?, area = ?, anio = ?,
                    km_mantenimiento = ?, id_usuario = ?, id_us_asignado = ?, usuario = ?, efecticard = ?
              WHERE id_vehiculo = ?"
        );
        $stmt->bind_param("sssssiiiisii", $placa, $marca, $modelo, $color, $area, $anio,
                          $kmMant, $idUsr, $idUsr, $nombreUsr, $efecti, $idVeh);
        $ok = $stmt->execute();
        $stmt->close();
        echo json_encode($ok
            ? ['status' => 'success', 'message' => "Vehículo $placa actualizado.", 'id_vehiculo' => $idVeh]
            : ['status' => 'error', 'message' => 'No se pudo actualizar el vehículo.']);
        exit;
    }

    // 'Activo' sin espacio final: los registros viejos lo traen, pero MySQL lo ignora al
    // comparar y no hay razón para propagar el dato sucio.
    $stmt = $conn->prepare(
        "INSERT INTO inventario
            (placa, marca, modelo, color, area, anio, km_mantenimiento,
             id_usuario, id_us_asignado, usuario, efecticard, estatus, fecha_registro, asignado)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Activo', CURDATE(), ?)"
    );
    // `asignado` es una bandera SI/NO, NO el nombre: una corrección de julio dejó nombres
    // ahí por error y hubo que limpiarlos.
    $flagAsignado = $idUsr > 0 ? 'SI' : 'NO';
    $stmt->bind_param("sssssiiiisis", $placa, $marca, $modelo, $color, $area, $anio,
                      $kmMant, $idUsr, $idUsr, $nombreUsr, $efecti, $flagAsignado);
    $ok = $stmt->execute();
    $nuevo = $conn->insert_id;
    $stmt->close();

    echo json_encode($ok
        ? ['status' => 'success', 'message' => "Vehículo $placa dado de alta.", 'id_vehiculo' => $nuevo]
        : ['status' => 'error', 'message' => 'No se pudo dar de alta el vehículo.']);
    exit;
}

// ============================== BAJA / REACTIVACIÓN ==============================

/**
 * Baja lógica: solo cambia el estatus.
 *
 * NO se borra la fila. Checklists, cargas de gasolina, check-ins, siniestros y préstamos
 * apuntan a este id_vehiculo; borrarlo dejaría todo eso huérfano y sin forma de saber de
 * qué auto hablaba. Un vehículo inactivo desaparece de los selectores pero conserva su
 * historial completo.
 */
if ($accion === 'cambiarEstatusVehiculo') {
    if (!puedeAdministrarVehiculos($conn, $noEmpleado)) {
        echo json_encode(['status' => 'error', 'message' => 'No tienes permiso para dar de baja vehículos.']);
        exit;
    }

    $idVeh   = intval($_POST['id_vehiculo'] ?? 0);
    $nuevo   = strtoupper(trim($_POST['estatus'] ?? ''));
    $motivo  = trim($_POST['motivo'] ?? '');

    if ($idVeh <= 0 || !in_array($nuevo, ['ACTIVO', 'INACTIVO'], true)) {
        echo json_encode(['status' => 'error', 'message' => 'Datos incompletos.']);
        exit;
    }
    // El motivo es obligatorio al dar de baja: sin él, meses después nadie sabe si el auto
    // se vendió, se siniestró o solo se guardó.
    if ($nuevo === 'INACTIVO' && $motivo === '') {
        echo json_encode(['status' => 'error', 'message' => 'Escribe el motivo de la baja.']);
        exit;
    }

    $valor = $nuevo === 'INACTIVO' ? 'Inactivo' : 'Activo';
    $stmt = $conn->prepare("UPDATE inventario SET estatus = ? WHERE id_vehiculo = ?");
    $stmt->bind_param("si", $valor, $idVeh);
    $stmt->execute();
    $cambio = $stmt->affected_rows > 0;
    $stmt->close();

    if (!$cambio) {
        echo json_encode(['status' => 'error', 'message' => 'El vehículo ya estaba en ese estatus.']);
        exit;
    }

    // El motivo se deja en el log del servidor: la tabla no tiene dónde guardarlo y
    // agregarle una columna es un cambio de esquema que no se ha decidido.
    error_log("Vehiculo $idVeh -> $valor por noEmpleado $noEmpleado. Motivo: $motivo");

    echo json_encode(['status' => 'success', 'message' => "Vehículo marcado como $valor."]);
    exit;
}

// ============================== HISTORIAL ==============================

/**
 * Todo lo que se sabe de un vehículo, para la pantalla de historial.
 *
 * Cada bloque se consulta por separado en lugar de armar un JOIN gigante: son relaciones
 * uno-a-muchos independientes y unirlas multiplicaría filas sin control.
 */
if ($accion === 'historialVehiculo') {
    $idVeh = intval($_POST['id_vehiculo'] ?? 0);
    if ($idVeh <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Vehículo inválido.']);
        exit;
    }
    if (!puedeVerHistorialVehiculo($conn, $noEmpleado, $id_usuario, $idVeh)) {
        echo json_encode(['status' => 'error', 'message' => 'No tienes acceso al historial de este vehículo.']);
        exit;
    }

    $out = ['status' => 'success'];

    // --- Datos del vehículo
    $stmt = $conn->prepare(
        "SELECT i.*, u.nombre AS nombre_usuario
         FROM inventario i
         LEFT JOIN usuarios u ON u.id = (SELECT MAX(u2.id) FROM usuarios u2 WHERE u2.id_usuario = i.id_usuario)
         WHERE i.id_vehiculo = ? LIMIT 1"
    );
    $stmt->bind_param("i", $idVeh);
    $stmt->execute();
    $out['vehiculo'] = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$out['vehiculo']) {
        echo json_encode(['status' => 'error', 'message' => 'No se encontró el vehículo.']);
        exit;
    }

    // Cada consulta se acota con LIMIT: un vehículo con años de uso puede tener cientos
    // de check-ins y la pantalla solo muestra los más recientes.
    $bloques = [
        'checklists' => "SELECT c.id_checklist, c.fecha, c.estatus, c.motivo,
                                IFNULL(u.nombre,'S/R') AS usuario
                         FROM checklist c
                         LEFT JOIN usuarios u ON u.id = (SELECT MAX(u2.id) FROM usuarios u2 WHERE u2.id_usuario = c.id_usuario)
                         WHERE c.id_vehiculo = ? ORDER BY c.fecha DESC LIMIT 50",

        'checkins'   => "SELECT a.id_actividad, a.tipo_actividad, a.fecha_actividad, a.km_actual,
                                a.gasolina_actual, a.ot, a.coordenadas, a.notas,
                                IFNULL(u.nombre,'S/R') AS usuario
                         FROM actividad_vehiculo a
                         LEFT JOIN usuarios u ON u.id = (SELECT MAX(u2.id) FROM usuarios u2 WHERE u2.id_usuario = a.id_usuario)
                         WHERE a.id_vehiculo = ? ORDER BY a.fecha_actividad DESC LIMIT 50",

        'cargas'     => "SELECT cg.id, cg.fecha_carga, cg.fecha_registro, cg.monto, cg.pagos, cg.saldo,
                                cg.km_actual, cg.ot, cg.id_ciclo, cli.CLIENTE AS cliente,
                                IFNULL(u.nombre,'S/R') AS usuario
                         FROM carga_gasolina cg
                         LEFT JOIN clientes cli ON cli.IDCLTE = cg.id_cliente
                         LEFT JOIN usuarios u ON u.id = (SELECT MAX(u2.id) FROM usuarios u2 WHERE u2.id_usuario = cg.id_usuario)
                         WHERE cg.id_vehiculo = ? ORDER BY cg.fecha_carga DESC LIMIT 50",

        'solicitudes'=> "SELECT s.id, s.fecha, s.estatus, s.saldo_solicitud, s.km_actual,
                                s.notas_resolucion, s.fecha_resuelto
                         FROM solicitudes_gas s
                         WHERE s.id_vehiculo = ? ORDER BY s.fecha DESC LIMIT 50",

        'mantenimientos' => "SELECT m.id_mantenimiento, m.fecha_registro, m.VoBo_jefe AS estatus,
                                    m.tipo_mantenimiento, m.descripcion, m.kilometraje
                             FROM mantenimientos m
                             WHERE m.id_vehiculo = ? ORDER BY m.fecha_registro DESC LIMIT 50",

        'anomalias'  => "SELECT an.id, an.fecha, an.descripcion, an.foto_ruta, an.coordenadas,
                                IFNULL(u.nombre,'S/R') AS usuario
                         FROM anomalias an
                         LEFT JOIN usuarios u ON u.id = (SELECT MAX(u2.id) FROM usuarios u2 WHERE u2.id_usuario = an.id_usuario)
                         WHERE an.id_vehiculo = ? ORDER BY an.fecha DESC LIMIT 50",

        'prestamos'  => "SELECT p.id_prestamo, p.fecha_registro, p.fecha_inc_prestamo, p.fecha_fin_prestamo,
                                p.estatus, p.tipo_uso, p.motivo_us,
                                IFNULL(u.nombre,'S/R') AS usuario
                         FROM prestamos p
                         LEFT JOIN usuarios u ON u.id = (SELECT MAX(u2.id) FROM usuarios u2 WHERE u2.id_usuario = p.id_usuario)
                         WHERE p.id_vehiculo = ? ORDER BY p.fecha_registro DESC LIMIT 50",
    ];

    foreach ($bloques as $clave => $sql) {
        $out[$clave] = [];
        $stmt = $conn->prepare($sql);
        // Una tabla que no exista todavía (solicitudes_gas antes del despliegue) no debe
        // tumbar toda la pantalla: ese bloque simplemente sale vacío.
        if (!$stmt) { continue; }
        $stmt->bind_param("i", $idVeh);
        $stmt->execute();
        $r = $stmt->get_result();
        while ($row = $r->fetch_assoc()) { $out[$clave][] = $row; }
        $stmt->close();
    }

    // Siniestros va aparte porque su llave es id_siniestro y no sigue el patrón de las
    // demás; además interesa la ubicación para poder situar el evento.
    $out['siniestros'] = [];
    $stmtSin = $conn->prepare(
        "SELECT s.id_siniestro, s.fecha, s.hora, s.lugar, s.descripcion,
                s.coordenadas, s.kilometraje
         FROM siniestros s WHERE s.id_vehiculo = ? ORDER BY s.fecha DESC LIMIT 50"
    );
    if ($stmtSin) {
        $stmtSin->bind_param("i", $idVeh);
        $stmtSin->execute();
        $r = $stmtSin->get_result();
        while ($row = $r->fetch_assoc()) { $out['siniestros'][] = $row; }
        $stmtSin->close();
    }

    echo json_encode($out);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Acción no reconocida.']);
