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
$id_dueno = $_POST["id_dueno"];
$noEmpleado = $_COOKIE['noEmpleado'];
$rol = $_COOKIE['rol'];

$fecha_registro = date("Y-m-d H:i:s");
$fecha_inc_prestamo = $_POST["fecha_inc_prestamo"];
$fecha_fin_prestamo = $_POST["fecha_fin_prestamo"];
$fecha_entrega = $_POST["fecha_entrega"];
$fecha_entrega_final = $_POST["fecha_entrega_final"];
$hora = date("H:i:s", strtotime($hora));

$kilometraje_inicio = $_POST["km_inicio"];
$kilometraje_fin = $_POST["km_fin"];

$motivo = $_POST["motivo"];
$notas_jefe = $_POST["notas_jefe"];
$notas_denegar = $_POST["notas_denegar"];
$notas_finales = $_POST["notas_finales"];

$estatus = $_POST["estatus"];
$placa = $_POST["placa"];
$gasolina = $_POST["gasolina"];

$tipo_uso = $_POST["tipo_uso"];
$detalle_tipo_uso = $_POST["detalle_tipo_uso"];
$notas_jefe = $_POST["notas_jefe"];
/*---------------------------------------------*/
//Registro de Prestamo
if ($accion == "RegistrarPrestamo") {
    $sqlregistro = "INSERT INTO prestamos
                    (id_vehiculo, fecha_registro, id_usuario, id_checklist, fecha_inc_prestamo, fecha_fin_prestamo, estatus, tipo_uso, detalle_tipo_uso, motivo_us)
                    VALUES ('$id_vehiculo', '$fecha_registro', '$id_usuario', '$id_checklist', '$fecha_inc_prestamo',
                    '$fecha_fin_prestamo', 'PENDIENTE', '$tipo_uso', '$detalle_tipo_uso', '$motivo')";
    $resultregistro = $conn->query($sqlregistro);
    if ($resultregistro) {
        echo json_encode(["success" => true, "message" => "Préstamo registrado exitosamente."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al registrar la solicitud: " . $conn->error]);
    }
    exit;
}

//Consulta de Prestamos
if ($accion == "consultarPrestamos") {
    if ($rol == 3) {
        // ROL 3 es jefe de área
        $sqlConsulta = "SELECT prest.id_prestamo, prest.id_vehiculo, inv.placa, inv.marca, inv.modelo, inv.color, 
                               prest.fecha_inc_prestamo, prest.fecha_fin_prestamo, prest.estatus, prest.kilometraje_inicio, 
                               prest.tipo_uso, prest.detalle_tipo_uso, prest.motivo_us
                        FROM prestamos prest
                        INNER JOIN inventario inv ON prest.id_vehiculo = inv.id_vehiculo
                        WHERE inv.id_usuario = $id_usuario AND prest.estatus = 'PENDIENTE'";
    } elseif ($rol == 1) {
        // ROL 1 es usuario
        $sqlConsulta = "SELECT id_prestamo, id_vehiculo, fecha_inc_prestamo, fecha_fin_prestamo, estatus, kilometraje_inicio, 
                               tipo_uso, detalle_tipo_uso, motivo_us
                        FROM prestamos prest
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

//Consulta Actualizar Prestamo
if ($accion == "actualizarPrestamo") {
    if ($rol == 3) {
        $sqlConsulta = "SELECT prest.id_prestamo, prest.id_vehiculo, inv.placa, inv.marca, inv.modelo, inv.color, 
                               prest.fecha_registro_asignado, prest.fecha_fin_prestamo, prest.estatus, prest.kilometraje_inicio, 
                               prest.tipo_uso, prest.detalle_tipo_uso, prest.motivo_us
                        FROM prestamos prest
                        INNER JOIN inventario inv ON prest.id_usuario = inv.id_usuario
                        WHERE inv.id_usuario = $id_usuario AND prest.estatus = 'AUTORIZADO'";
    } elseif ($rol == 1) {
        $sqlConsulta = "SELECT id_prestamo, id_vehiculo, fecha_inc_prestamo, fecha_fin_prestamo, estatus, kilometraje_inicio, 
                               tipo_uso, detalle_tipo_uso, motivo_us
                        FROM prestamos prest
                        WHERE prest.id_usuario = $id_usuario AND prest.estatus = 'AUTORIZADO'";
    }
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
                    SET estatus = 'AUTORIZADO', id_vehiculo = '$id_vehiculo', fecha_registro_asignado = NOW(), 
                        notas_jefe = '$notas_jefe', fecha_registro_entrega = '$fecha_entrega', id_autoriza = '$id_usuario' 
                    WHERE id_prestamo = '$id_prestamo'";
                    echo $sqlAutoriza;
    $resultAutoriza = $conn->query($sqlAutoriza);
    if ($resultAutoriza) {
        echo json_encode(["success" => true, "message" => "Préstamo autorizado exitosamente."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al autorizar el préstamo: " . $conn->error]);
    }
    exit;
}

//Denegar Prestamo
if ($accion == "denegarPrestamo") {
    $sqlDenegar = " UPDATE prestamos 
                    SET estatus = 'DENEGADO', id_vehiculo = '$id_vehiculo', notas_jefe = '$notas_denegar', 
                        fecha_confirmacion = '$fecha_registro', id_autoriza = '$id_usuario'
                    WHERE id_prestamo = '$id_prestamo'";
    $resultDenegar = $conn->query($sqlDenegar);

    if ($resultDenegar) {
        echo json_encode(["success" => true, "message" => "Préstamo denegado exitosamente."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al denegar el préstamo: " . $conn->error]);
    }
    exit;
}

//Finalizar Prestamo
if ($accion == "finalizarPrestamo") {
    $sqlFinaliza = "UPDATE prestamos 
                    SET estatus = 'FINALIZADO', kilometraje_fin = '$kilometraje_fin', fecha_devolucion = '$fecha_devolucion', 
                        gasolina_fin = '$gasolina', notas_devolucion = '$notas_finales', id_autoriza = '$id_usuario'
                    WHERE id_prestamo = '$id_prestamo'";
    $resultFinaliza = $conn->query($sqlFinaliza);

    if ($resultFinaliza) {
        echo json_encode(["success" => true, "message" => "Préstamo finalizado exitosamente."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al finalizar el préstamo: " . $conn->error]);
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