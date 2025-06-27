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
$estatus = $_POST["estatus"];

/*---------------------------------------------*/
// Consulta para obtener los vehículos del inventario
if ($_POST['accion'] == 'ver_inventario') {
    $id_usuario_cookie = intval($_COOKIE['id_usuario']);
    $sql = "SELECT id_vehiculo, placa, modelo, marca, anio, usuarios.nombre as usuario, color
            FROM inventario 
            INNER JOIN usuarios ON inventario.id_usuario = usuarios.id_usuario     
            WHERE inventario.id_usuario = $id_usuario OR inventario.id_us_asignado = $id_usuario"; 
            
    $result = $conn->query($sql);

    $data = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'id_vehiculo' => $row['id_vehiculo'],
                'placa' => $row['placa'],
                'modelo' => $row['modelo'],
                'marca' => $row['marca'],
                'color' => $row['color'],
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
    $sql = "SELECT inv.placa, inv.modelo, inv.marca, inv.anio, inv.color, inv.usuario, doc.licencia, doc.tarjeta_circulacion, doc.refrendo_actual, doc.seguro_vehiculo, 
                doc.verificacion_vigente, doc.fecha_registro,doc.fecha_prox, doc.contacto
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
    $sql = "SELECT m.fecha_registro, m.tipo_mantenimiento, m.descripcion, m.foto, inv.placa, inv.modelo, inv.marca, inv.anio, inv.color, inv.usuario
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
    $sql="SELECT s.fecha_registro, s.fecha, FORMAT(s.hora, 'HH:mm:ss') AS hora, s.lugar, s.descripcion, s.partes_dañadas, s.ubicacion_vehiculo, f.imagen, 
                inv.placa, inv.modelo, inv.marca, inv.anio, inv.color, inv.usuario
            FROM siniestros s
            LEFT JOIN fotos f ON s.id_siniestro = f.id_formato
            LEFT JOIN inventario inv ON s.id_vehiculo = inv.id_vehiculo
            WHERE s.id_vehiculo = $id_vehiculo
            ORDER BY s.fecha_registro DESC
            LIMIT 4";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $siniestros = [];
        while ($row = $result->fetch_assoc()) {
            $siniestros[] = $row;
        }
        echo json_encode($siniestros);
    } else {
        echo json_encode(["error" => "No se encontró información de siniestros."]);
    }
    exit;
}

//Llena la tabla ver siniestros x vehiculo
if ($_POST['accion'] == 'verSiniestrosXVehiculo') {
    $sql = "SELECT 
                s.*, 
                GROUP_CONCAT(f.imagen) AS imagenes
            FROM siniestros s
            LEFT JOIN fotos f ON s.id_siniestro = f.id_formato
            WHERE s.id_vehiculo = $id_vehiculo
            GROUP BY s.id_siniestro
            ORDER BY s.fecha_registro DESC";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $siniestros = [];
        while ($row = $result->fetch_assoc()) {
            // Convertir las imágenes concatenadas en un array
            $row['imagenes'] = isset($row['imagenes']) ? explode(',', $row['imagenes']) : [];
            $siniestros[] = $row;
        }
        echo json_encode($siniestros);
    } else {
        echo json_encode(["error" => "No se encontró información de siniestros."]);
    }
    exit;
}

// Consulta para obtener los registros de documetnación
if ($_POST['accion'] == 'verDocumentacionXVehiculo') {
    $id_vehiculo = intval($_POST['id_vehiculo']);
    $sql = " SELECT doc.id, doc.id_vehiculo, doc.fecha_registro, doc.id_usuario_registro, doc.contacto, 
                    doc.fecha_prox, doc.licencia, doc.tarjeta_circulacion, doc.refrendo_actual, doc.seguro_vehiculo, doc.verificacion_vigente, 
                    inv.usuario, inv.id_usuario
            FROM documentacion doc
            LEFT JOIN inventario inv ON doc.id_vehiculo = inv.id_vehiculo
            LEFT JOIN usuarios u ON doc.id_usuario_registro = u.id_usuario 
            WHERE doc.id_vehiculo = $id_vehiculo
            ORDER BY doc.fecha_registro DESC";
    $result = $conn->query($sql);

    $documentos = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $documentos[] = $row;
        }
    }
    echo json_encode($documentos);
    exit;
}

// Consulta para obtener los registros de mantenimiento
if ($_POST['accion'] == 'verMantenimientoXVehiculo') {
    $id_vehiculo = intval($_POST['id_vehiculo']);
    $sql = "SELECT mant.*,  inv.usuario, inv.id_us_asignado, mant.VoBo_jefe, inv.placa, inv.modelo, inv.marca, inv.anio, inv.color
            FROM mantenimientos mant
            LEFT JOIN inventario inv ON mant.id_vehiculo = inv.id_vehiculo
            WHERE mant.solicitante = $id_usuario AND mant.VoBo_jefe = '$estatus'
            ORDER BY mant.fecha_registro DESC";
    $result = $conn->query($sql);

    $mantenimientos = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $mantenimientos[] = $row;
        }
    }
    echo json_encode($mantenimientos);
    exit;
}

if($_POST['accion'] == 'mantenimientoRealizado'){
    $id_mantenimiento = $_POST['id_mantenimiento'];
    $sql = "UPDATE mantenimientos SET VoBo_jefe = 'REALIZADO' WHERE id_mantenimiento = $id_mantenimiento";
    if ($conn->query($sql) === TRUE) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["error" => "Error al actualizar el mantenimiento: " . $conn->error]);
    }
    exit;
}
?>