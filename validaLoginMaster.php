<?php
include 'conn.php';

$accion = isset($_POST['accion']) ? $_POST['accion'] : '';

// Validación de usuario (otro caso)
$id_usuario = isset($_POST['id_usuarioCV']) ? $_POST['id_usuarioCV'] : '';
$nombredelusuario = isset($_POST['nombredelusuarioCV']) ? $_POST['nombredelusuarioCV'] : '';
$noEmpleado = isset($_POST['noEmpleadoCV']) ? $_POST['noEmpleadoCV'] : '';
$rol = isset($_POST['rolCV']) ? $_POST['rolCV'] : '';
$usuario = isset($_POST['correoCV']) ? $_POST['correoCV'] : '';


    $Qempresas = "SELECT cv.*, TIMESTAMPDIFF(YEAR, cv.fechaIngreso, CURDATE()) AS antiguedad,
                         TRIM(CONCAT(IFNULL(rrhh.nombres,''), ' ', IFNULL(rrhh.apellidos,''))) AS nombre_completo
                  FROM usuarios cv
                  LEFT JOIN mess_rrhh.usuarios rrhh ON rrhh.noEmpleado = cv.noEmpleado
                  WHERE cv.usuario = '" . mysqli_real_escape_string($conn, $usuario) . "' AND cv.estatus = 1";
    $res2 = mysqli_query($conn, $Qempresas) or die(mysqli_error($conn));
    $nr   = mysqli_num_rows($res2);

    while ($row2 = mysqli_fetch_array($res2)) {
        $id_usuario     = $row2["id_usuario"];
        $nombreEmpleado = ($row2["nombre_completo"] !== '') ? $row2["nombre_completo"] : $row2["nombre"];
        $noEmpleado     = $row2["noEmpleado"];
        $antiguedad     = $row2["antiguedad"];
        $diasD          = $row2["diasdisponibles"];
        $rol            = $row2["rol"];
        $gps            = $row2["gps"];
    }

    if($nr == 1){

        if ($gps == "1") {            
            echo '<script>document.cookie = "gps=activo; expires=" + new Date(Date.now() + 99900000).toUTCString() + ";SameSite=Lax;";</script>';
        } else {            
            echo '<script>document.cookie = "gps=inactivo; expires=" + new Date(Date.now() + 99900000).toUTCString() + ";SameSite=Lax;";</script>';
        }

        echo '<script>document.cookie = "id_usuario='.$id_usuario.';expires=" + new Date(Date.now() + 99900000).toUTCString() + ";SameSite=Lax;";</script>';
        echo '<script>document.cookie = "nombredelusuario='.$nombreEmpleado.';expires=" + new Date(Date.now() + 99900000).toUTCString() + ";SameSite=Lax;";</script>';
        echo '<script>document.cookie = "noEmpleado='.$noEmpleado.';expires=" + new Date(Date.now() + 99900000).toUTCString() + ";SameSite=Lax;";</script>';
        echo '<script>document.cookie = "rol='.$rol.';expires=" + new Date(Date.now() + 99900000).toUTCString() + ";SameSite=Lax;";</script>';
        echo '<script>document.cookie = "SesionLogin=LoginMaster; expires=" + new Date(Date.now() + 99900000).toUTCString() + ";SameSite=Lax;";</script>';
        echo '<script>document.cookie = "navSesion=Navegador; expires=" + new Date(Date.now() + 99900000).toUTCString() + ";SameSite=Lax;";</script>';
        echo '<script>window.location.assign("inicio")</script>';

        echo json_encode(['success' => true]);
        exit;
    }

    // Si no hay usuario válido
    echo json_encode([
        'success' => false,
        'message' => 'Usuario no válido.',
    ]);
    exit;

?>