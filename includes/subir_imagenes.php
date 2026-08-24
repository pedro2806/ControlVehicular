<?php
/**
 * Guarda en disco y en `fotos` las imágenes de una actividad.
 *
 * $imagenes es una entrada de $_FILES y llega en dos formas distintas:
 *   - input name="x[]"   -> ['tmp_name' => [...], 'name' => [...], 'error' => [...]]
 *   - un archivo suelto  -> ['tmp_name' => '...', 'name' => '...', 'error' => 0]
 * La segunda es la que manda el inicio de préstamo. Con ella 'tmp_name' es un
 * string y el foreach no iteraba nunca, así que la foto se perdía en silencio
 * (101 check-ins de préstamo quedaron sin ninguna imagen). Por eso se normaliza.
 */
function subirImagenesCheckin($imagenes, $id_actividad, $id_vehiculo, $conn, $placa, $actividad) {
    if (!isset($imagenes) || !is_array($imagenes) || !isset($imagenes['tmp_name'])) {
        return;
    }

    if (!is_array($imagenes['tmp_name'])) {
        $imagenes = [
            'tmp_name' => [$imagenes['tmp_name']],
            'name'     => [$imagenes['name'] ?? ''],
            'error'    => [$imagenes['error'] ?? UPLOAD_ERR_NO_FILE],
        ];
    }

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

    $consecutivo = 0;
    foreach ($imagenes['tmp_name'] as $key => $tmp_name) {
        if (($imagenes['error'][$key] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            continue;
        }
        $consecutivo++;
        $extension = pathinfo($imagenes['name'][$key] ?? '', PATHINFO_EXTENSION);
        if ($extension === '') { $extension = 'jpg'; }

        // El nombre lleva id_actividad + sufijo único. Antes era
        // {placa}_{actividad}_{n} con n reiniciándose en cada request: cada
        // check-in nuevo sobrescribía en disco la foto del anterior del mismo
        // vehículo, y las filas viejas de `fotos` acababan mostrando la nueva
        // (6 check-ins del Jetta SJK584A apuntan hoy al mismo archivo).
        $nombreArchivo = $placa . '_' . $actividad . '_' . intval($id_actividad)
                       . '_' . $consecutivo . '_' . substr(uniqid('', true), -6) . '.' . $extension;
        $ruta_destino = $carpetaActividad . '/' . $nombreArchivo;

        if (move_uploaded_file($tmp_name, $ruta_destino)) {
            $stmt = $conn->prepare("INSERT INTO fotos (formato, id_formato, id_vehiculo, imagen) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssis", $actividad, $id_actividad, $id_vehiculo, $ruta_destino);
            $stmt->execute();
            $stmt->close();
        }
    }
}
