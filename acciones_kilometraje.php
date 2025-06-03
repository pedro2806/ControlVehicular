<?php
include 'conn.php';
header('Content-Type: application/json');
mysqli_set_charset($conn, "utf8mb4");
date_default_timezone_set('America/Mexico_City');

// Obtener datos del POST
$id_vehiculo = isset($_POST['vehiculoAsignado']) ? $_POST['vehiculoAsignado'] : null;
$id_prestamo = isset($_POST['otRelacionada']) ? $_POST['otRelacionada'] : null;
$km_inicio = isset($_POST['kmActual']) ? $_POST['kmActual'] : null;
$notas = isset($_POST['notasCheckin']) ? $_POST['notasCheckin'] : null;
$patron = isset($_POST['patronRelacionado']) ? $_POST['patronRelacionado'] : null;
$accion = isset($_POST['accion']) ? $_POST['accion'] : null;
$gasActual = isset($_POST['gasActual']) ? $_POST['gasActual'] : null;
$otRelacionada = isset($_POST['otRelacionada']) ? $_POST['otRelacionada'] : null;
// Si recibes archivos (foto), puedes procesarlos aquí
if (isset($_FILES['foto']) && $_FILES['foto']['error'] == UPLOAD_ERR_OK) {
    $nombreArchivo = uniqid('foto_', true) . '.' . pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
    $ruta_destino_inicio = 'uploads/' . $nombreArchivo;
    move_uploaded_file($_FILES['foto']['tmp_name'], $ruta_destino_inicio);
}

if ($accion == 'CargarVehiculos'){
    $sql = "SELECT id_vehiculo, placa, marca, modelo, color FROM inventario Where id_usuario = '".$_COOKIE['id_usuario']."'";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $vehiculos = [];
        while ($row = $result->fetch_assoc()) {
            $vehiculos[] = $row;
        }
        echo json_encode($vehiculos);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se encontraron vehículos activos.']);
    }
    exit;
}

if($accion == 'CapturaCheckIn'){
    // Insertar los datos en la base de datos
    $sql = "INSERT INTO actividad_vehiculo (id_prestamo, id_vehiculo, id_usuario, km_actual, foto_url, fecha_actividad, tipo_actividad, notas, patron, gasolina_actual, ot)
            VALUES ('$id_prestamo', '$id_vehiculo', '".$_COOKIE['id_usuario']."', '$km_inicio', '$ruta_destino_inicio', NOW(), 'INICIO', '$notas', '$patron', '$gasActual','$otRelacionada')";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(['status' => 'success', 'message' => 'Check-in realizado correctamente.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al realizar el check-in: ' . $conn->error]);
    }
    exit;
}

if($accion == 'CapturaCheckOut'){
    // Insertar los datos en la base de datos
    $sql = "INSERT INTO actividad_vehiculo (id_prestamo, id_vehiculo, id_usuario, km_actual, foto_url, fecha_actividad, tipo_actividad, notas, patron, gasolina_actual, ot)
            VALUES ('$id_prestamo', '$id_vehiculo', '".$_COOKIE['id_usuario']."', '$km_inicio', '$ruta_destino_inicio', NOW(), 'FINALIZACION', '$notas', '$patron', '$gasActual', '$otRelacionada')";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(['status' => 'success', 'message' => 'Check-out realizado correctamente.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al realizar el check-out: ' . $conn->error]);
    }
    exit;
}

if($accion == 'ActividadesPendientes'){
    // Consultar las actividades pendientes de inicio para el usuario actual
    $sql = "SELECT av.*, i.placa, i.marca, i.modelo 
        FROM actividad_vehiculo av
        INNER JOIN inventario i ON av.id_vehiculo = i.id_vehiculo
        WHERE av.tipo_actividad = 'INICIO' 
        AND av.id_usuario = '".$_COOKIE['id_usuario']."'
        AND NOT EXISTS (
            SELECT 1 FROM actividad_vehiculo av2
            WHERE av2.id_usuario = av.id_usuario
        AND av2.tipo_actividad = 'FIN'
        )";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $actividades = [];
        while ($row = $result->fetch_assoc()) {
        $actividades[] = $row;
        }
        echo json_encode($actividades);
    } else {
        echo json_encode(['status' => 'success', 'data' => []]);
    }
    exit;
}

if($accion == 'Actividades'){
    // Consultar las actividades del usuario actual
    $sql = "SELECT av.*, i.placa, i.marca, i.modelo, (select u.nombre from usuarios u where u.id_usuario = av.id_usuario) as usuario
        FROM actividad_vehiculo av
        INNER JOIN inventario i ON av.id_vehiculo = i.id_vehiculo        
        WHERE av.id_usuario = '".$_COOKIE['id_usuario']."'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $actividades = [];
        while ($row = $result->fetch_assoc()) {
            $actividades[] = $row;
        }
        echo json_encode(['status' => 'success', 'actividades' => $actividades]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se encontraron actividades.']);
    }
    exit;
}
?>