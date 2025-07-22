<?php
include 'conn.php';
header('Content-Type: application/json');
mysqli_set_charset($conn, "utf8mb4");
date_default_timezone_set('America/Mexico_City');

$accion = $_POST["accion"];

$id_vehiculo = $_POST["id_vehiculo"];
$id_prestamo = $_POST["id_prestamo"];
$id_usuario = $_COOKIE['id_usuario'];
$id_checklist = $_POST["id_checklist"];
$id_dueno = $_POST["id_dueno"];
$id_recibe = $_POST["id_recibe"];
$noEmpleado = $_COOKIE['noEmpleado'];
$rol = $_COOKIE['rol'];

$fecha_inc_prestamo = $_POST["fecha_inc_prestamo"];
$fecha_fin_prestamo = $_POST["fecha_fin_prestamo"];
$fecha_entrega = $_POST["fecha_entrega"];
$fecha_entrega_inicio = $_POST["fecha_entrega_inicio"];
$fecha_entrega_final = $_POST["fecha_entrega_final"];
//$hora = date("H:i:s", strtotime($hora));

$km_inicio = $_POST["km_inicio"];
$km_fin = $_POST["km_fin"];

$motivo = $_POST["motivo"];
$notas_jefe = $_POST["notas_jefe"];
$notas_denegar = $_POST["notas_denegar"];
$notas_entrega = $_POST["notas_entrega"];
$notas_devolucion = $_POST["notas_devolucion"];

$estatus = $_POST["estatus"];
$placa = $_POST["placa"];
$gasolina_inicio = $_POST["gasolina_inicio"];
$gasolina_fin = $_POST["gasolina_fin"];

$tipo_uso = $_POST["tipo_uso"];
$detalle_tipo_uso = $_POST["detalle_tipo_uso"];
$notas_jefe = $_POST["notas_jefe"];
$destino = $_POST["destino"];
/*---------------------------------------------*/
//Registro de Prestamo
if ($accion == "RegistrarPrestamo") {
    $sqlregistro = "INSERT INTO prestamos
                    (fecha_registro, id_usuario, fecha_inc_prestamo, fecha_fin_prestamo, estatus, motivo_us, tipo_uso, detalle_tipo_uso, destino)
                    VALUES (NOW(), '$id_usuario', '$fecha_inc_prestamo', '$fecha_fin_prestamo', 'PENDIENTE', '$motivo', '$tipo_uso', '$detalle_tipo_uso', '$destino')";
    $resultregistro = $conn->query($sqlregistro); 
    if ($resultregistro) {
        echo json_encode(["success" => true, "message" => "Préstamo registrado exitosamente."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al registrar la solicitud: " . $conn->error]);
    }
    exit;
}

//Consulta de Prestamos
if ($accion == "consultarPrestamos") {
    if ($rol == 3) {
        // ROL 3 es jefe de área
        $sqlConsulta = "SELECT prest.id_prestamo, prest.id_vehiculo, inv.placa, inv.marca, inv.modelo, inv.color,
                        prest.fecha_inc_prestamo, prest.fecha_fin_prestamo, prest.estatus,
                        prest.tipo_uso, prest.detalle_tipo_uso, prest.motivo_us,
                        IFNULL((SELECT nombre FROM usuarios WHERE id_usuario = prest.id_usuario  LIMIT 1), 'S/R') AS nombre_usuario
                FROM prestamos prest
                LEFT JOIN inventario inv ON prest.id_vehiculo = inv.id_vehiculo
                WHERE prest.id_usuario IN (SELECT id_usuario FROM usuarios WHERE jefe = $noEmpleado UNION ALL SELECT $id_usuario)
                AND prest.estatus = 'PENDIENTE'";
    } elseif ($rol == 1) {
        // ROL 1 es usuario
        $sqlConsulta = "SELECT id_prestamo, id_vehiculo, fecha_inc_prestamo, fecha_fin_prestamo, estatus,
                            tipo_uso, detalle_tipo_uso, motivo_us,
                            IFNULL((SELECT nombre FROM usuarios WHERE id_usuario = prest.id_usuario  LIMIT 1), 'S/R') AS nombre_usuario
                        FROM prestamos prest
                        WHERE id_usuario = $id_usuario";
    } else {
        echo json_encode(["success" => false, "message" => "Rol no autorizado."]);
        exit;
    }
//echo $sqlConsulta;
    // Ejecutar la consulta
    $result = $conn->query($sqlConsulta);
    $prestamos = [];
    while ($row = $result->fetch_assoc()) {
        $prestamos[] = $row;
    }
    echo json_encode($prestamos);
}

//Consulta de Prestamos
if ($accion == "consultarPrestamosDetalle") {    
        $sqlConsulta = "SELECT prest.id_prestamo, prest.id_vehiculo, inv.placa, inv.marca, inv.modelo, inv.color, prest.id_vehiculo,
                        prest.fecha_inc_prestamo, prest.fecha_fin_prestamo, prest.estatus,
                        prest.tipo_uso, prest.detalle_tipo_uso, prest.motivo_us,
                        IFNULL((SELECT nombre FROM usuarios WHERE id_usuario = prest.id_usuario LIMIT 1), 'S/R') AS nombre_usuario
                FROM prestamos prest
                LEFT JOIN inventario inv ON prest.id_vehiculo = inv.id_vehiculo
                WHERE prest.id_prestamo = $id_prestamo
                ORDER BY prest.id_prestamo DESC";
    
//echo $sqlConsulta;
    // Ejecutar la consulta
    $result = $conn->query($sqlConsulta);
    $prestamos = [];
    while ($row = $result->fetch_assoc()) {
        $prestamos[] = $row;
    }
    echo json_encode($prestamos);
}

//Consulta de Prestamos Otra Area
if ($accion == "consultarPrestamosOtraArea") {
    if ($rol == 3) {
        // ROL 3 es jefe de área
        $sqlConsulta ="WITH CombinedPrestamos AS (
    -- Primera parte
    SELECT
        prest.id_prestamo,
        prest.id_vehiculo,
        inv.placa,
        inv.marca,
        inv.modelo,
        inv.color,
        prest.fecha_inc_prestamo,
        prest.fecha_fin_prestamo,
        prest.estatus,
        prest.tipo_uso,
        prest.detalle_tipo_uso,
        prest.motivo_us,
        IFNULL((SELECT nombre FROM usuarios WHERE id_usuario = prest.id_usuario LIMIT 1), 'S/R') AS nombre_usuario,
        1 AS source_type
    FROM prestamos prest
    LEFT JOIN inventario inv ON prest.id_vehiculo = inv.id_vehiculo
    WHERE inv.id_vehiculo IN (
        SELECT id_vehiculo
        FROM inventario
        WHERE id_usuario IN (
            SELECT id_usuario FROM usuarios WHERE jefe = $id_usuario
            UNION ALL
            SELECT $id_usuario
        )
    )
    AND prest.estatus = 'PENDIENTEAREA'

    UNION ALL

    -- Segunda parte
    SELECT
        prest.id_prestamo,
        prest.id_vehiculo,
        inv.placa,
        inv.marca,
        inv.modelo,
        inv.color,
        prest.fecha_inc_prestamo,
        prest.fecha_fin_prestamo,
        prest.estatus,
        prest.tipo_uso,
        prest.detalle_tipo_uso,
        prest.motivo_us,
        IFNULL((SELECT nombre FROM usuarios WHERE id_usuario = prest.id_usuario LIMIT 1), 'S/R') AS nombre_usuario,
        2 AS source_type
    FROM prestamos prest
    LEFT JOIN inventario inv ON prest.id_vehiculo = inv.id_vehiculo
    WHERE prest.id_usuario = $id_usuario
),

Duplicated AS (
    SELECT id_prestamo, COUNT(*) AS cnt
    FROM CombinedPrestamos
    GROUP BY id_prestamo
)

SELECT
    cp.id_prestamo,
    cp.id_vehiculo,
    cp.placa,
    cp.marca,
    cp.modelo,
    cp.color,
    cp.fecha_inc_prestamo,
    cp.fecha_fin_prestamo,
    cp.estatus,
    cp.tipo_uso,
    cp.detalle_tipo_uso,
    cp.motivo_us,
    cp.nombre_usuario,
    cp.source_type,
    CASE
        WHEN d.cnt > 1 THEN 'DUPLICATED'
        ELSE 'UNIQUE'
    END AS duplicate_indicator
FROM CombinedPrestamos cp
LEFT JOIN Duplicated d ON cp.id_prestamo = d.id_prestamo";
        /*
        $sqlConsulta = "SELECT prest.id_prestamo, prest.id_vehiculo, inv.placa, inv.marca, inv.modelo, inv.color,
                        prest.fecha_inc_prestamo, prest.fecha_fin_prestamo, prest.estatus,
                        prest.tipo_uso, prest.detalle_tipo_uso, prest.motivo_us,
                        IFNULL((SELECT nombre FROM usuarios WHERE id_usuario = prest.id_usuario LIMIT 1), 'S/R') AS nombre_usuario, '1' as tipoU
                FROM prestamos prest
                LEFT JOIN inventario inv ON prest.id_vehiculo = inv.id_vehiculo
                WHERE inv.id_vehiculo IN (SELECT id_vehiculo FROM inventario WHERE id_usuario IN (SELECT id_usuario FROM usuarios WHERE jefe = $noEmpleado UNION ALL SELECT $id_usuario))                
                AND prest.estatus = 'PENDIENTEAREA'
                UNION
                SELECT prest.id_prestamo, prest.id_vehiculo, inv.placa, inv.marca, inv.modelo, inv.color,
                        prest.fecha_inc_prestamo, prest.fecha_fin_prestamo, prest.estatus,
                        prest.tipo_uso, prest.detalle_tipo_uso, prest.motivo_us,
                        IFNULL((SELECT nombre FROM usuarios WHERE id_usuario = prest.id_usuario LIMIT 1), 'S/R') AS nombre_usuario, '0' as tipoU
                FROM prestamos prest
                LEFT JOIN inventario inv ON prest.id_vehiculo = inv.id_vehiculo
                WHERE prest.id_usuario = $id_usuario
                GROUP BY prest.id_prestamo";*/
    } elseif ($rol == 1) {
        // ROL 1 es usuario
        $sqlConsulta = "SELECT id_prestamo, prest.id_vehiculo, fecha_inc_prestamo, fecha_fin_prestamo, prest.estatus,
                            tipo_uso, detalle_tipo_uso, motivo_us,
                            IFNULL((SELECT nombre FROM usuarios WHERE id_usuario = prest.id_usuario LIMIT 1), 'S/R') AS nombre_usuario
                        FROM prestamos prest
                        LEFT JOIN inventario inv ON prest.id_vehiculo = inv.id_vehiculo
                        WHERE prest.id_usuario = $id_usuario";
    } else {
        echo json_encode(["success" => false, "message" => "Rol no autorizado."]);
        exit;
    }
    //echo $sqlConsulta;
    // Ejecutar la consulta
    $result = $conn->query($sqlConsulta);
    $prestamos = [];
    while ($row = $result->fetch_assoc()) {
        $prestamos[] = $row;
    }
    echo json_encode($prestamos);
}

//Consulta Actualizar Prestamo
if ($accion == "actualizarPrestamo") {
    $sqlConsulta = "SELECT prest.id_prestamo, 
                        prest.id_vehiculo, 
                        IFNULL(prest.tipo_uso, 'S/R') AS tipo_uso, 
                        IFNULL(prest.detalle_tipo_uso, 'S/R') AS detalle_tipo_uso, 
                        IFNULL(prest.motivo_us, 'S/R') AS motivo_us, 
                        IFNULL(prest.notas_jefe, 'S/R') AS notas_jefe,
                        IFNULL(prest.fecha_entrega, 'S/R') AS fecha_entrega,
                        IFNULL(prest.fecha_fin_prestamo, 'S/R') AS fecha_fin_prestamo, 
                        IFNULL((SELECT inv.placa FROM inventario inv WHERE inv.id_vehiculo = prest.id_vehiculo), 'S/R') AS placa,
                        IFNULL((SELECT inv.modelo FROM inventario inv WHERE inv.id_vehiculo = prest.id_vehiculo), 'S/R') AS modelo,
                        IFNULL((SELECT nombre FROM usuarios WHERE id_usuario = prest.id_usuario LIMIT 1), 'S/R') AS nombre_usuario, 'VERIFICAR' AS  accion
                    FROM prestamos prest
                    WHERE prest.id_usuario IN (SELECT id_usuario FROM usuarios WHERE jefe = $noEmpleado UNION ALL SELECT $id_usuario)
                    AND prest.estatus = 'AUTORIZADO'
                    UNION
                    SELECT prest.id_prestamo, 
                        prest.id_vehiculo, 
                        IFNULL(prest.tipo_uso, 'S/R') AS tipo_uso, 
                        IFNULL(prest.detalle_tipo_uso, 'S/R') AS detalle_tipo_uso, 
                        IFNULL(prest.motivo_us, 'S/R') AS motivo_us, 
                        IFNULL(prest.notas_jefe, 'S/R') AS notas_jefe,
                        IFNULL(prest.fecha_entrega, 'S/R') AS fecha_entrega,
                        IFNULL(prest.fecha_fin_prestamo, 'S/R') AS fecha_fin_prestamo, 
                        IFNULL((SELECT inv.placa FROM inventario inv WHERE inv.id_vehiculo = prest.id_vehiculo), 'S/R') AS placa,
                        IFNULL((SELECT inv.modelo FROM inventario inv WHERE inv.id_vehiculo = prest.id_vehiculo), 'S/R') AS modelo,
                        IFNULL((SELECT nombre FROM usuarios WHERE id_usuario = prest.id_usuario LIMIT 1), 'S/R') AS nombre_usuario, 'NOVERIFICAR' AS  accion
                    FROM prestamos prest
                    WHERE prest.id_autoriza = $id_usuario
                    AND prest.estatus = 'AUTORIZADO'
                    ";
    $result = $conn->query($sqlConsulta);
    $prestamos = [];
    while ($row = $result->fetch_assoc()) {
        $prestamos[] = $row;
    }
    echo json_encode($prestamos);
}

//Consulta Prestamos EN CURSO
if ($accion == "consultarPrestamosEnCurso") {
    $sqlConsulta= " SELECT prest.id_prestamo, prest.id_vehiculo, inv.placa, inv.marca, inv.modelo, inv.color, 
                        prest.fecha_inc_prestamo, prest.fecha_fin_prestamo, prest.estatus,
                        prest.tipo_uso, prest.detalle_tipo_uso, prest.motivo_us, inv.modelo                    
                    FROM prestamos prest
                    INNER JOIN inventario inv ON prest.id_vehiculo = inv.id_vehiculo
                    WHERE prest.id_usuario IN (SELECT id_usuario FROM usuarios WHERE jefe = $noEmpleado UNION ALL SELECT $id_usuario)
                    AND prest.estatus = 'EN CURSO'
                    ";
    $result = $conn->query($sqlConsulta);
    $prestamos = [];
    while ($row = $result->fetch_assoc()) {
        $prestamos[] = $row;
    }
    echo json_encode($prestamos);
}

//Aprobar Prestamo
if ($accion == "autorizarPrestamo") {    

    $parts = explode(',', $id_vehiculo);

    $idV = $parts[0];
    $tipo = $parts[1];

    if($tipo_uso == '1'){
        $sqlAutoriza = "UPDATE prestamos 
                        SET estatus = 'AUTORIZADO', fecha_registro_asignado = NOW(), 
                            notas_jefe = '$notas_jefe', fecha_entrega = '$fecha_entrega',  id_autoriza = '$id_usuario'                            
                        WHERE id_prestamo = '$id_prestamo'";
    }
    else{
        if($tipo == 'EXTERNO') {
            $sqlAutoriza = "UPDATE prestamos 
                        SET estatus = 'PENDIENTEAREA', fecha_registro_asignado = NOW(), 
                            notas_jefe = '$notas_jefe', fecha_entrega = '$fecha_entrega',  id_autoriza = '$id_usuario',
                            id_vehiculo = '$idV'
                        WHERE id_prestamo = '$id_prestamo'";
        } else {
            $sqlAutoriza = "UPDATE prestamos 
                        SET estatus = 'AUTORIZADO', fecha_registro_asignado = NOW(), 
                            notas_jefe = '$notas_jefe', fecha_entrega = '$fecha_entrega',  id_autoriza = '$id_usuario',
                            id_vehiculo = '$idV'
                        WHERE id_prestamo = '$id_prestamo'";    
        }
    }
    
    $resultAutoriza = $conn->query($sqlAutoriza);
    if ($resultAutoriza) {
        echo json_encode(["success" => true]);
        cambiarAsignado($conn, $idV); 
    } else {
        echo json_encode(["success" => false, "error" => $conn->error]);
    }
    exit;
}

//Denegar Prestamo
if ($accion == "denegarPrestamo") {
    $sqlDenegar = " UPDATE prestamos 
                    SET estatus = 'DENEGADO', notas_jefe = '$notas_denegar', 
                        fecha_registro_asignado = NOW(), id_autoriza = '$id_usuario'
                    WHERE id_prestamo = '$id_prestamo'";
    $resultDenegar = $conn->query($sqlDenegar);

    if ($resultDenegar) {
        echo json_encode(["success" => true, "message" => "Préstamo denegado exitosamente."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al denegar el préstamo: " . $conn->error]);
    }
    exit;
}

//Consulta de PRESTAMOS TERMINADOS
if ($accion == "consultarPrestamosTerminados") {
    $sqlConsulta = "SELECT prest.id_prestamo, prest.id_vehiculo, inv.placa, inv.marca, inv.modelo, inv.color,
                    prest.fecha_inc_prestamo, prest.fecha_fin_prestamo, prest.estatus,
                    prest.tipo_uso, prest.detalle_tipo_uso, prest.motivo_us,
                    IFNULL((SELECT nombre FROM usuarios WHERE id_usuario = prest.id_usuario), 'S/R') AS nombre_usuario
            FROM prestamos prest
            LEFT JOIN inventario inv ON prest.id_vehiculo = inv.id_vehiculo
            WHERE prest.id_usuario IN (SELECT id_usuario FROM usuarios WHERE jefe = $noEmpleado UNION ALL SELECT $id_usuario)
            AND prest.estatus IN ('FINALIZADO', 'CANCELADO')
            ORDER BY prest.id_prestamo DESC";
    $result = $conn->query($sqlConsulta);
    $prestamos = [];
    while ($row = $result->fetch_assoc()) {
        $prestamos[] = $row;
    }
    echo json_encode($prestamos);
}

//Cambia información de un vehículo en el modal de préstamo
if ($accion == "obtenerInfoVehiculo") {
    $sqlInfo = "SELECT modelo, color, estatus, asignado FROM inventario WHERE id_vehiculo = '$id_vehiculo'";
    $resultInfo = $conn->query($sqlInfo);

    if ($resultInfo->num_rows > 0) {
        $vehiculo = $resultInfo->fetch_assoc();
        echo json_encode(["success" => true, "vehiculo" => $vehiculo]);
    } else {
        echo json_encode(["success" => false, "message" => "No se encontró información del vehículo."]);
    }
    exit;
}

//Iniciar Préstamo
if ($accion == "iniciarPrestamo") {
    $sql = "UPDATE prestamos 
            SET estatus = 'EN CURSO', notas_entrega = '$notas_entrega', fecha_registro_entrega = NOW() 
            WHERE id_prestamo = '$id_prestamo'";
            
    $result = $conn->query($sql);
    if ($result) {
        if (!empty($placa)) {
            // Crear carpeta principal y subcarpeta "Prestamos"
            $carpetaPlaca = "img_control_vehicular/$placa";
            if (!file_exists($carpetaPlaca)) {
                mkdir($carpetaPlaca, 0777, true);
            }
            $carpetaPrestamo = "$carpetaPlaca/Prestamos";
            if (!file_exists($carpetaPrestamo)) {
                mkdir($carpetaPrestamo, 0777, true);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo crear la carpeta porque la placa está vacía.']);
            exit;
        }

        // Manejo de archivos (fotografía de inicio)
        $ruta_destino_inicio = null; // Inicializar la variable para la ruta de la foto
        if (
            isset($_FILES['fotos_inicio']['name']) &&
            (
                (is_array($_FILES['fotos_inicio']['name']) && !empty($_FILES['fotos_inicio']['name'][0])) ||
                (!is_array($_FILES['fotos_inicio']['name']) && !empty($_FILES['fotos_inicio']['name']))
            )
        ) {
            $names = is_array($_FILES['fotos_inicio']['name']) ? $_FILES['fotos_inicio']['name'] : [$_FILES['fotos_inicio']['name']];
            $tmps = is_array($_FILES['fotos_inicio']['tmp_name']) ? $_FILES['fotos_inicio']['tmp_name'] : [$_FILES['fotos_inicio']['tmp_name']];
            foreach ($tmps as $key => $tmp_name) {
                $file_name = $names[$key];
                $file_tmp = $tmps[$key];
                $fecha_actual = date('Ymd_His'); // Formato YYYYMMDD_HHMMSS
                $nuevo_nombre = "{$placa}_Prestamo_{$fecha_actual}";
                $extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $ruta_destino_actual = "$carpetaPrestamo/$nuevo_nombre.$extension";

                if (move_uploaded_file($file_tmp, $ruta_destino_actual)) {
                    if ($key === 0) { // Guardar la ruta del primer archivo para la base de datos
                        $ruta_destino_inicio = $ruta_destino_actual;
                    }
                } else {
                    error_log("Error al subir el archivo: $file_name");
                }
            }
        }

        // Actualizar la ruta de la foto de inicio en la base de datos
            
            // Actualizar la tabla actividad_vehiculo con la foto de inicio
            /*$sqlActualizarActividad = "INSERT INTO actividad_vehiculo (id_prestamo, id_vehiculo, id_usuario, km_actual, gasolina_actual, foto_url, fecha_actividad, tipo_actividad)
                                        VALUES ('$id_prestamo', '$id_vehiculo', '$id_usuario', '$km_inicio', '$gasolina_inicio', '$ruta_destino_inicio', NOW(), 'INICIO')";
            
            $conn->query($sqlActualizarActividad);
            */

        echo json_encode(['success' => true, 'message' => 'Préstamo iniciado exitosamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al iniciar el préstamo: ' . $conn->error]);
    }
    exit;
}

// Finalizar Préstamo
if ($accion == "finalizarPrestamo") {
    $sql = "UPDATE prestamos 
            SET estatus = 'FINALIZADO', notas_devolucion = '$notas_devolucion', fecha_registro_devolucion = NOW(), 
                id_recibe = '$id_recibe', id_autoriza = '$id_usuario' 
            WHERE id_prestamo = '$id_prestamo'";
    $result = $conn->query($sql);

    if ($result) {
        if (!empty($placa)) {
            // Crear carpeta principal y subcarpeta "Devoluciones"
            $carpetaPlaca = "img_control_vehicular/$placa";
            if (!file_exists($carpetaPlaca)) {
                mkdir($carpetaPlaca, 0777, true);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo crear la carpeta porque la placa está vacía.']);
            exit;
        }

        // Manejo de archivos 
        $ruta_destino_devolucion = null; // Inicializar la variable para la ruta de la foto
        if (!empty($_FILES['fotos_final']['name'][0])) {
            foreach ($_FILES['fotos_final']['tmp_name'] as $key => $tmp_name) {
                $file_name = $_FILES['fotos_final']['name'][$key];
                $file_tmp = $_FILES['fotos_final']['tmp_name'][$key];
                $fecha_actual = date('Ymd_His'); // Formato YYYYMMDD_HHMMSS
                $nuevo_nombre = "{$placa}_Devolucion_{$fecha_actual}_{$key}";
                $extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $ruta_destino_actual = "img_control_vehicular/$placa/Prestamos/$nuevo_nombre.$extension"; 

                if (move_uploaded_file($file_tmp, $ruta_destino_actual)) {
                    if ($key === 0) { // Guardar la ruta del primer archivo para la base de datos
                        $ruta_destino_devolucion = $ruta_destino_actual;
                    }
                } else {
                    error_log("Error al subir el archivo: $file_name");
                }
            }
        }

        // Actualizar la ruta de la foto de devolución en la base de datos
        
            // Actualizar la tabla actividad_vehiculo con la foto de devolución
            $sqlActualizarActividad = "INSERT INTO actividad_vehiculo (id_prestamo, id_vehiculo, id_usuario, km_actual, gasolina_actual, foto_url, fecha_actividad, tipo_actividad)
                                        VALUES ('$id_prestamo', '$id_vehiculo', '$id_usuario', '$km_fin', '$gasolina_fin', '$ruta_destino_devolucion', NOW(), 'FINALIZACION')";
            $conn->query($sqlActualizarActividad);           
        

        echo json_encode(['success' => true, 'message' => 'Préstamo finalizado exitosamente.']);
        cambiarAsignadoNo($conn, $id_vehiculo); 
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al finalizar el préstamo: ' . $conn->error]);
    }
    exit;
}

//Consulta de Usuarios con Rol 3
if ($accion == "consultarUsuariosRecibe") {
    $sql = "SELECT id_usuario, nombre FROM usuarios WHERE rol = 3 AND estatus = 1";
    $result = $conn->query($sql);

    $usuarios = [];
    while ($row = $result->fetch_assoc()) {
        $usuarios[] = $row;
    }
    echo json_encode($usuarios);
    exit;
}

//Cambiar ASGINADO a NO
function cambiarAsignado($conn, $id_vehiculo) {
    $sql = "UPDATE inventario SET asignado = 'SI' WHERE id_vehiculo = '$id_vehiculo'";
    $result = $conn->query($sql);
}

//Cambiar ASGINADO a SI
function cambiarAsignadoNo($conn, $id_vehiculo) {
    $sql = "UPDATE inventario SET asignado = 'NO' WHERE id_vehiculo = '$id_vehiculo'";
    $result = $conn->query($sql);
}
?>