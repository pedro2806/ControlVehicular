<?php
include 'conn.php';
header('Content-Type: application/json');
mysqli_set_charset($conn, "utf8mb4");
date_default_timezone_set('America/Mexico_City');

// Obtener datos del POST
$id_vehiculo = isset($_POST['vehiculoAsignado']) ? $_POST['vehiculoAsignado'] : null;
$id_prestamo = (isset($_POST['id_prestamo']) && $_POST['id_prestamo'] !== '' && $_POST['id_prestamo'] !== 'undefined') ? $_POST['id_prestamo'] : null;
$km_inicio = isset($_POST['kmActual']) ? $_POST['kmActual'] : null;
$notas = isset($_POST['notasCheckin']) ? $_POST['notasCheckin'] : null;
$patron = (isset($_POST['patronRelacionado']) && $_POST['patronRelacionado'] !== '' && $_POST['patronRelacionado'] !== 'undefined') ? $_POST['patronRelacionado'] : null;
$accion = isset($_POST['accion']) ? $_POST['accion'] : null;
$gasActual = isset($_POST['gasActual']) ? $_POST['gasActual'] : null;
$otRelacionada = isset($_POST['otRelacionada']) ? $_POST['otRelacionada'] : null;
$tipoServicio = isset($_POST['tipoServicio']) ? $_POST['tipoServicio'] : null;
$placa = isset($_POST['placa']) ? $_POST['placa'] : null;
$coordenadas = isset($_POST['coordenadas']) ? $_POST['coordenadas'] : null;
$ruta_destino_inicio = '';
$finalizarPrestamo = isset($_POST['finalizarPrestamo']) ? $_POST['finalizarPrestamo'] : 'No';
$ruta = isset($_POST['ruta']) ? $_POST['ruta'] : null;
$costoOv = isset($_POST['costoOv']) ? $_POST['costoOv'] : null;

function subirImagenesCheckin($imagenes, $id_actividad, $id_vehiculo, $conn, $placa, $actividad) {

    // Crear carpeta principal y subcarpeta "Actividades"
    $carpetaPlaca = "img_control_vehicular/$placa";
    if (!file_exists($carpetaPlaca)) {
        mkdir($carpetaPlaca, 0777, true);
    }
    $carpetaActividad = "$carpetaPlaca/Actividades";
    if (!file_exists($carpetaActividad)) {
        mkdir($carpetaActividad, 0777, true);
    }

    if (!isset($imagenes) || !is_array($imagenes)) {
        return;
    }
    foreach ($imagenes['tmp_name'] as $key => $tmp_name) {
        if ($imagenes['error'][$key] === UPLOAD_ERR_OK) {
            
            static $consecutivo = 1;
            $extension = pathinfo($imagenes['name'][$key], PATHINFO_EXTENSION);
            $nombreArchivo = $placa . '_' . $actividad . '_' . $consecutivo . '.' . $extension;
            $consecutivo++;
            $ruta_destino = $carpetaActividad.'/' . $nombreArchivo;

            if (move_uploaded_file($tmp_name, $ruta_destino)) {
                // Guardar la ruta de la imagen en la base de datos
                $stmt = $conn->prepare("INSERT INTO fotos (formato, id_formato, id_vehiculo, imagen) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssis", $actividad, $id_actividad, $id_vehiculo, $ruta_destino);
                $stmt->execute();
                $stmt->close();

            }
        }
    }
}

// Si recibes archivos (foto), puedes procesarlos aquí
/*
if (isset($_FILES['foto']) && $_FILES['foto']['error'] == UPLOAD_ERR_OK) {
    $nombreArchivo = uniqid('foto_', true) . '.' . pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
    $ruta_destino_inicio = 'uploads/' . $nombreArchivo;
    move_uploaded_file($_FILES['foto']['tmp_name'], $ruta_destino_inicio);
}*/

if ($accion == 'CargarVehiculos'){
    $sql = "SELECT id_vehiculo, placa, marca, modelo, color, '' as id_prestamo, '' as estatus
            FROM inventario Where id_usuario = '".$_COOKIE['id_usuario']."' OR id_us_asignado = '".$_COOKIE['id_usuario']."'
            UNION
            SELECT inv.id_vehiculo, inv.placa, inv.marca, inv.modelo, inv.color, p.id_prestamo, p.estatus
            FROM inventario inv
            INNER JOIN prestamos p ON inv.id_vehiculo = p.id_vehiculo
            WHERE (p.id_usuario = '".$_COOKIE['id_usuario']."') AND (p.estatus = 'AUTORIZADO' OR p.estatus = 'EN CURSO')";
            /*SELECT inv.id_vehiculo, inv.placa, inv.marca, inv.modelo, inv.color, p.id_prestamo, p.estatus
            FROM inventario inv
            INNER JOIN prestamos p ON inv.id_vehiculo = p.id_vehiculo
            WHERE (p.id_usuario = '".$_COOKIE['id_usuario']."' OR id_us_asignado = '".$_COOKIE['id_usuario']."') AND (p.estatus = 'AUTORIZADO' OR p.estatus = 'EN CURSO')";
            */
    
    //SELECT id_vehiculo, placa, marca, modelo, color FROM inventario Where id_usuario = '".$_COOKIE['id_usuario']."' OR id_us_asignado = '".$_COOKIE['id_usuario']."'";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $vehiculos = [];
        while ($row = $result->fetch_assoc()) {
            $vehiculos[] = $row;
        }
        echo json_encode($vehiculos);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se encontraron vehículos activos.']);
    }
    exit;
}

if($accion == 'CapturaCheckIn'){
    // Insertar los datos en la base de datos
    $sql = "INSERT INTO actividad_vehiculo (id_prestamo, id_vehiculo, id_usuario, km_actual, foto_url, fecha_actividad, tipo_actividad, notas, patron, gasolina_actual, ot, detalle_tipo_uso, coordenadas, ruta, costoOv)
            VALUES ('$id_prestamo', '$id_vehiculo', '".$_COOKIE['id_usuario']."', '$km_inicio', '$ruta_destino_inicio', NOW(), 'INICIO', '$notas', '$patron', '$gasActual','$otRelacionada', '$tipoServicio', '$coordenadas', '$ruta', '$costoOv')";

    if ($conn->query($sql) === TRUE) {
        //actualizar el estatus del préstamo a 'EN CURSO'
            if (!empty($id_prestamo)) {
                $updatePrestamo = "UPDATE prestamos SET estatus = 'EN CURSO' WHERE id_prestamo = '$id_prestamo'";
                if ($conn->query($updatePrestamo) !== TRUE) {
                    //echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el préstamo: ' . $conn->error]);
                    //exit;
                }
            }

            $consultaUltimaActividad = "SELECT id_actividad FROM actividad_vehiculo WHERE id_usuario = '" . $_COOKIE['id_usuario'] . "' ORDER BY fecha_actividad DESC, id_actividad DESC LIMIT 1";
            $resultUltimaActividad = $conn->query($consultaUltimaActividad);
            $idUltimaActividad = null;

            if ($resultUltimaActividad && $resultUltimaActividad->num_rows > 0) {
                $rowUltimaActividad = $resultUltimaActividad->fetch_assoc();
                $idUltimaActividad = $rowUltimaActividad['id_actividad'];
                // Ahora $idUltimaActividad contiene solo el id de la última actividad
            }
            // Procesar imágenes de check-in si existen
            if (isset($_FILES['imgCheckin'])) {
                subirImagenesCheckin($_FILES['imgCheckin'], $idUltimaActividad, $id_vehiculo, $conn, $placa, 'checkin');
            }

            // update asignado SI del inventario
            $updateInventario = "UPDATE inventario SET asignado = 'SI' WHERE id_vehiculo = '$id_vehiculo'";
            if ($conn->query($updateInventario) !== TRUE) {
                //echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el vehículo asignado: ' . $conn->error]);
                //exit;
            }

        echo json_encode(['status' => 'success', 'message' => 'Check-in realizado correctamente.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al realizar el check-in: ' . $conn->error]);
    }
    exit;
}

if($accion == 'CapturaCheckOut'){
    // Insertar los datos en la base de datos
    $sql = "INSERT INTO actividad_vehiculo (id_prestamo, id_vehiculo, id_usuario, km_actual, foto_url, fecha_actividad, tipo_actividad, notas, patron, gasolina_actual, ot, detalle_tipo_uso, coordenadas, ruta, costoOv)
            VALUES ('$id_prestamo', '$id_vehiculo', '".$_COOKIE['id_usuario']."', '$km_inicio', '$ruta_destino_inicio', NOW(), 'FINALIZACION', '$notas', '$patron', '$gasActual', '$otRelacionada', '$tipoServicio', '$coordenadas', '$ruta', '$costoOv')";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(['status' => 'success', 'message' => 'Check-out realizado correctamente.']);
        // Actualizar el estatus del préstamo a 'FINALIZADO'
        $updatePrestamo = "UPDATE prestamos SET estatus = 'FINALIZADO' WHERE id_prestamo = '$id_prestamo'";
        if (!empty($id_prestamo) && $finalizarPrestamo == 'Si') {
            if ($conn->query($updatePrestamo) === TRUE) {
                //echo json_encode(['status' => 'success', 'message' => 'Préstamo actualizado a FINALIZADO.']);
            }
        }

        // Obtener el ID de la última actividad del usuario
            $consultaUltimaActividad = "SELECT id_actividad FROM actividad_vehiculo WHERE id_usuario = '" . $_COOKIE['id_usuario'] . "' ORDER BY fecha_actividad DESC, id_actividad DESC LIMIT 1";
            $resultUltimaActividad = $conn->query($consultaUltimaActividad);
            $idUltimaActividad = null;

            if ($resultUltimaActividad && $resultUltimaActividad->num_rows > 0) {
                $rowUltimaActividad = $resultUltimaActividad->fetch_assoc();
                $idUltimaActividad = $rowUltimaActividad['id_actividad'];
                // Ahora $idUltimaActividad contiene solo el id de la última actividad
            }
        // Procesar imágenes de check-out si existen
            if (isset($_FILES['imgCheckinNuevo'])) {
                subirImagenesCheckin($_FILES['imgCheckinNuevo'], $idUltimaActividad, $id_vehiculo, $conn, $placa, 'checkout');
            }

            // update asignado SI del inventario
            $updateInventario = "UPDATE inventario SET asignado = 'NO' WHERE id_vehiculo = '$id_vehiculo'";
            if ($conn->query($updateInventario) !== TRUE) {
                //echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el vehículo asignado: ' . $conn->error]);
                //exit;
            }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al realizar el check-out: ' . $conn->error]);
    }
    exit;
}

if($accion == 'ActividadesPendientes'){
    // Consultar las actividades pendientes de inicio para el usuario actual
    $sql = "SELECT
                av_ultima.id_usuario,
                av_ultima.id_vehiculo,
                i.placa,
                i.marca,
                i.modelo,
                av_ultima.notas,
                av_ultima.tipo_actividad AS ultima_actividad_registrada,
                av_ultima.fecha_actividad AS fecha_ultima_actividad,
                av_ultima.patron,
                av_ultima.ot,
                av_ultima.detalle_tipo_uso,
                av_ultima.id_prestamo,
                av_ultima.km_actual,
                av_ultima.gasolina_actual                
            FROM
                actividad_vehiculo av_ultima
            INNER JOIN
                inventario i ON av_ultima.id_vehiculo = i.id_vehiculo
            WHERE
                av_ultima.id_usuario = $_COOKIE[id_usuario]
                AND av_ultima.fecha_actividad = (
                    SELECT MAX(av_max.fecha_actividad)
                    FROM actividad_vehiculo av_max
                    WHERE av_max.id_usuario = av_ultima.id_usuario
                    AND av_max.id_vehiculo = av_ultima.id_vehiculo
                )
                AND av_ultima.tipo_actividad = 'INICIO'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $actividades = [];
        while ($row = $result->fetch_assoc()) {
        $actividades[] = $row;
        }
        echo json_encode($actividades);
    } else {
        echo json_encode(['status' => 'success', 'data' => []]);
    }
    exit;
}

if($accion == 'Actividades'){
    // Consultar las actividades del usuario actual
    $sql = "SELECT av.*, i.placa, i.marca, i.modelo, (select u.nombre from usuarios u where u.id_usuario = av.id_usuario) as usuario
        FROM actividad_vehiculo av
        INNER JOIN inventario i ON av.id_vehiculo = i.id_vehiculo        
        WHERE av.id_usuario = '".$_COOKIE['id_usuario']."'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $actividades = [];
        while ($row = $result->fetch_assoc()) {
            $actividades[] = $row;
        }
        echo json_encode(['status' => 'success', 'actividades' => $actividades]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se encontraron actividades.']);
    }
    exit;
}

if($accion == 'ActividadesCalendario'){
    // Consultar las actividades del usuario actual
    $sql ="WITH ActividadesOrdenadas AS (
        SELECT
            av.*,
            ROW_NUMBER() OVER (PARTITION BY av.id_usuario, av.id_vehiculo ORDER BY av.fecha_actividad, av.id_actividad) as rn,
            LEAD(av.tipo_actividad) OVER (PARTITION BY av.id_usuario, av.id_vehiculo ORDER BY av.fecha_actividad, av.id_actividad) as siguiente_tipo_actividad,
            LEAD(av.fecha_actividad) OVER (PARTITION BY av.id_usuario, av.id_vehiculo ORDER BY av.fecha_actividad, av.id_actividad) as siguiente_fecha_actividad,
            LEAD(av.km_actual) OVER (PARTITION BY av.id_usuario, av.id_vehiculo ORDER BY av.fecha_actividad, av.id_actividad) as siguiente_km_actual,
            LEAD(av.gasolina_actual) OVER (PARTITION BY av.id_usuario, av.id_vehiculo ORDER BY av.fecha_actividad, av.id_actividad) as siguiente_gasolina_actual,
            LEAD(av.foto_url) OVER (PARTITION BY av.id_usuario, av.id_vehiculo ORDER BY av.fecha_actividad, av.id_actividad) as siguiente_foto_url,
            LEAD(av.notas) OVER (PARTITION BY av.id_usuario, av.id_vehiculo ORDER BY av.fecha_actividad, av.id_actividad) as siguiente_notas,
            LEAD(av.patron) OVER (PARTITION BY av.id_usuario, av.id_vehiculo ORDER BY av.fecha_actividad, av.id_actividad) as siguiente_patron,
            LEAD(av.ot) OVER (PARTITION BY av.id_usuario, av.id_vehiculo ORDER BY av.fecha_actividad, av.id_actividad) as siguiente_ot,
            LEAD(av.id_actividad) OVER (PARTITION BY av.id_usuario, av.id_vehiculo ORDER BY av.fecha_actividad, av.id_actividad) as id_actividad_fin,
            LEAD(av.id_prestamo) OVER (PARTITION BY av.id_usuario, av.id_vehiculo ORDER BY av.fecha_actividad, av.id_actividad) as id_prestamo_fin
            -- Agrega más columnas de LEAD si necesitas más datos de la fila 'FIN'
        FROM
            actividad_vehiculo av
        WHERE av.id_usuario = '$_COOKIE[id_usuario]' -- Considera un usuario específico como en tu ejemplo
    )
    SELECT
        ao.id_usuario,
        ao.id_vehiculo,
        i.placa,
        i.marca,
        i.modelo,
        ao.id_actividad AS id_actividad_inicio,
        ao.id_prestamo AS id_prestamo_inicio,
        ao.tipo_actividad AS tipo_actividad_inicio,
        ao.fecha_actividad AS fecha_inicio,
        ao.km_actual AS km_inicio,
        ao.gasolina_actual AS gasolina_inicio,
        ao.coordenadas AS coordenadas_inicio,
        ao.foto_url AS foto_url_inicio,
        ao.notas AS notas_inicio,
        ao.patron AS patron_inicio,
        ao.ot AS ot_inicio,
        u.nombre,
        -- Datos de la FINALIZACION
        CASE WHEN ao.siguiente_tipo_actividad = 'FINALIZACION' THEN ao.id_actividad_fin ELSE NULL END AS id_actividad_fin,
        CASE WHEN ao.siguiente_tipo_actividad = 'FINALIZACION' THEN ao.id_prestamo_fin ELSE NULL END AS id_prestamo_fin,
        CASE WHEN ao.siguiente_tipo_actividad = 'FINALIZACION' THEN ao.siguiente_tipo_actividad ELSE NULL END AS tipo_actividad_fin,
        CASE WHEN ao.siguiente_tipo_actividad = 'FINALIZACION' THEN ao.siguiente_fecha_actividad ELSE NULL END AS fecha_fin,
        CASE WHEN ao.siguiente_tipo_actividad = 'FINALIZACION' THEN ao.siguiente_km_actual ELSE NULL END AS km_fin,
        CASE WHEN ao.siguiente_tipo_actividad = 'FINALIZACION' THEN ao.siguiente_gasolina_actual ELSE NULL END AS gasolina_fin,
        -- No hay 'coordenadas_fin' si solo se registra al inicio
        CASE WHEN ao.siguiente_tipo_actividad = 'FINALIZACION' THEN ao.siguiente_foto_url ELSE NULL END AS foto_url_fin,
        CASE WHEN ao.siguiente_tipo_actividad = 'FINALIZACION' THEN ao.siguiente_notas ELSE NULL END AS notas_fin,
        CASE WHEN ao.siguiente_tipo_actividad = 'FINALIZACION' THEN ao.siguiente_patron ELSE NULL END AS patron_fin,
        CASE WHEN ao.siguiente_tipo_actividad = 'FINALIZACION' THEN ao.siguiente_ot ELSE NULL END AS ot_fin
    FROM
        ActividadesOrdenadas ao
    INNER JOIN inventario i ON ao.id_vehiculo = i.id_vehiculo
    INNER JOIN usuarios u ON ao.id_usuario = u.id_usuario
    WHERE ao.tipo_actividad = 'INICIO'    
    ORDER BY ao.id_usuario, ao.id_vehiculo, ao.fecha_actividad;";
        /*$sql = "SELECT av.*, i.placa, i.marca, i.modelo, (select u.nombre from usuarios u where u.id_usuario = av.id_usuario) as usuario
            FROM actividad_vehiculo av
            INNER JOIN inventario i ON av.id_vehiculo = i.id_vehiculo        
            WHERE av.id_usuario = '".$_COOKIE['id_usuario']."'";*/
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $actividades = [];
        while ($row = $result->fetch_assoc()) {
            $actividades[] = $row;
        }
        echo json_encode(['status' => 'success', 'actividades' => $actividades]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No se encontraron actividades.']);
    }
    exit;
}
if($accion == 'ActividadesCalendarioPlaneadas'){
    // Consultar las actividades planeadas del usuario actual
    $sql = "SELECT p.fecha_inc_prestamo, p.fecha_fin_prestamo, p.motivo_us, p.detalle_tipo_uso, p.estatus, u.nombre,
                IF(i.placa IS NULL OR i.placa = '', 'Vehiculo por asignar', i.placa) AS placa,
                IF(i.modelo IS NULL OR i.modelo = '', '', i.modelo) AS modelo,
                IF(i.marca IS NULL OR i.marca = '', '', i.marca) AS marca
            FROM prestamos p
            INNER JOIN usuarios u ON p.id_usuario = u.id_usuario
            LEFT JOIN inventario i ON p.id_vehiculo = i.id_vehiculo";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $actividades = [];
        while ($row = $result->fetch_assoc()) {
            $actividades[] = $row;
        }
        echo json_encode(['status' => 'success', 'actividades' => $actividades]);
    }   
}
?>