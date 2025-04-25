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
                    (id_vehiculo, fecha_registro, contacto, fecha_prox, tarjeta_circulacion, refrendo_actual, seguro_auto, verificacion_vigente)
                    VALUES ('$id_vehiculo', '$fecha_registro', '$contacto', '$fecha_prox', '$tarjeta_circulacion', '$refrendo_actual', '$seguro_auto', '$verificacion_vigente')";                   
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
    error_log("Iniciando manejo de documentos para la placa: " . $placa);

    $rutaBase = "img_control_vehicular";
    $rutaPlaca = $rutaBase . "/" . $placa;
    $rutaDocumentacion = $rutaPlaca . "/Documentacion";

    // Crear las carpetas si no existen
    if (!file_exists($rutaPlaca)) {
        if (!mkdir($rutaPlaca, 0777, true)) {
            error_log("Error al crear la carpeta de la placa: " . $rutaPlaca);
            echo json_encode(["success" => false, "message" => "Error al crear la carpeta de la placa."]);
            exit;
        }
    }

    if (!file_exists($rutaDocumentacion)) {
        if (!mkdir($rutaDocumentacion, 0777, true)) {
            error_log("Error al crear la carpeta de documentación: " . $rutaDocumentacion);
            echo json_encode(["success" => false, "message" => "Error al crear la carpeta de documentación."]);
            exit;
        }
    }

    $rutasArchivos = [];

    // Manejar Tarjeta de Circulación
    if (isset($_FILES['archivoCirculacion'])) {
        error_log("Procesando archivo de Tarjeta de Circulación...");
        if ($_FILES['archivoCirculacion']['error'] === UPLOAD_ERR_OK) {
            $nombreArchivo = "TarjetaCirculacion_" . date("Ymd_His") . "." . pathinfo($_FILES['archivoCirculacion']['name'], PATHINFO_EXTENSION);
            $rutaDestino = $rutaDocumentacion . "/" . $nombreArchivo;
            if (move_uploaded_file($_FILES['archivoCirculacion']['tmp_name'], $rutaDestino)) {
                $rutasArchivos['archivoCirculacion'] = $rutaDestino;
                error_log("Archivo de Tarjeta de Circulación guardado en: " . $rutaDestino);
            } else {
                error_log("Error al mover el archivo de Tarjeta de Circulación.");
            }
        } else {
            error_log("Error en el archivo de Tarjeta de Circulación: " . $_FILES['archivoCirculacion']['error']);
        }
    }

    // Repite el mismo proceso para los demás archivos (Refrendo, Póliza, Verificación)
    // ...

    // Responder con las rutas de los archivos
    if (!empty($rutasArchivos)) {
        echo json_encode(["success" => true, "rutasArchivos" => $rutasArchivos]);
    } else {
        error_log("No se procesaron archivos correctamente.");
        echo json_encode(["success" => false, "message" => "No se procesaron archivos correctamente."]);
    }
    exit;
}
?>