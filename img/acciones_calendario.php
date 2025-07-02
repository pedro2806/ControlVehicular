<?php
include '../conn.php';
header('Content-Type: application/json');
mysqli_set_charset($conn, "utf8mb4");
date_default_timezone_set('America/Mexico_City');
$accion = isset($_POST['accion']) ? $_POST['accion'] : '';

if ($accion == 'CargarVehiculos'){
    $sql = "SELECT id_vehiculo, placa, marca, modelo, color, '' as id_prestamo, '' as estatus
            FROM inventario Where id_usuario = '".$_COOKIE['id_usuario']."' OR id_us_asignado = '".$_COOKIE['id_usuario']."'
            UNION
            SELECT inv.id_vehiculo, inv.placa, inv.marca, inv.modelo, inv.color, p.id_prestamo, p.estatus
            FROM inventario inv
            INNER JOIN prestamos p ON inv.id_vehiculo = p.id_vehiculo
            WHERE (p.id_usuario = '".$_COOKIE['id_usuario']."' OR id_us_asignado = '".$_COOKIE['id_usuario']."') AND (p.estatus = 'AUTORIZADO' OR p.estatus = 'EN CURSO')";
    
    
    //SELECT id_vehiculo, placa, marca, modelo, color FROM inventario Where id_usuario = '".$_COOKIE['id_usuario']."' OR id_us_asignado = '".$_COOKIE['id_usuario']."'";
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

if($accion == 'ActividadesCalendarioPlaneadas'){
    // Consultar las actividades planeadas del usuario actual
    $fechaHoy = date('Y-m-d');
    $fechaInicio = date('Y-m-d', strtotime($fechaHoy . ' -2 days'));

    $sql = "SELECT ot.*, DATE(ot.FechaPlaneadaInicio) as FechaPlaneadaInicioDate, c.nombre as cliente
            FROM ordenes_servicio ot
            LEFT JOIN clientes c ON ot.customer_id = c.id_cliente
            WHERE DATE(ot.FechaPlaneadaInicio) >= '$fechaInicio' AND ot.qualityAreas NOT IN ('qualityAreas') AND ot.status IN ('Asignada', 'Trabajando') AND ot.engineers != ''";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $actividades = [];
        while ($row = $result->fetch_assoc()) {
            $actividades[] = $row;
        }
        echo json_encode(['status' => 'success', 'actividades' => $actividades]);
    }   
}
?>