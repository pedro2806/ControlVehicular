<?php
include 'conn.php';
header('Content-Type: application/json');
mysqli_set_charset($conn, "utf8mb4");
date_default_timezone_set('America/Mexico_City');

$accion = $_POST["accion"];

$id_vehiculo = $_POST["id_vehiculo"];
$id_prestamo = $_POST["id_prestamo"];
$id_usuario = $_COOKIE['id_usuario'];
$id_checklist = $_POST["id_checklist"];
$fecha_registro = date("Y-m-d H:i:s");
$fecha_inc_prestamo = $_POST["fecha_inc_prestamo"];
$fecha_fin_prestamo = $_POST["fecha_fin_prestamo"];
$estatus = $_POST["estatus"];
$kilometraje_inicio = $_POST["km_inicio"];
$kilometraje_fin = $_POST["km_fin"];
$fecha_entrega = $_POST["fecha_entrega"];
$motivo = $_POST["motivo"];
$notas_aprobadas = $_POST["notas_aprobadas"];
$notas_denegar = $_POST["notas_denegar"];
$notas = trim($notas_aprobadas . " " . $notas_denegar);
$hora = date("H:i:s", strtotime($hora));

$id_dueno = $_POST["id_dueno"];
$noEmpleado = $_COOKIE['noEmpleado'];
$placa = $_POST["placa"];

$rol = $_COOKIE['rol'];
/*---------------------------------------------*/
//Registro de Prestamo
if ($accion == "RegistrarPrestamo") {

    $sqlregistro = "INSERT INTO prestamos
                    (id_vehiculo, fecha_registro, id_usuario, id_checklist, fecha_inc_prestamo, fecha_fin_prestamo, estatus, motivo_us)
                    VALUES ('0', '$fecha_registro', '$id_usuario', '$id_checklist', '$fecha_inc_prestamo',
                    '$fecha_fin_prestamo', 'PENDIENTE', '$motivo')";
    $resultregistro = $conn->query($sqlregistro); 
    if ($resultregistro) {
        echo json_encode(["success" => true, "message" => "Prestamo registrado exitosamente."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al registrar la solicitud: " . $conn->error]);
    }
    exit;
}

//Consulta de Prestamos
if ($accion == "consultarPrestamos") {
    if ($rol == 3) {
        //ROL 3 es jefe de area
        $sqlConsulta =" SELECT prest.id_prestamo, prest.id_vehiculo, inv.placa, inv.marca, inv.modelo, inv.color, 
                                prest.fecha_inc_prestamo, prest.fecha_fin_prestamo, prest.estatus, prest.kilometraje_inicio, prest.motivo
                        FROM prestamos prest
                        INNER JOIN inventario inv ON prest.id_vehiculo = inv.id_vehiculo
                        WHERE inv.id_usuario = $id_usuario AND prest.estatus = 'PENDIENTE'";

    } elseif ($rol == 1) {
        //ROL 1 es usuario 
        $sqlConsulta =" SELECT prest.id_prestamo, prest.id_vehiculo, inv.placa, inv.marca, inv.modelo, inv.color, 
                                prest.fecha_inc_prestamo, prest.fecha_fin_prestamo, prest.estatus, prest.kilometraje_inicio, prest.motivo
                        FROM prestamos prest
                        INNER JOIN inventario inv ON prest.id_vehiculo = inv.id_vehiculo
                        WHERE prest.id_usuario = $id_usuario";
    } else {
        echo json_encode(["success" => false, "message" => "Rol no autorizado."]);
        exit;
    }

    // Ejecutar la consulta
    $result = $conn->query($sqlConsulta);

    $prestamos = [];
    while ($row = $result->fetch_assoc()) {
        $prestamos[] = $row;
    }
    echo json_encode($prestamos);
}

//Aprobar Prestamo
if ($accion == "autorizarPrestamo") {
    $sqlAutoriza = "UPDATE prestamos 
            SET estatus = 'AUTORIZADO', id_vehiculo = '$id_vehiculo', fecha_confirmacion = NOW(), 
                notas = '$notas', kilometraje_inicio = '$kilometraje_inicio', kilometraje_fin = '$kilometraje_fin',     
                fecha_entrega = '$fecha_entrega', id_autoriza = '$id_usuario' 
            WHERE id_prestamo = '$id_prestamo'";
    $resultAutoriza = $conn->query($sqlAutoriza);
    echo json_encode(["success" => true]);
}

//Denegar Prestamo
if ($accion == "denegarPrestamo") {
    $sqlDenegar = " UPDATE prestamos 
                    SET estatus = 'DENEGADO', id_vehiculo = '$id_vehiculo', notas = '$notas_denegar', fecha_confirmacion = '$fecha_registro', id_autoriza = '$id_usuario'
                    WHERE id_prestamo = '$id_prestamo'";
    $resultDenegar = $conn->query($sqlDenegar);

    if ($resultDenegar) {
        echo json_encode(["success" => true, "message" => "Préstamo denegado exitosamente."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al denegar el préstamo: " . $conn->error]);
    }
    exit;
}

//Cambia información de un vehículo en el modal de préstamo
if ($accion == "obtenerInfoVehiculo") {
    $sqlInfo = "SELECT modelo, color, estatus, asignado FROM inventario WHERE id_vehiculo = '$id_vehiculo'";
    $resultInfo = $conn->query($sqlInfo);

    if ($resultInfo->num_rows > 0) {
        $vehiculo = $resultInfo->fetch_assoc();
        echo json_encode(["success" => true, "vehiculo" => $vehiculo]);
    } else {
        echo json_encode(["success" => false, "message" => "No se encontró información del vehículo."]);
    }
    exit;
}
?>