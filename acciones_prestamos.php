<?php
include 'includes/api_bootstrap.php';

$accion = $_POST["accion"] ?? '';

$id_vehiculo = $_POST["id_vehiculo"] ?? null;
$id_prestamo = $_POST["id_prestamo"] ?? null;
$id_usuario = $_COOKIE['id_usuario'] ?? null;
$id_checklist = $_POST["id_checklist"] ?? null;
$id_dueno = $_POST["id_dueno"] ?? null;
$id_recibe = $_POST["id_recibe"] ?? null;
$noEmpleado = $_COOKIE['noEmpleado'] ?? null;
$rol = $_COOKIE['rol'] ?? null;

$fecha_inc_prestamo = $_POST["fecha_inc_prestamo"] ?? null;
$fecha_fin_prestamo = $_POST["fecha_fin_prestamo"] ?? null;
$fecha_entrega = $_POST["fecha_entrega"] ?? null;
$fecha_entrega_inicio = $_POST["fecha_entrega_inicio"] ?? null;
$fecha_entrega_final = $_POST["fecha_entrega_final"] ?? null;

$km_inicio = $_POST["km_inicio"] ?? null;
$km_fin = $_POST["km_fin"] ?? null;

$motivo = $_POST["motivo"] ?? null;
$notas_jefe = $_POST["notas_jefe"] ?? null;
$notas_denegar = $_POST["notas_denegar"] ?? null;
$notas_entrega = $_POST["notas_entrega"] ?? null;
$notas_devolucion = $_POST["notas_devolucion"] ?? null;

$estatus = $_POST["estatus"] ?? null;
$placa = $_POST["placa"] ?? null;
$gasolina_inicio = $_POST["gasolina_inicio"] ?? null;
$gasolina_fin = $_POST["gasolina_fin"] ?? null;

$tipo_uso = $_POST["tipo_uso"] ?? null;
$detalle_tipo_uso = $_POST["detalle_tipo_uso"] ?? null;
$notas_jefe = $_POST["notas_jefe"] ?? null;
$destino = $_POST["destino"] ?? null;
/*---------------------------------------------*/
//Registro de Prestamo
if ($accion == "RegistrarPrestamo") {
    $sqlregistro = "INSERT INTO prestamos
                    (fecha_registro, id_usuario, fecha_inc_prestamo, fecha_fin_prestamo, estatus, motivo_us, tipo_uso, destino, id_vehiculo)
                    VALUES (NOW(), '$id_usuario', '$fecha_inc_prestamo', '$fecha_fin_prestamo', 'PENDIENTE', '$motivo', '$tipo_uso',  '$destino', '$id_vehiculo')";
    $resultregistro = $conn->query($sqlregistro); 
    if ($resultregistro) {
        echo json_encode(["success" => true, "message" => "Préstamo registrado exitosamente."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al registrar la solicitud: " . $conn->error]);
    }
    exit;
}

// Función genérica para consultar préstamos por estatus
function consultarPrestamosPorEstatus($conn, $estatus, $id_usuario, $noEmpleado, $usarFechasActividad = false) {

}

if ($accion == "consultarPrestamos") {
    ///$estatus, $id_usuario, $noEmpleado, 
        $usarFechasActividad = false;
        $estatusArray = is_array($estatus) ? $estatus : [$estatus];
        $estatusStr = "'" . implode("','", $estatusArray) . "'";
        $esPendiente = (count($estatusArray) === 1 && $estatusArray[0] === 'PENDIENTE');

        // Detectar si es jefe por jerarquía (sin depender de cookie rol)
        $stmtJefe = $conn->prepare("SELECT 1 FROM usuarios WHERE jefe = ? LIMIT 1");
        $stmtJefe->bind_param("s", $noEmpleado);
        $stmtJefe->execute();
        $esJefe = $stmtJefe->get_result()->num_rows > 0;
        $stmtJefe->close();

        $selectFechas = $usarFechasActividad
            ? "(SELECT MAX(fecha_actividad) FROM actividad_vehiculo WHERE id_vehiculo = prest.id_vehiculo AND tipo_actividad = 'INICIO') AS fecha_inc_prestamo,
            (SELECT MAX(fecha_actividad) FROM actividad_vehiculo WHERE id_vehiculo = prest.id_vehiculo AND tipo_actividad = 'FINALIZACION') AS fecha_fin_prestamo,"
            : "prest.fecha_inc_prestamo, prest.fecha_fin_prestamo,";

        $camposExtra = $esPendiente ? "" : ", prest.notas_jefe, prest.id_usuario, prest.fecha_entrega,
                        (SELECT MAX(km_actual) FROM actividad_vehiculo WHERE id_vehiculo = prest.id_vehiculo) AS km";

        $propiedadCol = $esJefe
            ? ", CASE WHEN inv.id_usuario = $id_usuario THEN 'mio' ELSE 'otro' END AS propiedad_vehiculo"
            : "";

        if ($esJefe) {
            $whereRol = "(prest.id_usuario IN (SELECT id_usuario FROM usuarios WHERE jefe = $noEmpleado UNION ALL SELECT $id_usuario)
                        OR prest.id_vehiculo IN (SELECT id_vehiculo FROM inventario WHERE id_usuario = $id_usuario))";
        } else {
            $whereRol = "prest.id_usuario = $id_usuario";
        }

        $sqlConsulta = "SELECT prest.id_prestamo, prest.id_vehiculo, inv.placa, inv.marca, inv.modelo, inv.color,
                        $selectFechas
                        prest.estatus, prest.tipo_uso, prest.detalle_tipo_uso, prest.motivo_us,
                        IFNULL((SELECT nombre FROM usuarios WHERE id_usuario = prest.id_usuario LIMIT 1), 'S/R') AS nombre_usuario,
                        inv.usuario AS valida, prest.fecha_registro
                        $propiedadCol $camposExtra
                FROM prestamos prest
                LEFT JOIN inventario inv ON prest.id_vehiculo = inv.id_vehiculo
                WHERE $whereRol
                ORDER BY prest.id_prestamo DESC";
                //WHERE $whereRol AND prest.estatus IN ($estatusStr)
        //echo $sqlConsulta; // Para depuración, puedes eliminarlo después
        $result = $conn->query($sqlConsulta);
        $prestamos = [];
        if ($result) { while ($row = $result->fetch_assoc()) { $prestamos[] = $row; } }
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
    $result = $conn->query($sqlConsulta);
    $prestamos = [];
    while ($row = $result->fetch_assoc()) {
        $prestamos[] = $row;
    }
    echo json_encode($prestamos);
}

//Consulta de Prestamos Otra Area
if ($accion == "consultarPrestamosOtraArea") {
    if ($rol == 3 || $rol == 2) {
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

//Aprobar Prestamo
if ($accion == "autorizarPrestamo") {    

    $parts = explode(',', $id_vehiculo);

    $idV =  $_POST["id_vehiculo"];// $parts[0];
    $tipo =  $_POST["tipo_uso"];// $parts[1];

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
    $sql = "SELECT id_usuario, nombre FROM usuarios WHERE rol = 3 AND estatus = 1 ORDER BY nombre ASC";
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