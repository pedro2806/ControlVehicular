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
$id_recibe = $_POST["id_recibe"];
$noEmpleado = $_COOKIE['noEmpleado'];
$rol = $_COOKIE['rol'];

$fecha_inc_prestamo = $_POST["fecha_inc_prestamo"];
$fecha_fin_prestamo = $_POST["fecha_fin_prestamo"];
$fecha_entrega = $_POST["fecha_entrega"];
$fecha_entrega_inicio = $_POST["fecha_entrega_inicio"];
$fecha_entrega_final = $_POST["fecha_entrega_final"];
$hora = date("H:i:s", strtotime($hora));

$km_inicio = $_POST["km_inicio"];
$km_fin = $_POST["km_fin"];

$motivo = $_POST["motivo"];
$notas_jefe = $_POST["notas_jefe"];
$notas_denegar = $_POST["notas_denegar"];
$notas_entrega = $_POST["notas_entrega"];
$notas_devolucion = $_POST["notas_devolucion"];

$estatus = $_POST["estatus"];
$placa = $_POST["placa"];
$gasolina_inicio = $_POST["gasolina_inicio"];
$gasolina_fin = $_POST["gasolina_fin"];

$tipo_uso = $_POST["tipo_uso"];
$detalle_tipo_uso = $_POST["detalle_tipo_uso"];
$notas_jefe = $_POST["notas_jefe"];
/*---------------------------------------------*/
//Registro de Prestamo
if ($accion == "RegistrarPrestamo") {
    $sqlregistro = "INSERT INTO prestamos
                    (fecha_registro, id_usuario, id_checklist, fecha_inc_prestamo, fecha_fin_prestamo, estatus, motivo_us, tipo_uso, detalle_tipo_uso)
                    VALUES (NOW(), '$id_usuario', '$id_checklist', '$fecha_inc_prestamo', '$fecha_fin_prestamo', 'PENDIENTE', '$motivo', '$tipo_uso', '$detalle_tipo_uso')";
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
        $sqlConsulta = " SELECT prest.id_prestamo, prest.id_vehiculo, inv.placa, inv.marca, inv.modelo, inv.color, 
                                prest.fecha_inc_prestamo, prest.fecha_fin_prestamo, prest.estatus, prest.km_inicio, 
                                prest.tipo_uso, prest.detalle_tipo_uso, prest.motivo_us, prest.fecha_entrega
                        FROM prestamos prest
                        LEFT JOIN inventario inv ON prest.id_vehiculo = inv.id_vehiculo
                        WHERE prest.id_usuario = $id_usuario AND prest.estatus = 'PENDIENTE'
                        GROUP BY prest.id_prestamo DESC";
    } elseif ($rol == 1) {
        // ROL 1 es usuario
        $sqlConsulta = "SELECT id_prestamo, id_vehiculo, fecha_inc_prestamo, fecha_fin_prestamo, estatus, km_inicio, 
                               tipo_uso, detalle_tipo_uso, motivo_us
                        FROM prestamos 
                        WHERE id_usuario = $id_usuario
                        GROUP BY prest.id_prestamo DESC";
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
    $sqlConsulta = " SELECT prest.id_prestamo, 
                        prest.id_vehiculo, 
                        IFNULL(prest.tipo_uso, 'S/R') AS tipo_uso, 
                        IFNULL(prest.detalle_tipo_uso, 'S/R') AS detalle_tipo_uso, 
                        IFNULL(prest.motivo_us, 'S/R') AS motivo_us, 
                        IFNULL(prest.notas_jefe, 'S/R') AS notas_jefe, 
                        IFNULL(prest.fecha_entrega, 'S/R') AS fecha_entrega, 
                        IFNULL(prest.fecha_fin_prestamo, 'S/R') AS fecha_fin_prestamo, 
                        IFNULL((SELECT inv.placa FROM inventario inv WHERE inv.id_vehiculo = prest.id_vehiculo), 'S/R') AS placa,
                        IFNULL((SELECT inv.modelo FROM inventario inv WHERE inv.id_vehiculo = prest.id_vehiculo), 'S/R') AS modelo
                    FROM prestamos prest
                    WHERE  prest.id_usuario = $id_usuario AND prest.estatus = 'AUTORIZADO'
                    GROUP BY id_prestamo DESC";
    $result = $conn->query($sqlConsulta);
    $prestamos = [];
    while ($row = $result->fetch_assoc()) {
        $prestamos[] = $row;
    }
    echo json_encode($prestamos);
}

//Consulta Prestamos EN CURSO
if ($accion == "consultarPrestamosEnCurso") {
    $sqlConsulta= " SELECT prest.id_prestamo, prest.id_vehiculo, inv.placa, inv.marca, inv.modelo, inv.color, 
                        prest.fecha_inc_prestamo, prest.fecha_fin_prestamo, prest.estatus, prest.km_inicio, 
                        prest.tipo_uso, prest.detalle_tipo_uso, prest.motivo_us, inv.modelo
                    FROM prestamos prest
                    INNER JOIN inventario inv ON prest.id_vehiculo = inv.id_vehiculo
                    WHERE prest.id_usuario = $id_usuario AND prest.estatus = 'EN CURSO'
                    GROUP BY id_prestamo DESC";
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
                        notas_jefe = '$notas_jefe', fecha_entrega = '$fecha_entrega',  id_autoriza = '$id_usuario' 
                    WHERE id_prestamo = '$id_prestamo'";
    $resultAutoriza = $conn->query($sqlAutoriza);
    if ($resultAutoriza) {
        echo json_encode(["success" => true]);
        cambiarAsignado($conn, $id_vehiculo); 
    } else {
        echo json_encode(["success" => false, "error" => $conn->error]);
    }
    exit;
}

//Denegar Prestamo
if ($accion == "denegarPrestamo") {
    $sqlDenegar = " UPDATE prestamos 
                    SET estatus = 'DENEGADO', id_vehiculo = '$id_vehiculo', notas_jefe = '$notas_denegar', 
                        fecha_registro_asignado = NOW(), id_autoriza = '$id_usuario'
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

//Iniciar Préstamo
if ($accion == "iniciarPrestamo") {
    $sql = "UPDATE prestamos 
            SET estatus = 'EN CURSO', km_inicio = '$km_inicio', gasolina_inicio = '$gasolina_inicio', 
                notas_entrega = '$notas_entrega', fecha_registro_entrega = NOW() 
            WHERE id_prestamo = '$id_prestamo'";
            
    $result = $conn->query($sql);
    if ($result) {
        if (!empty($placa)) {
            // Crear carpeta principal y subcarpeta "Prestamos"
            $carpetaPlaca = "img_control_vehicular/$placa";
            if (!file_exists($carpetaPlaca)) {
                mkdir($carpetaPlaca, 0777, true);
            }
            $carpetaPrestamo = "$carpetaPlaca/Prestamos";
            if (!file_exists($carpetaPrestamo)) {
                mkdir($carpetaPrestamo, 0777, true);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo crear la carpeta porque la placa está vacía.']);
            exit;
        }

        // Manejo de archivos (fotografía de inicio)
        $ruta_destino_inicio = null; // Inicializar la variable para la ruta de la foto
        if (!empty($_FILES['fotos_inicio']['name'][0])) {
            foreach ($_FILES['fotos_inicio']['tmp_name'] as $key => $tmp_name) {
                $file_name = $_FILES['fotos_inicio']['name'][$key];
                $file_tmp = $_FILES['fotos_inicio']['tmp_name'][$key];
                $fecha_actual = date('Ymd_His'); // Formato YYYYMMDD_HHMMSS
                $nuevo_nombre = "{$placa}_Prestamo_{$fecha_actual}";
                $extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $ruta_destino_actual = "$carpetaPrestamo/$nuevo_nombre.$extension";

                if (move_uploaded_file($file_tmp, $ruta_destino_actual)) {
                    if ($key === 0) { // Guardar la ruta del primer archivo para la base de datos
                        $ruta_destino_inicio = $ruta_destino_actual;
                    }
                } else {
                    error_log("Error al subir el archivo: $file_name");
                }
            }
        }

        // Actualizar la ruta de la foto de inicio en la base de datos
        if ($ruta_destino_inicio) {
            $sqlActualizarFoto = "UPDATE prestamos 
                                  SET foto_entrega = '$ruta_destino_inicio' 
                                  WHERE id_prestamo = '$id_prestamo'";
            $conn->query($sqlActualizarFoto);
        }

        echo json_encode(['success' => true, 'message' => 'Préstamo iniciado exitosamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al iniciar el préstamo: ' . $conn->error]);
    }
    exit;
}

// Finalizar Préstamo
if ($accion == "finalizarPrestamo") {
    $sql = "UPDATE prestamos 
            SET estatus = 'FINALIZADO', km_fin = '$km_fin', gasolina_fin = '$gasolina_fin', 
                notas_devolucion = '$notas_devolucion', fecha_registro_devolucion = NOW(), 
                id_recibe = '$id_recibe', id_autoriza = '$id_usuario' 
            WHERE id_prestamo = '$id_prestamo'";
    $result = $conn->query($sql);

    if ($result) {
        if (!empty($placa)) {
            // Crear carpeta principal y subcarpeta "Devoluciones"
            $carpetaPlaca = "img_control_vehicular/$placa";
            if (!file_exists($carpetaPlaca)) {
                mkdir($carpetaPlaca, 0777, true);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo crear la carpeta porque la placa está vacía.']);
            exit;
        }

        // Manejo de archivos 
        $ruta_destino_devolucion = null; // Inicializar la variable para la ruta de la foto
        if (!empty($_FILES['fotos_final']['name'][0])) {
            foreach ($_FILES['fotos_final']['tmp_name'] as $key => $tmp_name) {
                $file_name = $_FILES['fotos_final']['name'][$key];
                $file_tmp = $_FILES['fotos_final']['tmp_name'][$key];
                $fecha_actual = date('Ymd_His'); // Formato YYYYMMDD_HHMMSS
                $nuevo_nombre = "{$placa}_Devolucion_{$fecha_actual}_{$key}";
                $extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $ruta_destino_actual = "img_control_vehicular/$placa/Prestamos/$nuevo_nombre.$extension"; 

                if (move_uploaded_file($file_tmp, $ruta_destino_actual)) {
                    if ($key === 0) { // Guardar la ruta del primer archivo para la base de datos
                        $ruta_destino_devolucion = $ruta_destino_actual;
                    }
                } else {
                    error_log("Error al subir el archivo: $file_name");
                }
            }
        }

        // Actualizar la ruta de la foto de devolución en la base de datos
        if ($ruta_destino_devolucion) {
            $sqlActualizarFoto = "UPDATE prestamos 
                                  SET foto_devolucion = '$ruta_destino_devolucion' 
                                  WHERE id_prestamo = '$id_prestamo'";
            $conn->query($sqlActualizarFoto);
        }

        echo json_encode(['success' => true, 'message' => 'Préstamo finalizado exitosamente.']);
        cambiarAsignadoNo($conn, $id_vehiculo); 
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al finalizar el préstamo: ' . $conn->error]);
    }
    exit;
}

//Consulta de Usuarios con Rol 3
if ($accion == "consultarUsuariosRecibe") {
    $sql = "SELECT id_usuario, nombre FROM usuarios WHERE rol = 3 AND estatus = 1";
    $result = $conn->query($sql);

    $usuarios = [];
    while ($row = $result->fetch_assoc()) {
        $usuarios[] = $row;
    }
    echo json_encode($usuarios);
    exit;
}

//Cambiar ASGINADO a NO
function cambiarAsignado($conn, $id_vehiculo) {
    $sql = "UPDATE inventario SET asignado = 'SI' WHERE id_vehiculo = '$id_vehiculo'";
    $result = $conn->query($sql);
}

//Cambiar ASGINADO a SI
function cambiarAsignadoNo($conn, $id_vehiculo) {
    $sql = "UPDATE inventario SET asignado = 'NO' WHERE id_vehiculo = '$id_vehiculo'";
    $result = $conn->query($sql);
}
?>