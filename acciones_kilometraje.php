<?php
include 'includes/api_bootstrap.php';
require_once __DIR__ . '/calcular_ruta.php';

// Calcula el km de un check-in del modal global. Origen = GPS capturado en
// `coordenadas` ("lat, lng"); destino = actividad OTROS del día o cliente OV/OT.
// Síncrono (no async) porque el front redirige tras el éxito y mataría la
// petición. Se envuelve en try/catch para no romper el check-in si falla.
function calcularKmCheckinGlobal(mysqli $conn, ?string $coordenadas, ?string $ovOt, $idActividad): void {
    try {
        $p    = array_map('trim', explode(',', (string) $coordenadas));
        $oLat = isset($p[0]) && $p[0] !== '' ? floatval($p[0]) : null;
        $oLng = isset($p[1]) && $p[1] !== '' ? floatval($p[1]) : null;
        $idUsuario = intval($_COOKIE['id_usuarioL'] ?? $_COOKIE['id_usuario'] ?? 0) ?: null;
        if ($oLat !== null && $oLng !== null && intval($idActividad) > 0) {
            procesarKmCheckin($conn, (string) $ovOt, $oLat, $oLng, $idUsuario, intval($idActividad), date('Y-m-d H:i:s'));
        }
    } catch (Throwable $e) { /* el cálculo de km nunca debe tumbar el check-in */ }
}

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
// El inicio de préstamo no manda 'placa', y sin ella las fotos del check-in se
// guardaban fuera de la carpeta del vehículo (img_control_vehicular//Actividades).
// Se resuelve desde la BD, que además no depende de lo que mande el cliente.
if (($placa === null || trim($placa) === '') && intval($id_vehiculo) > 0) {
    $stmtPlaca = $conn->prepare("SELECT placa FROM inventario WHERE id_vehiculo = ? LIMIT 1");
    if ($stmtPlaca) {
        $idVehPlaca = intval($id_vehiculo);
        $stmtPlaca->bind_param("i", $idVehPlaca);
        $stmtPlaca->execute();
        $rowPlaca = $stmtPlaca->get_result()->fetch_assoc();
        $stmtPlaca->close();
        if ($rowPlaca && $rowPlaca['placa'] !== '') { $placa = $rowPlaca['placa']; }
    }
}
$coordenadas = isset($_POST['coordenadas']) ? $_POST['coordenadas'] : null;
$ruta_destino_inicio = '';
$finalizarPrestamo = isset($_POST['finalizarPrestamo']) ? $_POST['finalizarPrestamo'] : 'No';
$ruta = isset($_POST['ruta']) ? $_POST['ruta'] : null;
$costoOv = isset($_POST['costoOv']) ? $_POST['costoOv'] : null;

include_once 'includes/subir_imagenes.php';

// Si recibes archivos (foto), puedes procesarlos aquí
/*
if (isset($_FILES['foto']) && $_FILES['foto']['error'] == UPLOAD_ERR_OK) {
    $nombreArchivo = uniqid('foto_', true) . '.' . pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
    $ruta_destino_inicio = 'uploads/' . $nombreArchivo;
    move_uploaded_file($_FILES['foto']['tmp_name'], $ruta_destino_inicio);
}*/

if ($accion == 'CargarVehiculos'){

        // Resolver el id de usuario de forma robusta: preferir id_usuarioL (el id de
        // CV que setea el login) y caer a id_usuario si aquel viene vacío. Antes usaba
        // solo id_usuario, que en algunas sesiones venía vacío -> 0 filas -> error.
        $idU = intval($_COOKIE['id_usuarioL'] ?? 0);
        if ($idU <= 0) { $idU = intval($_COOKIE['id_usuario'] ?? 0); }
        if ($idU <= 0) {
            // Sin usuario válido no se consulta (evita hacer match con id_usuario=0 = S/A).
            echo json_encode(['status' => 'error', 'message' => 'No se encontraron vehículos activos.']);
            exit;
        }

        $sql = "SELECT id_vehiculo, placa, marca, modelo, color, '' as id_prestamo, '' as estatus
            FROM inventario Where id_usuario = $idU OR id_us_asignado = $idU
            UNION
            SELECT inv.id_vehiculo, inv.placa, inv.marca, inv.modelo, inv.color, p.id_prestamo, p.estatus
            FROM inventario inv
            INNER JOIN prestamos p ON inv.id_vehiculo = p.id_vehiculo
            WHERE (p.id_usuario = $idU) AND (p.estatus = 'AUTORIZADO' OR p.estatus = 'EN CURSO')";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
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

if ($accion == 'CargarVehiculosPTenencia'){
    $sql = "SELECT id_vehiculo, placa, marca, modelo
            FROM inventario
            WHERE id_usuario = '".$_COOKIE['id_usuario']."' OR id_us_asignado = '".$_COOKIE['id_usuario']."'";
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $vehiculos = [];
        while ($row = $result->fetch_assoc()) {
            $vehiculos[] = $row;
        }
        echo json_encode(['success' => true, 'vehiculos' => $vehiculos]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontraron vehículos asignados.']);
    }
    exit;
}


if ($accion == 'tomaKm'){

    $IDvehiculoAsignado = intval($_POST['IDvehiculoAsignado'] ?? 0);

    // Subconsultas escalares independientes: SIEMPRE devuelve 1 fila (con NULL donde
    // no haya datos). Antes usaba INNER JOIN actividad_vehiculo + carga_gasolina, que
    // devolvía 0 filas (y error) si el vehículo aún no tenía actividad NI cargas.
    //   gasolina_actual = nivel de gasolina de la última actividad
    //   saldo           = saldo de la última carga de gasolina (si hay)
    $stmt = $conn->prepare(
        "SELECT
            (SELECT av.gasolina_actual FROM actividad_vehiculo av
             WHERE av.id_vehiculo = ?
             ORDER BY av.id_actividad DESC LIMIT 1) AS gasolina_actual,
            (SELECT cg.saldo FROM carga_gasolina cg
             WHERE cg.id_vehiculo = ?
             ORDER BY cg.id DESC LIMIT 1) AS saldo"
    );
    $stmt->bind_param("ii", $IDvehiculoAsignado, $IDvehiculoAsignado);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: ['gasolina_actual' => null, 'saldo' => null];
    $stmt->close();

    // kmMax sale del helper compartido (includes/api_bootstrap.php), que mira las TRES
    // fuentes de odómetro y devuelve la lectura más reciente. Antes esta consulta solo
    // veía actividad_vehiculo y con MAX(): los vehículos que solo tienen cargas de
    // gasolina salían vacíos, y el veh 4 mostraba un check-in de 2025 en vez de la
    // carga de 2026. Se envía como '' cuando no hay lecturas, para que el input quede
    // en blanco (captura manual) y no con un 0.
    $ultimoKM = obtenerUltimoKM($conn, $IDvehiculoAsignado);
    $row['kmMax'] = $ultimoKM > 0 ? $ultimoKM : null;

    echo json_encode([$row]);
    exit;
}

if($accion == 'CapturaCheckIn'){
    // La foto del odómetro es OBLIGATORIA en el primer registro de KM de la semana.
    // Misma regla y misma condición que 'checkInQR' en acciones_qr.php: el inicio de
    // préstamo es el otro camino que llega a actividad_vehiculo, y sin esto se
    // saltaba la regla. Se resuelve en el servidor, no con un flag del cliente.
    $stmtSem = $conn->prepare("
        SELECT 1 FROM actividad_vehiculo
        WHERE id_vehiculo = ? AND km_actual > 0
          AND YEARWEEK(fecha_actividad, 1) = YEARWEEK(CURDATE(), 1)
        LIMIT 1
    ");
    if ($stmtSem) {
        $idVehSem = intval($id_vehiculo);
        $stmtSem->bind_param("i", $idVehSem);
        $stmtSem->execute();
        $hayKMEstaSemana = (bool) $stmtSem->get_result()->fetch_assoc();
        $stmtSem->close();

        $traeFoto = false;
        if (isset($_FILES['imgCheckin']['error'])) {
            foreach ((array) $_FILES['imgCheckin']['error'] as $errFoto) {
                if ($errFoto === UPLOAD_ERR_OK) { $traeFoto = true; break; }
            }
        }

        if (!$hayKMEstaSemana && !$traeFoto) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'La foto del kilometraje es obligatoria en el primer registro de la semana.'
            ]);
            exit;
        }
    }

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

            // Calcular km recorrido de este check-in (origen GPS -> destino actividad/cliente)
            calcularKmCheckinGlobal($conn, $coordenadas, $otRelacionada, $idUltimaActividad);

        echo json_encode(['status' => 'success', 'message' => 'Check-in realizado correctamente.', 'id_actividad' => $idUltimaActividad]);
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

            // Calcular km recorrido de este check-out (origen GPS -> destino actividad/cliente)
            calcularKmCheckinGlobal($conn, $coordenadas, $otRelacionada, $idUltimaActividad);

        echo json_encode(['status' => 'success', 'message' => 'Check-out realizado correctamente.', 'id_actividad' => $idUltimaActividad]);
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

/**
 * Vista de gerentes: TODOS los check-in en crudo.
 *
 * Deliberadamente NO cruza INICIO con FINALIZACION como hace 'Actividades' más abajo.
 * Ese cruce solo devuelve viajes cerrados y los usuarios no cierran la actividad: de
 * 379 INICIO hay 312 FINALIZACION, así que un listado por pares esconde justo los
 * registros que hay que auditar. Aquí sale una fila por registro, y los INICIO sin
 * cierre posterior se marcan con abierto = 1.
 *
 * El acceso se valida en servidor con accesos_especiales (nunca con la cookie `rol`,
 * que la escribe el cliente). Ver includes/api_bootstrap.php.
 *
 * Se usa verTodosVehiculo y no un permiso propio: ver los check-in de toda la flota
 * implica ver toda la flota, así que separarlos permitiría dar uno sin el otro.
 */
if ($accion == 'consultarCheckins') {
    if (!tieneAccesoEspecial($conn, $_COOKIE['noEmpleado'] ?? 0, 'verTodosVehiculo')) {
        echo json_encode(['status' => 'error', 'message' => 'No tienes acceso a esta vista.']);
        exit;
    }

    $desde = $_POST['desde'] ?? '';
    $hasta = $_POST['hasta'] ?? '';
    $idVeh = intval($_POST['id_vehiculo'] ?? 0);

    // Por defecto los últimos 30 días: la tabla crece sin tope y traerla completa
    // castiga al navegador del que abre la vista.
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) $desde = date('Y-m-d', strtotime('-30 days'));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) $hasta = date('Y-m-d');

    $filtroVeh = $idVeh > 0 ? " AND av.id_vehiculo = ? " : "";

    // El JOIN a usuarios usa MAX(id) porque usuarios.id_usuario NO es único; un LEFT
    // JOIN directo multiplicaría las filas del listado.
    $sql = "SELECT av.id_actividad, av.id_vehiculo, av.tipo_actividad, av.fecha_actividad,
                   av.km_actual, av.gasolina_actual, av.coordenadas, av.foto_url,
                   av.notas, av.ot, av.patron,
                   inv.placa, inv.marca, inv.modelo,
                   IFNULL(NULLIF(TRIM(CONCAT(IFNULL(rrhh.nombres,''),' ',IFNULL(rrhh.apellidos,''))),''), u.nombre) AS usuario,
                   CASE WHEN av.tipo_actividad = 'INICIO' AND NOT EXISTS (
                            SELECT 1 FROM actividad_vehiculo f
                            WHERE f.id_vehiculo = av.id_vehiculo
                              AND f.id_usuario  = av.id_usuario
                              AND f.tipo_actividad = 'FINALIZACION'
                              AND f.fecha_actividad > av.fecha_actividad
                        ) THEN 1 ELSE 0 END AS abierto
            FROM actividad_vehiculo av
            LEFT JOIN inventario inv ON inv.id_vehiculo = av.id_vehiculo
            LEFT JOIN usuarios u ON u.id = (
                SELECT MAX(u2.id) FROM usuarios u2 WHERE u2.id_usuario = av.id_usuario
            )
            LEFT JOIN mess_rrhh.usuarios rrhh ON rrhh.noEmpleado = u.noEmpleado
            WHERE DATE(av.fecha_actividad) BETWEEN ? AND ?
            $filtroVeh
            ORDER BY av.fecha_actividad DESC, av.id_actividad DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Error al preparar la consulta.']);
        exit;
    }
    if ($idVeh > 0) $stmt->bind_param("ssi", $desde, $hasta, $idVeh);
    else            $stmt->bind_param("ss", $desde, $hasta);
    $stmt->execute();
    $res = $stmt->get_result();

    $checkins = [];
    while ($row = $res->fetch_assoc()) $checkins[] = $row;
    $stmt->close();

    echo json_encode(['status' => 'success', 'desde' => $desde, 'hasta' => $hasta, 'checkins' => $checkins]);
    exit;
}

if($accion == 'Actividades'){
    // Consultar las actividades del usuario actual
    /*$sql = "SELECT av.*, i.placa, i.marca, i.modelo, (select u.nombre from usuarios u where u.id_usuario = av.id_usuario) as usuario
        FROM actividad_vehiculo av
        INNER JOIN inventario i ON av.id_vehiculo = i.id_vehiculo        
        WHERE av.id_usuario = '".$_COOKIE['id_usuario']."'";
    $result = $conn->query($sql);*/
    $sql = "SELECT 
                i.id_usuario,
                i.id_vehiculo,
                i.id_actividad AS actividad_inicio,
                f.id_actividad AS actividad_fin,
                i.km_actual AS km_inicio,
                f.km_actual AS km_final,
                f.km_actual - i.km_actual AS km_recorridos,
                i.fecha_actividad AS Fecha_Inicio,
                f.fecha_actividad AS Fecha_Fin,
                inv.placa, inv.marca, inv.modelo,
                IFNULL(NULLIF(TRIM(CONCAT(IFNULL(rrhh.nombres,''),' ',IFNULL(rrhh.apellidos,''))),'' ), inv.usuario) AS usuario,
                f.notas AS notasF, 
                i.notas AS notasI,
                f.ot
            FROM actividad_vehiculo i
            JOIN actividad_vehiculo f
                ON i.id_vehiculo = f.id_vehiculo
                AND i.id_usuario = f.id_usuario 
                AND f.tipo_actividad = 'FINALIZACION'
                AND f.fecha_actividad = (
                    SELECT MIN(fecha_actividad)
                    FROM actividad_vehiculo
                    WHERE tipo_actividad = 'FINALIZACION'
                    AND id_vehiculo = i.id_vehiculo
                    AND id_usuario = i.id_usuario
                    AND fecha_actividad > i.fecha_actividad
                )
            INNER JOIN inventario inv ON i.id_vehiculo = inv.id_vehiculo
            LEFT JOIN usuarios cv_u ON cv_u.id_usuario = inv.id_usuario
            LEFT JOIN mess_rrhh.usuarios rrhh ON rrhh.noEmpleado = cv_u.noEmpleado
            WHERE i.tipo_actividad = 'INICIO'
                AND inv.id_vehiculo IN (SELECT id_vehiculo FROM inventario WHERE id_usuario = '".$_COOKIE['id_usuario']."')";
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