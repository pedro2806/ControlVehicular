<?php
include 'conn.php';
header('Content-Type: application/json');
mysqli_set_charset($conn, "utf8mb4");
date_default_timezone_set('America/Mexico_City');

$accion = $_POST['accion'] ?? '';

// Obtener datos completos del vehículo para la vista QR
if ($accion === 'obtenerDatosVehiculo') {
    $id_vehiculo = intval($_POST['id_vehiculo'] ?? 0);
    if (!$id_vehiculo) {
        echo json_encode(['error' => 'ID de vehículo inválido.']);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT i.id_vehiculo, i.placa, i.modelo, i.marca, i.anio, i.color,
               i.usuario, i.area, i.foto_general,
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
    $noEmpleado = intval($_COOKIE['noEmpleado'] ?? 0);
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

echo json_encode(['error' => 'Acción no reconocida.']);
