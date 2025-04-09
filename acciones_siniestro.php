<?php
header('Content-Type: application/json');
include 'conn.php';
mysqli_set_charset($conn, "utf8mb4");

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
/*----------------------------------------------------------------------------*/

//Registro de Siniestro
if($accion == "registroSiniestro") {

    $sqlregistro = "INSERT INTO siniestros 
                    VALUES ('', '$id_coche', '$fecha', '$hora', '$kilometraje', '$gasolina', '$origen', '$destino', '$lugar', 
                            '$empresa', '$servicio', '$coordenadas', '$descripcion', '$partes_dañadas', '$ubicacion_vehiculo', '$contacto')";
    $resultregistro = $conn->query($sqlregistro);
    
    // Devolver los datos en formato JSON
    echo json_encode($resultregistro);
}
?>