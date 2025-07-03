<?php
include '../conn.php';
header('Content-Type: application/json');
mysqli_set_charset($conn, "utf8mb4");
date_default_timezone_set('America/Mexico_City');
$accion = isset($_POST['accion']) ? $_POST['accion'] : '';
$area = isset($_POST['area']) ? $_POST['area'] : '';
$ingeniero = isset($_POST['ing']) ? $_POST['ing'] : '';


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
    $fechaInicio = date('Y-m-d', strtotime($fechaHoy . ' -10 days'));

    /*$sql = "SELECT ot.*, DATE(ot.FechaPlaneadaInicio) as FechaPlaneadaInicioDate, c.nombre as cliente
            FROM ordenes_servicio ot
            LEFT JOIN clientes c ON ot.customer_id = c.id_cliente
            WHERE DATE(ot.FechaPlaneadaInicio) >= '$fechaInicio' AND ot.qualityAreas NOT IN ('qualityAreas') AND ot.status IN ('Asignada', 'Trabajando') AND ot.engineers != ''";*/
    $sql = "SELECT ot.*, DATE(ot.start_date) as FechaPlaneadaInicioDate
            FROM servicios_planeados ot            
            WHERE DATE(ot.start_date) >= '$fechaInicio' AND ot.tipo_ot = 'SiteServiceOrder'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $actividades = [];
        while ($row = $result->fetch_assoc()) {
            $actividades[] = $row;
        }
        echo json_encode(['status' => 'success', 'actividades' => $actividades]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se encontraron actividades planeadas o error en la consulta.']);
    }
}



if($accion == 'ActividadesCalendarioPlaneadasfiltro'){
    // Consultar las actividades planeadas del usuario actual
    $fechaHoy = date('Y-m-d');
    $fechaInicio = date('Y-m-d', strtotime($fechaHoy . ' -10 days'));

    /*$sql = "SELECT ot.*, DATE(ot.FechaPlaneadaInicio) as FechaPlaneadaInicioDate, c.nombre as cliente
            FROM ordenes_servicio ot
            LEFT JOIN clientes c ON ot.customer_id = c.id_cliente
            WHERE DATE(ot.FechaPlaneadaInicio) >= '$fechaInicio' AND ot.qualityAreas NOT IN ('qualityAreas') AND ot.status IN ('Asignada', 'Trabajando') AND ot.engineers != ''";*/
    $sql = "SELECT ot.*, DATE(ot.start_date) as FechaPlaneadaInicioDate
            FROM servicios_planeados ot            
            WHERE DATE(ot.start_date) >= '$fechaInicio' AND ot.tipo_ot = 'SiteServiceOrder'";
    

    $whereClauses = [];

    if (!empty($area)) {
        $area = $conn->real_escape_string($area);
        $whereClauses[] = "REPLACE(SUBSTRING_INDEX(ot.order_code, '-', 1), '25', '') =  '$area'";
    }

    if (!empty($ingeniero)) {
        $ingeniero = $conn->real_escape_string($ingeniero);
        $whereClauses[] = "ot.engineer LIKE '%" . $ingeniero . "%'";
    }

    if (!empty($whereClauses)) {
        $sql .= " AND " . implode(' AND ', $whereClauses);
    }
    //echo $sql;
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $actividades = [];
        while ($row = $result->fetch_assoc()) {
            $actividades[] = $row;
        }
        echo json_encode(['status' => 'success', 'actividades' => $actividades]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se encontraron actividades planeadas o error en la consulta.']);
    }
}
?>