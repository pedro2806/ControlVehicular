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

/*---------------------------------------------*/


//Registro de Mantenimiento
if ($accion == "RegistrarDocumentos") {
    $rutaBase = "img_control_vehicular";
    $rutaPlaca = $rutaBase . "/" . $placa;
    $rutaDocumentacion = $rutaPlaca . "/Documentacion";

    // Validar que $placa no esté vacío
    if (empty($placa)) {
        echo json_encode(["success" => false, "message" => "El valor de la placa está vacío."]);
        exit;
    }

    // Crear la carpeta principal con el nombre de la placa si no existe
    if (!file_exists($rutaPlaca)) {
        mkdir($rutaPlaca, 0777, true);
    }

    // Verificar y crear la subcarpeta "Documentacion"
    if (!file_exists($rutaDocumentacion)) {
        mkdir($rutaDocumentacion, 0777, true);
    }

    $rutasArchivos = [
        'archivoCirculacion' => 'S/R',
        'archivoRefrendo' => 'S/R',
        'archivoPoliza' => 'S/R',
        'archivoVerificacion' => 'S/R'
    ];

    // Procesar Tarjeta de Circulación
    if (isset($_FILES['archivoCirculacion']) && $_FILES['archivoCirculacion']['error'] === UPLOAD_ERR_OK) {
        $nombreArchivo = "TarjetaCirculacion_" . date("Ymd_His") . "." . pathinfo($_FILES['archivoCirculacion']['name'], PATHINFO_EXTENSION);
        $rutaDestino = $rutaDocumentacion . "/" . $nombreArchivo;
        if (move_uploaded_file($_FILES['archivoCirculacion']['tmp_name'], $rutaDestino)) {
            $rutasArchivos['archivoCirculacion'] = $rutaDestino;
        }
    }

    // Procesar Refrendo
    if (isset($_FILES['archivoRefrendo']) && $_FILES['archivoRefrendo']['error'] === UPLOAD_ERR_OK) {
        $nombreArchivo = "Refrendo_" . date("Ymd_His") . "." . pathinfo($_FILES['archivoRefrendo']['name'], PATHINFO_EXTENSION);
        $rutaDestino = $rutaDocumentacion . "/" . $nombreArchivo;
        if (move_uploaded_file($_FILES['archivoRefrendo']['tmp_name'], $rutaDestino)) {
            $rutasArchivos['archivoRefrendo'] = $rutaDestino;
        }
    }

    // Procesar Póliza de Seguro
    if (isset($_FILES['archivoPoliza']) && $_FILES['archivoPoliza']['error'] === UPLOAD_ERR_OK) {
        $nombreArchivo = "PolizaSeguro_" . date("Ymd_His") . "." . pathinfo($_FILES['archivoPoliza']['name'], PATHINFO_EXTENSION);
        $rutaDestino = $rutaDocumentacion . "/" . $nombreArchivo;
        if (move_uploaded_file($_FILES['archivoPoliza']['tmp_name'], $rutaDestino)) {
            $rutasArchivos['archivoPoliza'] = $rutaDestino;
        }
    }

    // Procesar Verificación
    if (isset($_FILES['archivoVerificacion']) && $_FILES['archivoVerificacion']['error'] === UPLOAD_ERR_OK) {
        $nombreArchivo = "Verificacion_" . date("Ymd_His") . "." . pathinfo($_FILES['archivoVerificacion']['name'], PATHINFO_EXTENSION);
        $rutaDestino = $rutaDocumentacion . "/" . $nombreArchivo;
        if (move_uploaded_file($_FILES['archivoVerificacion']['tmp_name'], $rutaDestino)) {
            $rutasArchivos['archivoVerificacion'] = $rutaDestino;
        }
    }

    // Registrar en la base de datos
    $sql = "INSERT INTO documentacion (id_vehiculo, fecha_registro, contacto, fecha_prox, tarjeta_circulacion, refrendo_actual, seguro_auto, verificacion_vigente)
            VALUES ('$id_vehiculo', '$fecha_registro', '$contacto', '$fecha_prox', '{$rutasArchivos['archivoCirculacion']}', '{$rutasArchivos['archivoRefrendo']}', '{$rutasArchivos['archivoPoliza']}', '{$rutasArchivos['archivoVerificacion']}')";
    $result = $conn->query($sql);

    if ($result) {
        echo json_encode(["success" => true, "message" => "Documentación registrada exitosamente."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al registrar la documentación: " . $conn->error]);
    }
    exit;
}
?>