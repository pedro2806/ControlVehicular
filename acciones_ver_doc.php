<?php
include 'conn.php';
header('Content-Type: application/json');
mysqli_set_charset($conn, "utf8mb4");
date_default_timezone_set('America/Mexico_City');

$accion = $_POST["accion"];

$id_vehiculo = $_POST["id_vehiculo"];
$fecha_registro = date("Y-m-d H:i:s");
$id_dueno = $_POST["id_dueno"];
$noEmpleado = $_COOKIE['noEmpleado'];
$id_usuario = $_COOKIE['id_usuario'];
$foto = $_POST["rutaImagen"];
$placa = $_POST["placa"];

/*---------------------------------------------*/
// Consulta para obtener los vehículos del inventario
if ($_POST['accion'] == 'ver_inventario') {
    $sql = "SELECT id_vehiculo, placa, modelo, marca, anio, usuario 
            FROM inventario";
    $result = $conn->query($sql);

    $data = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'id_vehiculo' => $row['id_vehiculo'],
                'placa' => $row['placa'],
                'modelo' => $row['modelo'],
                'marca' => $row['marca'],
                'anio' => $row['anio'],
                'usuario' => $row['usuario'] ?? 'Sin asignar',
            ];
        }
    }
    echo json_encode($data);
    exit;
}

// Consulta para obtener los documentos del vehículo
if ($_POST['accion'] == 'documentosVehiculo') {
    $sql = "SELECT inv.placa, inv.modelo, inv.marca, inv.anio, inv.usuario, doc.licencia, doc.tarjeta_circulacion, doc.refrendo_actual, doc.seguro_vehiculo, doc.verificacion_vigente
            FROM inventario inv
            LEFT JOIN documentacion doc ON inv.id_vehiculo = doc.id_vehiculo
            WHERE inv.id_vehiculo = $id_vehiculo
            ORDER BY doc.fecha_registro DESC
            LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $vehiculo = $result->fetch_assoc();
        echo json_encode($vehiculo);
    } else {
        echo json_encode(["error" => "No se encontró información para la placa proporcionada."]);
    }
    exit;
}

// Consulta para obtener los mantenimiento del vehículo 
if ($_POST['accion'] == 'mantenimientoVehiculo') {
    $sql = "SELECT m.fecha_registro, m.tipo_mantenimiento, m.descripcion, m.foto, 
                   inv.placa, inv.modelo, inv.marca, inv.anio, inv.usuario
            FROM mantenimientos m
            JOIN inventario inv ON m.id_vehiculo = inv.id_vehiculo
            WHERE m.id_vehiculo = $id_vehiculo
            ORDER BY m.fecha_registro DESC
            LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $mantenimiento = $result->fetch_assoc();
        echo json_encode($mantenimiento);
    } else {
        echo json_encode(["error" => "No se encontró información de mantenimiento."]);
    }
    exit;
}

// Consulta para obtener los siniestros del vehículo 
if ($_POST['accion'] == 'siniestrosVehiculo') {
    $sql = "SELECT s.fecha_registro, s.descripcion, f.imagen
            FROM siniestros s
            JOIN fotos f ON s.id_vehiculo = f.id_vehiculo
            WHERE s.id_vehiculo = $id_vehiculo
            ORDER BY s.fecha_registro DESC
            LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $siniestro = $result->fetch_assoc();
        echo json_encode($siniestro);
    } else {
        echo json_encode(["error" => "No se encontró información de siniestros."]);
    }
    exit;
}
?>