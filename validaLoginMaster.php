<?php
include 'conn.php';

$accion = isset($_POST['accion']) ? $_POST['accion'] : '';

// Validación de usuario (otro caso)
$id_usuario = isset($_POST['id_usuario']) ? $_POST['id_usuario'] : '';
$nombredelusuario = isset($_POST['nombredelusuario']) ? $_POST['nombredelusuario'] : '';
$noEmpleado = isset($_POST['noEmpleado']) ? $_POST['noEmpleado'] : '';
$rol = isset($_POST['rol']) ? $_POST['rol'] : '';
$usuario = isset($_POST['correo']) ? $_POST['correo'] : '';

if (empty($id_usuario) || empty($nombredelusuario) || empty($noEmpleado) || empty($rol)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
    exit;
} else {
    $Qempresas  =  "SELECT  *, TIMESTAMPDIFF(YEAR,fechaIngreso,CURDATE()) AS antiguedad, rol FROM usuarios WHERE usuario  = '".$usuario."' AND estatus = 1";
    $res2 =  mysqli_query( $conn, $Qempresas ) or die (mysqli_error($conn));
    $nr = mysqli_num_rows($res2);

    while ($row2 = mysqli_fetch_array($res2)){
        $nombreEmpleado = $row2["nombre"];
        $noEmpleado = $row2["noEmpleado"];
        $antiguedad = $row2["antiguedad"];
        $diasD = $row2["diasdisponibles"];
        $rol = $row2["rol"];
    }

    if($nr == 1){
        setcookie('Lantiguedad', $antiguedad, time() + 604800, "/ControlVehicular", "", false, true);

        session_start();
        $_SESSION['nombredelusuario'] = $nombreEmpleado;
        $_SESSION['noEmpleado'] = $noEmpleado;
        $_SESSION['rol'] = $rol;
        $_SESSION['correo'] = $usuario;
        $_SESSION['id_usuario'] = $id_usuario;

        echo json_encode(['success' => true]);
        exit;
    }
    //if (!isset($_COOKIE['antiguedad']) || $_COOKIE['antiguedad'] != $antiguedad) {
       //     setcookie('Lantiguedad', $antiguedad, time() + 604800, "/", "", false, true);
        //}
    // Si no hay usuario válido
    echo json_encode([
        'success' => false,
        'message' => 'Usuario no válido.',
    ]);
    exit;
}
?>