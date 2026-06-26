<?php
function subirImagenesCheckin($imagenes, $id_actividad, $id_vehiculo, $conn, $placa, $actividad) {
    $carpetaPlaca = "img_control_vehicular/$placa";
    if (!file_exists($carpetaPlaca)) {
        mkdir($carpetaPlaca, 0777, true);
    }

    $subcarpetas = ['checkin' => 'Actividades', 'checkout' => 'Actividades', 'siniestro' => 'Siniestro'];
    $subdir = $subcarpetas[$actividad] ?? $actividad;
    $carpetaActividad = "$carpetaPlaca/$subdir";
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
            $ruta_destino = $carpetaActividad . '/' . $nombreArchivo;

            if (move_uploaded_file($tmp_name, $ruta_destino)) {
                $stmt = $conn->prepare("INSERT INTO fotos (formato, id_formato, id_vehiculo, imagen) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssis", $actividad, $id_actividad, $id_vehiculo, $ruta_destino);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}
