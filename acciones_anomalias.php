<?php
include 'includes/api_bootstrap.php';

$accion = $_POST['accion'] ?? '';

$id_vehiculo = intval($_POST['id_vehiculo'] ?? 0);
$id_usuario  = intval($_COOKIE['id_usuario'] ?? 0);
$descripcion = trim($_POST['descripcion'] ?? '');
$tiene_foto = isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK;

if ($accion === 'registrarAnomalia') {
    if (!$id_vehiculo || !$id_usuario || $descripcion === '' || !$tiene_foto) {
        echo json_encode(['success' => false, 'error' => 'Datos incompletos.']);
        exit;
    }

    // Obtener placa para nombre de carpeta
    $stmt = $conn->prepare("SELECT placa FROM inventario WHERE id_vehiculo = ? LIMIT 1");
    $stmt->bind_param("i", $id_vehiculo);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        echo json_encode(['success' => false, 'error' => 'Vehículo no encontrado.']);
        exit;
    }

    $placa     = $row['placa'];
    $foto_ruta = null;

    if ($tiene_foto) {
        $ext  = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $dir  = __DIR__ . "/img_control_vehicular/{$placa}/anomalias";

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $nombre    = "{$placa}_anomalia_" . date('Ymd_His') . ".{$ext}";
        $destino   = "{$dir}/{$nombre}";
        $foto_ruta = "img_control_vehicular/{$placa}/anomalias/{$nombre}";

        if (!move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
            echo json_encode(['success' => false, 'error' => 'No se pudo guardar la foto.']);
            exit;
        }
    }

    // La ubicación es opcional (columna NULL): si el GPS falla, la anomalía se registra
    // igual. Es un reporte de mantenimiento, no un siniestro, así que no vale la pena
    // bloquear al usuario por no tener señal.
    $coordenadas = trim($_POST['coordenadas'] ?? '');
    if ($coordenadas === '') $coordenadas = null;

    $stmt = $conn->prepare(
        "INSERT INTO anomalias (id_vehiculo, id_usuario, descripcion, foto_ruta, coordenadas, fecha)
         VALUES (?, ?, ?, ?, ?, NOW())"
    );
    $stmt->bind_param("iisss", $id_vehiculo, $id_usuario, $descripcion, $foto_ruta, $coordenadas);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al guardar en la base de datos.']);
    }
    $stmt->close();
    exit;
}

/**
 * Feed del historial de anomalías.
 *
 * Mismo criterio de acceso que el historial de siniestros (obtenerFeedSiniestros en
 * acciones_siniestro.php): se reutiliza la opción verHistorialSiniestro porque quien
 * puede ver los siniestros de toda la flota es la misma gente que necesita ver las
 * anomalías; separarlas obligaría a dar dos permisos que siempre van juntos.
 *
 * Sin ese acceso, cada quien ve solo las de sus vehículos (propios, asignados o en
 * préstamo autorizado). Nunca se filtra con la cookie `rol`, que la escribe el cliente.
 */
if ($accion === 'obtenerFeedAnomalias') {
    $noEmpleado = $_COOKIE['noEmpleado'] ?? 0;
    $verTodas   = tieneAccesoEspecial($conn, $noEmpleado, 'verHistorialSiniestro');

    $where = '';
    if (!$verTodas) {
        $idU = intval($id_usuario);
        $where = "WHERE a.id_vehiculo IN (
            SELECT inv.id_vehiculo FROM inventario inv WHERE inv.id_us_asignado = $idU OR inv.id_usuario = $idU
            UNION
            SELECT p.id_vehiculo FROM prestamos p WHERE p.id_usuario = $idU AND p.estatus = 'AUTORIZADO'
        )";
    }

    // usuarios.id_usuario no es único: se empareja con la fila de mayor id para que el
    // JOIN no multiplique las anomalías.
    $sql = "SELECT a.id, a.id_vehiculo, a.descripcion, a.foto_ruta, a.coordenadas,
                   a.fecha, DATE(a.fecha) AS fecha_dia,
                   inv.placa, inv.modelo, inv.marca, inv.color, inv.anio,
                   IFNULL(NULLIF(TRIM(CONCAT(IFNULL(rrhh.nombres,''),' ',IFNULL(rrhh.apellidos,''))),''), u.nombre) AS nombre_usuario
            FROM anomalias a
            INNER JOIN inventario inv ON inv.id_vehiculo = a.id_vehiculo
            LEFT JOIN usuarios u ON u.id = (
                SELECT MAX(u2.id) FROM usuarios u2 WHERE u2.id_usuario = a.id_usuario
            )
            LEFT JOIN mess_rrhh.usuarios rrhh ON rrhh.noEmpleado = u.noEmpleado
            $where
            ORDER BY a.fecha DESC, a.id DESC";

    $res = $conn->query($sql);
    $anomalias = [];
    if ($res) { while ($row = $res->fetch_assoc()) $anomalias[] = $row; }
    echo json_encode($anomalias);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Acción no reconocida.']);
