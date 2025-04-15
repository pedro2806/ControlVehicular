<!-- filepath: c:\xampp\htdocs\ControlVehicular\acciones_documentacion.php -->
<?php
include 'conn.php';
header('Content-Type: application/json');
mysqli_set_charset($conn, "utf8mb4");

$accion = $_POST['accion'];

if ($accion == "guardarDocumentacion") {
    $id_coche = $_POST['id_coche']; // Asegúrate de recibir el ID del coche
    $rutaBase = "documentacion_vehiculos";
    $rutaCoche = $rutaBase . "/" . $id_coche;

    if (!file_exists($rutaCoche)) {
        mkdir($rutaCoche, 0777, true);
    }

    $documentos = [
        "tarjeta_circulacion" => "Tarjeta_Circulacion",
        "refrendo_actual" => "Refrendo_Actual",
        "seguro_auto" => "Seguro_Auto",
        "verificacion_vigente" => "Verificacion_Vigente"
    ];

    $rutasGuardadas = [];
    foreach ($documentos as $campo => $nombreArchivo) {
        if (isset($_FILES[$campo]) && $_FILES[$campo]['error'] === UPLOAD_ERR_OK) {
            $extension = pathinfo($_FILES[$campo]['name'], PATHINFO_EXTENSION);
            $rutaDestino = $rutaCoche . "/" . $nombreArchivo . "." . $extension;

            if (move_uploaded_file($_FILES[$campo]['tmp_name'], $rutaDestino)) {
                $rutasGuardadas[$campo] = $rutaDestino;
            } else {
                echo json_encode(["success" => false, "message" => "Error al guardar el archivo: $campo"]);
                exit;
            }
        }
    }

    // Guardar las rutas en la base de datos
    $sql = "INSERT INTO c_Documentacion (id_Coche, Tarjeta_circulacion, Refrendo_actual, Seguro_auto, Verificacion_vigente)
            VALUES ('$id_coche', '{$rutasGuardadas['tarjeta_circulacion']}', '{$rutasGuardadas['refrendo_actual']}', '{$rutasGuardadas['seguro_auto']}', '{$rutasGuardadas['verificacion_vigente']}')
            ON DUPLICATE KEY UPDATE 
            Tarjeta_circulacion = '{$rutasGuardadas['tarjeta_circulacion']}', 
            Refrendo_actual = '{$rutasGuardadas['refrendo_actual']}', 
            Seguro_auto = '{$rutasGuardadas['seguro_auto']}', 
            Verificacion_vigente = '{$rutasGuardadas['verificacion_vigente']}'";

    if ($conn->query($sql)) {
        echo json_encode(["success" => true, "message" => "Documentación guardada exitosamente."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al guardar en la base de datos: " . $conn->error]);
    }
    exit;
}
?>