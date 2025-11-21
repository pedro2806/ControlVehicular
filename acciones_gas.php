<?php
include 'conn.php';
header('Content-Type: application/json');
mysqli_set_charset($conn, "utf8mb4");
date_default_timezone_set('America/Mexico_City');

$id_usuario = $_COOKIE['id_usuario'];
$noEmpleado = $_COOKIE['noEmpleado'];
$id_vehiculo = isset($_POST['id_vehiculo']) ? $_POST['id_vehiculo'] : null;

$accion = isset($_POST['accion']) ? $_POST['accion'] : null;

$placa = isset($_POST['placa']) ? $_POST['placa'] : null;
$monto = isset($_POST['monto']) ? $_POST['monto'] : null;
$pagos = isset($_POST['pagos']) ? $_POST['pagos'] : null;
$saldo = isset($_POST['saldo']) ? $_POST['saldo'] : null;
$km_actual = isset($_POST['km_actual']) ? $_POST['km_actual'] : null;
$fecha_carga = isset($_POST['fecha_carga']) ? $_POST['fecha_carga'] : null;
$fecha_registro = isset($_POST['fecha_registro']) ? $_POST['fecha_registro'] : null;

    //FUNCION PARA REGISTRAR CARGA
    if ($accion == 'registraGas'){
        $sqlR = "INSERT INTO carga_gasolina (`id_usuario`, `id_vehiculo`, `monto`, `pagos`, `saldo`, `km_actual`, `fecha_carga`, `fecha_registro`) 
                VALUES ('$id_usuario', '$id_vehiculo', '$monto', '$pagos', '$saldo', '$km_actual', '$fecha_carga', now())";
            
        $resultR = $conn->query($sqlR);
        if($resultR){
            echo json_encode(array("status" => "success", "message" => "Carga de gasolina registrada correctamente."));
        } else {
            echo json_encode(array("status" => "error", "message" => "Error al registrar la carga de gasolina: " . $conn->error));
        }
        exit;
    }

    //FUNCION PARA OBTENER DATOS DE LA ULTIMA CARGA
    if ($accion == 'obtenerRegistrosGas'){
        $sqlU = "SELECT ca.*, CONCAT(inv.placa, ' - ', inv.modelo, ' - ', inv.marca) AS Vehiculo, inv.usuario
                FROM `carga_gasolina`  ca
                INNER JOIN inventario inv ON inv.id_vehiculo = ca.id_vehiculo
                WHERE inv.id_vehiculo IN (SELECT id_vehiculo FROM inventario WHERE id_usuario = '".$_COOKIE['id_usuario']."')
                ORDER BY ca.id DESC";
        
        $resultU = $conn->query($sqlU);
        $registros = array();
        if ($resultU) {
            while ($row = $resultU->fetch_assoc()) {
                $registros[] = $row;
            }
            echo json_encode($registros);
        } else {
            echo json_encode(array("status" => "error", "message" => "Error al obtener la última carga de gasolina: " . $conn->error));
        }
        exit;
    }
?>