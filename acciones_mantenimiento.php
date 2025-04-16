<?php
include 'conn.php';
header('Content-Type: application/json');
mysqli_set_charset($conn, "utf8mb4");
date_default_timezone_set('America/Mexico_City');

$accion = $_POST["accion"];

$id_mantenimiento = $_POST["id_mantenimiento"];
$id_vehiculo = $_POST["id_vehiculo"];
$fecha_registro = date("Y-m-d H:i:s");
$kilometraje = $_POST["kilometraje"];
$gasolina = $_POST["gasolina"];
$tipo_mantenimiento = $_POST["tipo_mantenimiento"];
$descripcion = $_POST["descripcion"];
$solicitante = $_POST["solicitante"];
$VoBo_jefe = $_POST["VoBo_jefe"];
$fecha_proxi = $_POST["fecha_proxi"];
$km_proxi = $_POST["km_proxi"];
$tipo_carro = $_POST["tipo_carro"];
$id_dueno = $_POST["id_dueno"];

$noEmpleado = $_COOKIE['noEmpleado'];
$id_usuario = $_COOKIE['id_usuario'];
$foto = $_POST["rutaImagen"];
$placa = $_POST["placa"];
$foto = $_POST["rutaImagen"];

$id_mantenimiento = $_POST["id_mantenimiento"];
$notas = $_POST['comentario'];
$fecha_programada = $_POST['fecha_programada'];
/*---------------------------------------------*/


//Registro de Mantenimiento
if ($accion == "RegistrarMantenimiento") {

    $sqlregistro = "INSERT INTO mantenimientos
                    (id_vehiculo, fecha_registro, kilometraje, gasolina, tipo_mantenimiento, descripcion, solicitante, VoBo_jefe, 
                    fecha_proxi, km_proxi, tipo_carro, id_dueno, foto)
                    VALUES ('$id_vehiculo', '$fecha_registro', '$kilometraje', '$gasolina', '$tipo_mantenimiento', '$descripcion', '$solicitante', 'PENDIENTE' ,
                    '$fecha_proxi', '$km_proxi', '$tipo_carro', '$id_dueno', '$foto')";                   
    $resultregistro = $conn->query($sqlregistro);
    if ($resultregistro) {
        echo json_encode(["success" => true, "message" => "Mantenimiento registrado exitosamente."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al registrar el mantenimiento: " . $conn->error]);
    }
    exit;
}

//Creacion de carpeta
if ($accion == "manejarCarpetasYFoto") {
    
    $rutaBase = "img_control_vehicular";
    $rutaPlaca = $rutaBase . "/" . $placa;
    $rutaMantenimiento = $rutaPlaca . "/Mantenimiento";

    if (!file_exists($rutaPlaca)) {
        mkdir($rutaPlaca, 0777, true);
    }
    if (!file_exists($rutaMantenimiento)) {
        mkdir($rutaMantenimiento, 0777, true);
    }

    // Manejar la subida de la imagen
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $rutaImagen = $placa . "_Mantenimiento_" . date("Ymd_his") . "." . pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $rutaTemporal = $_FILES['foto']['tmp_name'];
        $rutaDestino = $rutaMantenimiento . "/" . $rutaImagen;

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

//Consulta de Usuarios
if ($accion == "consultarUsuarios") {
    $sql = "SELECT id_usuario, nombre FROM usuarios";
    $result = $conn->query($sql);

    $usuarios = [];
    while ($row = $result->fetch_assoc()) {
        $usuarios[] = $row;
    }

    echo json_encode($usuarios);
    exit;
}

//Consulta de Mantenimientos
if ($accion == "consultarMantenimientos") {
    $sqlConsulta = "SELECT mant.id_mantenimiento, mant.id_vehiculo , mant.fecha_registro, mant.kilometraje, mant.gasolina, mant.tipo_mantenimiento, 
                           mant.descripcion, mant.VoBo_jefe, inv.placa
                    FROM mantenimientos mant
                    INNER JOIN inventario inv ON mant.id_vehiculo = inv.id_vehiculo
                    WHERE mant.VoBo_jefe = 'PENDIENTE'";
    $result = $conn->query($sqlConsulta);

    $mantenimientos = [];
    while ($row = $result->fetch_assoc()) {
        $mantenimientos[] = $row;
    }

    echo json_encode($mantenimientos);
}

//Aprobar Mantenimiento
if ($accion == "autorizarMantenimiento") {
    $sqlAutoriza = "UPDATE mantenimientos 
            SET VoBo_jefe = 'Autorizado', notas = '$notas', fecha_programada = '$fecha_programada' 
            WHERE id_mantenimiento = '$id_mantenimiento'";
    $resultAutoriza = $conn->query($sqlAutoriza);
    echo json_encode(["success" => true]);
}

//Denegar Mantenimiento
if ($accion == "denegarMantenimiento") {
    $sqlDenegar = "UPDATE mantenimientos 
            SET VoBo_jefe = 'Denegado', notas = '$notas', fecha_programada = '$fecha_programada' 
            WHERE id_mantenimiento = '$id_mantenimiento'";
    $resultDenegar = $conn->query($sqlDenegar);

    echo json_encode(["success" => true]);
}
?>