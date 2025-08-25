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
$formato = (isset($_POST["formato"]) && $_POST["formato"] !== null && $_POST["formato"] !== '') ? $_POST["formato"] : null;
/*----------------------------------------------------------------------------*/

function subirImagenesCheckin($imagenes, $id_actividad, $id_vehiculo, $conn, $placa, $actividad) {

    // Crear carpeta principal y subcarpeta "Actividades"
    $carpetaPlaca = "img_control_vehicular/$placa";
    if (!file_exists($carpetaPlaca)) {
        mkdir($carpetaPlaca, 0777, true);
    }
    $carpetaActividad = "$carpetaPlaca/Siniestro";
    if (!file_exists($carpetaActividad)) {
        mkdir($carpetaActividad, 0777, true);
    }

    if (!isset($imagenes) || !is_array($imagenes)) {
        return;
    }
    foreach ($imagenes['tmp_name'] as $key => $tmp_name) {
        if ($imagenes['error'][$key] === UPLOAD_ERR_OK) {
            
            static $consecutivo = 1;
            $extension = pathinfo($imagenes['name'][$key], PATHINFO_EXTENSION);
            $nombreArchivo = $placa . '_' . $actividad . '_' . $consecutivo . '.' . $extension;
            $consecutivo++;
            $ruta_destino = $carpetaActividad.'/' . $nombreArchivo;

            if (move_uploaded_file($tmp_name, $ruta_destino)) {
                // Guardar la ruta de la imagen en la base de datos
                $stmt = $conn->prepare("INSERT INTO fotos (formato, id_formato, id_vehiculo, imagen) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssis", $actividad, $id_actividad, $id_vehiculo, $ruta_destino);
                $stmt->execute();
                $stmt->close();

            }
        }
    }
}

//Registro de Siniestro
if ($accion == "registroSiniestro") {
    $sqlregistro = "INSERT INTO siniestros 
                                (id_vehiculo, fecha_registro, fecha, hora, tipo_carro, id_dueno, kilometraje, gasolina, origen, destino, lugar, 
                                empresa, servicio, coordenadas, descripcion, partes_dañadas, ubicacion_vehiculo, contacto) 
                    VALUES ('$id_vehiculo', '$fecha_registro', '$fecha', '$hora', '$tipo_carro', '$id_usuario', '$kilometraje', '$gasolina', '$origen', 
                            '$destino', '$lugar', '$empresa', '$servicio', '$coordenadas', '$descripcion', '$partes_dañadas', '$ubicacion_vehiculo', '$contacto')";      
    if ($conn->query($sqlregistro) === TRUE) {
        
            $consultaUltimaActividad = "SELECT id_siniestro FROM siniestros WHERE id_dueno = '" . $_COOKIE['id_usuario'] . "' ORDER BY fecha_registro DESC, id_siniestro DESC LIMIT 1";
            $resultUltimaActividad = $conn->query($consultaUltimaActividad);
            $idUltimaActividad = null;

            if ($resultUltimaActividad && $resultUltimaActividad->num_rows > 0) {
                $rowUltimaActividad = $resultUltimaActividad->fetch_assoc();
                $idUltimaActividad = $rowUltimaActividad['id_siniestro'];
                // Ahora $idUltimaActividad contiene solo el id de la última actividad
            }
            // Procesar imágenes de check-in si existen
            if (isset($_FILES['foto'])) {
                subirImagenesCheckin($_FILES['foto'], $idUltimaActividad, $id_vehiculo, $conn, $placa, 'siniestro');
            }




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
    $rol = $_COOKIE['rol'];
    if ($rol == '3' || $rol == '4'  || $rol == '2') { // 3: Gerente, 4: Administrador
        $sqlConsultaVehiculosG ="SELECT inv.id_vehiculo, inv.placa, inv.modelo, inv.marca, inv.color, inv.anio, inv.usuario, inv.id_usuario, 'AREA' as tipo
                            FROM inventario inv
                            WHERE id_usuario = $id_usuario  OR inv.id_usuario = $id_usuario
                            UNION
                            SELECT inv.id_vehiculo, inv.placa, inv.modelo, inv.marca, inv.color, inv.anio, inv.usuario, inv.id_usuario, 'EXTERNO' as tipo
                            FROM inventario inv
                            WHERE inv.id_usuario != $id_usuario";
    } 
    if ($rol == '1') { 
        $sqlConsultaVehiculosG ="(SELECT inv.id_vehiculo, inv.placa, inv.modelo, inv.marca, inv.color, inv.anio, inv.usuario, inv.id_usuario, 'AREA' as tipo
                            FROM inventario inv
                            INNER JOIN usuarios u ON $id_usuario = u.id_usuario
                            WHERE inv.id_usuario = $id_usuario
                            OR inv.id_usuario IN (SELECT id_usuario FROM usuarios WHERE jefe = u.jefe UNION ALL SELECT id_usuario FROM usuarios WHERE noEmpleado =  u.jefe) ORDER BY inv.usuario)
                            UNION
                            (SELECT inv.id_vehiculo, inv.placa, inv.modelo, inv.marca, inv.color, inv.anio, inv.usuario, inv.id_usuario, 'EXTERNO' as tipo
                            FROM inventario inv
                            WHERE inv.id_usuario != $id_usuario ORDER BY inv.usuario)";
    }
    

    $result = $conn->query($sqlConsultaVehiculosG);

    $vehiculos = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $vehiculos[] = $row;
        }
    }
    echo json_encode($vehiculos);
}

// Consulta para obtener los vehiculos en general
if ($accion == "consultarInventarioAutoriza") {    
        $sqlConsultaVehiculosG ="SELECT inv.id_vehiculo, inv.placa, inv.modelo, inv.marca, inv.color, inv.anio, inv.usuario, inv.id_usuario, 'AREA' as tipo
                            FROM inventario inv                            
                            WHERE inv.id_usuario = $id_usuario";
    
    

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