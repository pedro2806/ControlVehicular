<?php
include 'conn.php';
header('Content-Type: application/json');
mysqli_set_charset($conn, "utf8mb4");
date_default_timezone_set('America/Mexico_City');

$accion = $_POST["accion"];

$id_coche = $_POST["id_coche"];
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
$nombre_dueno = $_POST["nombre_dueno"];
$fecha_registro = date("Y-m-d H:i:s");
$placa = $_POST["placa"];
$marca = $_POST["marca"];
$modelo = $_POST["modelo"];
$color = $_POST["color"];
$anio = $_POST["anio"];
$noEmpleado = $_COOKIE['noEmpleado'];
/*----------------------------------------------------------------------------*/

//Registro de Siniestro
if ($accion == "registroSiniestro") {

    /*$rutaFoto = null; // Inicializar la ruta de la foto como null
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $nombreArchivo = $_FILES['foto']['name'];
        $rutaTemporal = $_FILES['foto']['tmp_name'];
        $rutaDestino = "uploads/" . $nombreArchivo;

        if (move_uploaded_file($rutaTemporal, $rutaDestino)) {
            $rutaFoto = $rutaDestino; // Guardar la ruta de la foto si se subió correctamente
        } else {
            echo json_encode(["success" => false, "message" => "Error al subir la foto."]);
            exit;
        }
    }*/
    $sqlregistro = "INSERT INTO siniestros 
                    (fecha_registro, fecha, tipo_carro, nombre_dueno, hora, kilometraje, gasolina, origen, destino, lugar, 
                    empresa, servicio, coordenadas, descripcion, partes_dañadas, ubicacion_vehiculo, contacto, foto)
                    VALUES ('$fecha_registro', '$fecha', '$tipo_carro', '$nombre_dueno', '$hora', '$kilometraje', '$gasolina', '$origen', '$destino',
                            '$lugar', '$empresa', '$servicio', '$coordenadas', '$descripcion', '$partes_dañadas', '$ubicacion_vehiculo', '$contacto', '$rutaFoto')";
                            
    $resultregistro = $conn->query($sqlregistro);
    // Verificar si la consulta fue exitosa
    if ($resultregistro) {
        echo json_encode(["success" => true, "message" => "Siniestro registrado exitosamente."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al registrar el siniestro: " . $conn->error]);
    }
}

// Consulta para obtener los datos de la tabla "inventario"
if ($accion == "consultarInventario") {

    $sqlConsultaVehiculos = "SELECT placa, modelo, color, anio 
            FROM inventario 
            WHERE id_usuario = '$noEmpleado'";
    $result = $conn->query($sqlConsultaVehiculos);

    $vehiculos = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $vehiculos[] = $row;
        }
    }
    echo json_encode($vehiculos);
}
?>