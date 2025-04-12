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
$id_usuario = $_COOKIE['id_usuario'];
$foto = $_POST["rutaImagen"];
/*----------------------------------------------------------------------------*/

//Registro de Siniestro
if ($accion == "registroSiniestro") {

    $sqlregistro = "INSERT INTO siniestros 
                    (id_coche, fecha_registro, fecha, tipo_carro, nombre_dueno, hora, kilometraje, gasolina, origen, destino, lugar, 
                    empresa, servicio, coordenadas, descripcion, partes_dañadas, ubicacion_vehiculo, contacto, foto)
                    VALUES ('$id_coche', '$fecha_registro', '$fecha', '$tipo_carro', '$nombre_dueno', '$hora', '$kilometraje', '$gasolina', '$origen', '$destino',
                            '$lugar', '$empresa', '$servicio', '$coordenadas', '$descripcion', '$partes_dañadas', '$ubicacion_vehiculo', '$contacto', '$foto')";
                            
    $resultregistro = $conn->query($sqlregistro);
    // Verificar si la consulta fue exitosa
    if ($resultregistro) {
        echo json_encode(["success" => true, "message" => "Siniestro registrado exitosamente."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al registrar el siniestro: " . $conn->error]);
    }
}

//Creacion de carpeta
if ($accion == "manejarCarpetasYFoto") {
    
    $rutaBase = "img_control_vehicular";
    $rutaPlaca = $rutaBase . "/" . $placa;
    $rutaSiniestros = $rutaPlaca . "/Siniestros";

    if (!file_exists($rutaPlaca)) {
        mkdir($rutaPlaca, 0777, true);
    }
    if (!file_exists($rutaSiniestros)) {
        mkdir($rutaSiniestros, 0777, true);
    }

    // Manejar la subida de la imagen
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $rutaImagen = $placa . "_Siniestro_" . date("Ymd_his") . "." . pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $rutaTemporal = $_FILES['foto']['tmp_name'];
        $rutaDestino = $rutaSiniestros . "/" . $rutaImagen;

        if (move_uploaded_file($rutaTemporal, $rutaDestino)) {
            echo json_encode([
                "success" => true,
                "rutaImagen" => $rutaImagen
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Error al mover la imagen al destino."
            ]);
        }
    } else {
        echo json_encode([
            "success" => false,
            "message" => "No se recibió ninguna imagen o hubo un error al subirla."
        ]);
    }
    exit;
}
// Consulta para obtener los datos de la tabla "inventario"
if ($accion == "consultarInventario") {

    $sqlConsultaVehiculos = "SELECT id_coche, placa, modelo 
            FROM inventario 
            WHERE id_usuario = '$id_usuario'";
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