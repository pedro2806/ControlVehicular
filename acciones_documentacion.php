<?php
include 'conn.php';
header('Content-Type: application/json');
mysqli_set_charset($conn, "utf8mb4");
date_default_timezone_set('America/Mexico_City');

$accion = $_POST["accion"];

$id_vehiculo = $_POST["id_vehiculo"];
$fecha_registro = date("Y-m-d H:i:s");
$contacto = $_POST["contacto"];
$fecha_prox = $_POST["fecha_prox"];
$tarjeta_circulacion = $_POST["tarjeta_circulacion"];
$refrendo_actual = $_POST['refrendo_actual'];
$seguro_auto = $_POST["seguro_auto"];
$verificacion_vigente = $_POST["verificacion_vigente"];
$ruta_documento = $_POST["ruta_documento"];
$placa = $_POST["placa"];

error_log("Acción recibida: " . $accion);
/*---------------------------------------------*/


//Registro de Mantenimiento
if ($accion == "RegistrarDocumentos") {
    $sqlregistro = "INSERT INTO documentacion
                    (id_vehiculo, fecha_registro, contacto, fecha_prox, tarjeta_circulacion, refrendo_actual, seguro_auto, verificacion_vigente, ruta_Documento)
                    VALUES ('$id_vehiculo', '$fecha_registro', '$contacto', '$fecha_prox', '$tarjeta_circulacion', '$refrendo_actual', '$seguro_auto', '$verificacion_vigente', '$ruta_documento')";                   
    $resultregistro = $conn->query($sqlregistro);
    if ($resultregistro) {
        echo json_encode(["success" => true, "message" => "Registrado exitosamente."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al hacer el registro: " . $conn->error]);
    }
    exit;
}

// Manejar Documentos
if ($accion == "manejarDocumentos") {
    $rutaBase = "img_control_vehicular";
    $rutaPlaca = $rutaBase . "/" . $placa;
    $rutaDocumentacion = $rutaPlaca . "/Documentacion";

    // Crear las carpetas si no existen
    if (!file_exists($rutaPlaca)) {
        if (!mkdir($rutaPlaca, 0777, true)) {
            echo json_encode([
                "success" => false,
                "message" => "Error al crear la carpeta de la placa: $rutaPlaca"
            ]);
            exit;
        }
    }

    if (!file_exists($rutaDocumentacion)) {
        if (!mkdir($rutaDocumentacion, 0777, true)) {
            echo json_encode([
                "success" => false,
                "message" => "Error al crear la carpeta de documentación: $rutaDocumentacion"
            ]);
            exit;
        }
    }

    $rutasArchivos = [];

    // Manejar Tarjeta de Circulación
    if (isset($_FILES['archivoCirculacion']) && $_FILES['archivoCirculacion']['error'] === UPLOAD_ERR_OK) {
        $nombreArchivo = "TarjetaCirculacion_" . date("Ymd_His") . "." . pathinfo($_FILES['archivoCirculacion']['name'], PATHINFO_EXTENSION);
        $rutaDestino = $rutaDocumentacion . "/" . $nombreArchivo;
        if (move_uploaded_file($_FILES['archivoCirculacion']['tmp_name'], $rutaDestino)) {
            $rutasArchivos['archivoCirculacion'] = $rutaDestino;
        }
    }

    // Manejar Refrendo
    if (isset($_FILES['archivoRefrendo']) && $_FILES['archivoRefrendo']['error'] === UPLOAD_ERR_OK) {
        $nombreArchivo = "Refrendo_" . date("Ymd_His") . "." . pathinfo($_FILES['archivoRefrendo']['name'], PATHINFO_EXTENSION);
        $rutaDestino = $rutaDocumentacion . "/" . $nombreArchivo;
        if (move_uploaded_file($_FILES['archivoRefrendo']['tmp_name'], $rutaDestino)) {
            $rutasArchivos['archivoRefrendo'] = $rutaDestino;
        }
    }

    // Manejar Póliza de Seguro
    if (isset($_FILES['archivoPoliza']) && $_FILES['archivoPoliza']['error'] === UPLOAD_ERR_OK) {
        $nombreArchivo = "PolizaSeguro_" . date("Ymd_His") . "." . pathinfo($_FILES['archivoPoliza']['name'], PATHINFO_EXTENSION);
        $rutaDestino = $rutaDocumentacion . "/" . $nombreArchivo;
        if (move_uploaded_file($_FILES['archivoPoliza']['tmp_name'], $rutaDestino)) {
            $rutasArchivos['archivoPoliza'] = $rutaDestino;
        }
    }

    // Manejar Verificación
    if (isset($_FILES['archivoVerificacion']) && $_FILES['archivoVerificacion']['error'] === UPLOAD_ERR_OK) {
        $nombreArchivo = "Verificacion_" . date("Ymd_His") . "." . pathinfo($_FILES['archivoVerificacion']['name'], PATHINFO_EXTENSION);
        $rutaDestino = $rutaDocumentacion . "/" . $nombreArchivo;
        if (move_uploaded_file($_FILES['archivoVerificacion']['tmp_name'], $rutaDestino)) {
            $rutasArchivos['archivoVerificacion'] = $rutaDestino;
        }
    }

    // Responder con la ruta de la carpeta y las rutas de los archivos
    echo json_encode([
        "success" => true,
        "rutaDocumentacion" => $rutaDocumentacion,
        "rutasArchivos" => $rutasArchivos
    ]);
    exit;
}
?>