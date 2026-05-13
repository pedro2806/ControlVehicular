<?php
include 'conn.php';
header('Content-Type: application/json');
mysqli_set_charset($conn, "utf8mb4");
date_default_timezone_set('America/Mexico_City');

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

    $stmt = $conn->prepare(
        "INSERT INTO anomalias (id_vehiculo, id_usuario, descripcion, foto_ruta, fecha)
         VALUES (?, ?, ?, ?, NOW())"
    );
    $stmt->bind_param("iiss", $id_vehiculo, $id_usuario, $descripcion, $foto_ruta);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al guardar en la base de datos.']);
    }
    $stmt->close();
    exit;
}

echo json_encode(['success' => false, 'error' => 'Acción no reconocida.']);
