<?php
include 'conn.php';
header('Content-Type: application/json');
mysqli_set_charset($conn, "utf8mb4");
date_default_timezone_set('America/Mexico_City');

$accion = $_POST['accion'] ?? '';
    $id_vehiculo = intval($_POST['id_vehiculo'] ?? 0);
    $km_actual   = intval($_POST['km_actual'] ?? 0);
    $notas        = trim($_POST['notas'] ?? '');
    $coordenadas  = trim($_POST['coordenadas'] ?? '');
    $noEmpleado   = intval($_COOKIE['noEmpleado'] ?? 0);

// Obtener datos completos del vehículo para la vista QR
if ($accion === 'obtenerDatosVehiculo') {
    if (!$id_vehiculo) {
        echo json_encode(['error' => 'ID de vehículo inválido.']);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT i.id_vehiculo, i.placa, i.modelo, i.marca, i.anio, i.color,
               i.usuario, i.area, i.foto_general,
               i.id_usuario, i.id_us_asignado,
               c.id_checklist,
               c.estatus  AS estatus_checklist,
               c.fecha    AS fecha_checklist,
               d.fecha_prox,
               DATE(d.fecha_registro) AS fecha_reg_doc,
               d.licencia, d.tarjeta_circulacion, d.refrendo_actual,
               d.seguro_vehiculo, d.verificacion_vigente,
               m.VoBo_jefe AS estatus_mant,
               DATE(m.fecha_registro) AS fecha_mant
        FROM inventario i
        LEFT JOIN checklist c ON c.id_checklist = (
            SELECT id_checklist FROM checklist
            WHERE id_vehiculo = i.id_vehiculo
            ORDER BY fecha DESC, id_checklist DESC
            LIMIT 1
        )
        LEFT JOIN documentacion d ON d.id = (
            SELECT id FROM documentacion
            WHERE id_vehiculo = i.id_vehiculo
            ORDER BY fecha_registro DESC
            LIMIT 1
        )
        LEFT JOIN mantenimientos m ON m.id_mantenimiento = (
            SELECT id_mantenimiento FROM mantenimientos
            WHERE id_vehiculo = i.id_vehiculo
            ORDER BY fecha_registro DESC
            LIMIT 1
        )
        WHERE i.id_vehiculo = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $id_vehiculo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['error' => 'Vehículo no encontrado.']);
        exit;
    }

    echo json_encode($result->fetch_assoc());
    $stmt->close();
    exit;
}

// Verificar si el usuario tiene acceso al módulo QR
if ($accion === 'verificarAccesoQR') {
    $stmt = $conn->prepare(
        "SELECT 1 FROM mess_rrhh.accesos_especiales
         WHERE noEmpleado = ? AND sistema = 'ctrlVehicular' AND opcion = 'verQR' AND estatus = 1
         LIMIT 1"
    );
    $tieneAcceso = false;
    if ($stmt) {
        $stmt->bind_param("i", $noEmpleado);
        $stmt->execute();
        $tieneAcceso = (bool) $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
    echo json_encode(['tieneAcceso' => $tieneAcceso]);
    exit;
}

// Verificar si el usuario tiene check-in activo para este vehículo
if ($accion === 'verificarEstadoCheckin') {
    $id_usuario_cookie = intval($_COOKIE['id_usuario'] ?? 0);

    $stmt = $conn->prepare("
        SELECT id_actividad FROM actividad_vehiculo
        WHERE id_usuario = ? AND id_vehiculo = ?
          AND fecha_actividad = (
              SELECT MAX(fecha_actividad)
              FROM actividad_vehiculo
              WHERE id_usuario = ? AND id_vehiculo = ?
          )
          AND tipo_actividad = 'INICIO'
        LIMIT 1
    ");
    $stmt->bind_param("iiii", $id_usuario_cookie, $id_vehiculo, $id_usuario_cookie, $id_vehiculo);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Primer registro de KM en la semana actual para este vehículo (cualquier usuario)
    $stmtKm = $conn->prepare("
        SELECT 1 FROM actividad_vehiculo
        WHERE id_vehiculo = ? AND km_actual > 0
          AND DATE(fecha_actividad) IN (
              SELECT FECHA FROM dimtiempo
              WHERE SEMANA = (
                  SELECT SEMANA FROM dimtiempo WHERE FECHA = CURDATE()
              )
          )
        LIMIT 1
    ");
    $stmtKm->bind_param("i", $id_vehiculo);
    $stmtKm->execute();
    $hayKMEstaSemana = (bool) $stmtKm->get_result()->fetch_assoc();
    $stmtKm->close();

    echo json_encode([
        'tieneCheckinActivo'  => (bool)$row,
        'id_actividad'        => $row ? intval($row['id_actividad']) : null,
        'primerKMDeLaSemana'  => !$hayKMEstaSemana
    ]);
    exit;
}

// Helpers internos para check-in/out QR
function obtenerPlacaVehiculo($conn, $id_vehiculo) {
    $stmt = $conn->prepare("SELECT placa FROM inventario WHERE id_vehiculo = ? LIMIT 1");
    $stmt->bind_param("i", $id_vehiculo);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row['placa'] ?? 'SIN_PLACA';
}

function subirFotoKM($placa, $prefijo) {
    if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) return null;
    $carpeta = "img_control_vehicular/$placa/km";
    if (!file_exists($carpeta)) mkdir($carpeta, 0777, true);
    $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
    $nombre = $placa . '_' . $prefijo . '_' . date('Ymd_His') . '.' . $ext;
    $ruta = "$carpeta/$nombre";
    move_uploaded_file($_FILES['foto']['tmp_name'], $ruta);
    return $ruta;
}

// Check-In desde QR
if ($accion === 'checkInQR') {
    if (!$id_vehiculo) { echo json_encode(['error' => 'ID inválido.']); exit; }
    $id_usuario_cookie = intval($_COOKIE['id_usuario'] ?? 0);
    $ot        = trim($_POST['ot'] ?? '');
    $placa     = obtenerPlacaVehiculo($conn, $id_vehiculo);
    $ruta_foto = subirFotoKM($placa, 'checkin');

    $stmt = $conn->prepare("INSERT INTO actividad_vehiculo (id_vehiculo, id_usuario, km_actual, foto_url, coordenadas, ot, fecha_actividad, tipo_actividad) VALUES (?, ?, ?, ?, ?, ?, NOW(), 'INICIO')");
    $stmt->bind_param("iiisss", $id_vehiculo, $id_usuario_cookie, $km_actual, $ruta_foto, $coordenadas, $ot);

    if ($stmt->execute()) {
        $conn->query("UPDATE inventario SET asignado = 'SI' WHERE id_vehiculo = " . $id_vehiculo);
        if ($km_actual > 0) {
            $stmtKm = $conn->prepare("INSERT INTO kilometrajes (id_vehiculo, km, fecha) VALUES (?, ?, NOW())");
            $stmtKm->bind_param("ii", $id_vehiculo, $km_actual);
            $stmtKm->execute();
            $stmtKm->close();
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Error al registrar check-in.']);
    }
    $stmt->close();
    exit;
}

// Check-Out desde QR
if ($accion === 'checkOutQR') {
    if (!$id_vehiculo) { echo json_encode(['error' => 'ID inválido.']); exit; }
    $id_usuario_cookie = intval($_COOKIE['id_usuario'] ?? 0);
    $ot        = trim($_POST['ot'] ?? '');
    $placa     = obtenerPlacaVehiculo($conn, $id_vehiculo);
    $ruta_foto = subirFotoKM($placa, 'checkout');

    $stmt = $conn->prepare("INSERT INTO actividad_vehiculo (id_vehiculo, id_usuario, km_actual, foto_url, coordenadas, ot, fecha_actividad, tipo_actividad) VALUES (?, ?, ?, ?, ?, ?, NOW(), 'FINALIZACION')");
    $stmt->bind_param("iiisss", $id_vehiculo, $id_usuario_cookie, $km_actual, $ruta_foto, $coordenadas, $ot);

    if ($stmt->execute()) {
        $conn->query("UPDATE inventario SET asignado = 'NO' WHERE id_vehiculo = " . $id_vehiculo);
        $conn->query("UPDATE prestamos SET estatus = 'FINALIZADO', fecha_fin_prestamo = NOW() WHERE id_usuario = " . $id_usuario_cookie . " AND id_vehiculo = " . $id_vehiculo . " AND estatus = 'EN CURSO'");
        if ($km_actual > 0) {
            $stmtKm = $conn->prepare("INSERT INTO kilometrajes (id_vehiculo, km, fecha) VALUES (?, ?, NOW())");
            $stmtKm->bind_param("ii", $id_vehiculo, $km_actual);
            $stmtKm->execute();
            $stmtKm->close();
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Error al registrar check-out.']);
    }
    $stmt->close();
    exit;
}

// Registrar préstamo automático al escanear QR si el vehículo no es del usuario
if ($accion === 'registrarPrestamoQR') {
    if (!$id_vehiculo) {
        echo json_encode(['error' => 'ID inválido.']);
        exit;
    }

    $id_usuario_cookie = intval($_COOKIE['id_usuario'] ?? 0);

    // Verificar si el vehículo es propio (dueño principal o asignado)
    $stmt = $conn->prepare(
        "SELECT id_vehiculo FROM inventario
         WHERE id_vehiculo = ? AND (id_usuario = ? OR id_us_asignado = ?) LIMIT 1"
    );
    $stmt->bind_param("iii", $id_vehiculo, $id_usuario_cookie, $id_usuario_cookie);
    $stmt->execute();
    $esPropio = (bool) $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($esPropio) {
        echo json_encode(['success' => true, 'necesitoPrestamo' => false]);
        exit;
    }

    // Verificar si ya existe un préstamo EN CURSO para este usuario+vehículo
    $stmt = $conn->prepare(
        "SELECT id_prestamo FROM prestamos
         WHERE id_usuario = ? AND id_vehiculo = ? AND estatus = 'EN CURSO' LIMIT 1"
    );
    $stmt->bind_param("ii", $id_usuario_cookie, $id_vehiculo);
    $stmt->execute();
    $existente = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existente) {
        echo json_encode(['success' => true, 'necesitoPrestamo' => true, 'yaTenia' => true]);
        exit;
    }

    // Crear préstamo automático EN CURSO
    $stmt = $conn->prepare(
        "INSERT INTO prestamos
         (fecha_registro, id_usuario, fecha_inc_prestamo, fecha_fin_prestamo, estatus, motivo_us, tipo_uso, detalle_tipo_uso, destino, id_vehiculo)
         VALUES (NOW(), ?, NOW(), DATE_ADD(NOW(), INTERVAL 1 DAY), 'EN CURSO', 'Escaneo QR', 'Visita Cliente QR', '', '', ?)"
    );
    $stmt->bind_param("ii", $id_usuario_cookie, $id_vehiculo);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'necesitoPrestamo' => true, 'creado' => true]);
    } else {
        echo json_encode(['error' => 'No se pudo registrar el préstamo: ' . $conn->error]);
    }
    $stmt->close();
    exit;
}

// Registrar lectura semanal de odómetro (solo lunes)
if ($accion === 'registrarKMSemanal') {
    if (!$id_vehiculo) {
        echo json_encode(['error' => 'Datos incompletos.']);
        exit;
    }

    // Obtener placa para la ruta de la foto
    $stmt = $conn->prepare("SELECT placa FROM inventario WHERE id_vehiculo = ? LIMIT 1");
    $stmt->bind_param("i", $id_vehiculo);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $placa = $row['placa'] ?? 'SIN_PLACA';

    $ruta_foto = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $carpeta = "img_control_vehicular/$placa/km";
        if (!file_exists($carpeta)) {
            mkdir($carpeta, 0777, true);
        }
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $nombre = $placa . '_km_' . date('Ymd_His') . '.' . $ext;
        $ruta_foto = "$carpeta/$nombre";
        move_uploaded_file($_FILES['foto']['tmp_name'], $ruta_foto);
    }

    $id_usuario = intval($_COOKIE['id_usuarioL'] ?? 0);
    $stmt = $conn->prepare(
        "INSERT INTO actividad_vehiculo (id_vehiculo, id_usuario, km_actual, foto_url, fecha_actividad, tipo_actividad, notas)
         VALUES (?, ?, ?, ?, NOW(), 'KM_SEMANAL', ?)"
    );
    $stmt->bind_param("iiiss", $id_vehiculo, $id_usuario, $km_actual, $ruta_foto, $notas);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'No se pudo guardar el registro.']);
    }
    $stmt->close();
    exit;
}

echo json_encode(['error' => 'Acción no reconocida.']);
