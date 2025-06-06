<?php
include 'conn.php';
header('Content-Type: application/json');
mysqli_set_charset($conn, "utf8mb4");
date_default_timezone_set('America/Mexico_City');

$accion = $_POST["accion"];

$id_vehiculo = $_POST["id_vehiculo"];
$fecha = $_POST["fecha"];
$hora = $_POST["hora"];
$kilometraje = $_POST["kilometraje"];
$gasolina = $_POST["gasolina"];
$origen = $_POST["origen"];
$destino = $_POST["destino"];
$lugar = $_POST["lugar"];
$empresa = $_POST["empresa"];
$servicio = $_POST["servicio"];
$coordenadas = $_POST["coordenadas"];
$descripcion = $_POST["descripcion"];
$partes_dañadas = $_POST["daños"];
$ubicacion_vehiculo = $_POST["ubicacion"];
$contacto = $_POST["contacto"];

$tipo_carro = $_POST["tipo_carro"];
$id_dueno = $_POST["id_dueno"];
$fecha_registro = date("Y-m-d H:i:s");
$placa = $_POST["placa"];
$marca = $_POST["marca"];
$modelo = $_POST["modelo"];
$color = $_POST["color"];
$anio = $_POST["anio"];
$noEmpleado = $_COOKIE['noEmpleado'];
$id_usuario = $_COOKIE['id_usuario'];
$rutasImagenes = $_POST["rutasImagenes"];

$id_formato = $_POST["id_formato"];
$formato = $_POST["formato"];
/*----------------------------------------------------------------------------*/

//Registro de Siniestro
if ($accion == "registroSiniestro") {
    $sqlregistro = "INSERT INTO siniestros 
                                (id_vehiculo, fecha_registro, fecha, hora, tipo_carro, id_dueno, kilometraje, gasolina, origen, destino, lugar, 
                                empresa, servicio, coordenadas, descripcion, partes_dañadas, ubicacion_vehiculo, contacto) 
                    VALUES ('$id_vehiculo', '$fecha_registro', '$fecha', '$hora', '$tipo_carro', '$id_dueno', '$kilometraje', '$gasolina', '$origen', 
                            '$destino', '$lugar', '$empresa', '$servicio', '$coordenadas', '$descripcion', '$partes_dañadas', '$ubicacion_vehiculo', '$contacto')";      
    if ($conn->query($sqlregistro) === TRUE) {
        $id_formato = $conn->insert_id;
        echo json_encode(["success" => true, "id_formato" => $id_formato, "message" => "Siniestro registrado exitosamente."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al registrar el siniestro."]);
    }
    exit;
}

// Subir imágenes
if ($accion == "subirImagenes") {
    $rutaBase = "img_control_vehicular/$placa/Siniestro";
    if (!file_exists($rutaBase)) {
        mkdir($rutaBase, 0777, true);
        error_log("Carpeta creada: $rutaBase");
    }

    $contador = 1; 
    foreach ($_FILES['fotos']['tmp_name'] as $key => $tmp_name) {
        $file_name = $_FILES['fotos']['name'][$key];
        $file_tmp = $_FILES['fotos']['tmp_name'][$key];
        $extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $nuevoNombre = "{$placa}_Siniestro_" . date("Ymd_His") . "_{$contador}.$extension";
        $rutaDestino = $rutaBase ."/". $nuevoNombre; 

        // Mover el archivo a la ruta destino
        if (move_uploaded_file($file_tmp, $rutaDestino)) {
            error_log("Archivo movido a: $rutaDestino");

            // Insertar la ruta en la tabla "fotos"
            $sql = "INSERT INTO fotos (formato, id_formato, id_vehiculo, imagen) 
                    VALUES ('Siniestro', '$id_formato', '$id_vehiculo', '$rutaDestino')";
            if ($conn->query($sql) === TRUE) {
                error_log("Imagen registrada en la base de datos: $rutaDestino");
            } else {
                error_log("Error al registrar la imagen en la base de datos: " . $conn->error);
            }
        } else {
            error_log("Error al mover el archivo: $nuevoNombre");
        }

        $contador++; // Incrementar el contador para la siguiente imagen
    }

    echo json_encode(["success" => true, "message" => "Imágenes subidas exitosamente."]);
    exit;
}

// Consulta para obtener los vehiculos asignados al usuario 
if ($accion == "consultarInventario") {

    $sqlConsultaVehiculos ="SELECT inv.id_vehiculo, inv.placa, inv.modelo, inv.marca, inv.color, inv.anio
                            FROM inventario inv
                            WHERE inv.id_us_asignado = $id_usuario OR inv.id_usuario = $id_usuario
                            UNION
                            SELECT inv.id_vehiculo, inv.placa, inv.modelo, inv.marca, inv.color, inv.anio 
                            FROM prestamos p 
                            INNER JOIN inventario inv ON p.id_vehiculo = inv.id_vehiculo
                            WHERE p.id_usuario = $id_usuario AND p.estatus= 'AUTORIZADO'";
    $result = $conn->query($sqlConsultaVehiculos);

    $vehiculos = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $vehiculos[] = $row;
        }
    }
    echo json_encode($vehiculos);
}

// Consulta para obtener los vehiculos en general
if ($accion == "consultarInventarioGeneral") {

    $sqlConsultaVehiculosG ="SELECT inv.id_vehiculo, inv.placa, inv.modelo, inv.marca, inv.color, inv.anio, inv.usuario, inv.id_usuario
                            FROM inventario inv
                            WHERE id_usuario = $id_usuario AND  asignado = 'NO'  OR inv.id_usuario = $id_usuario
                            UNION
                            SELECT inv.id_vehiculo, inv.placa, inv.modelo, inv.marca, inv.color, inv.anio, inv.usuario, inv.id_usuario
                            FROM inventario inv
                            WHERE inv.id_usuario != $id_usuario AND inv.asignado = 'NO'";
    $result = $conn->query($sqlConsultaVehiculosG);

    $vehiculos = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $vehiculos[] = $row;
        }
    }
    echo json_encode($vehiculos);
}
?>