<?php
include 'conn.php';

$opcion = $_POST['opcion'];

if ($opcion == "llenaTVehiculosAsignados") {
    $sql = "SELECT id, id_coche, id_usuario, usuario, area, placa, modelo, tipo, 
            color, anio, foto_general, estatus, fecha_registro, km_mantenimiento, referencia 
            FROM inventario 
            WHERE id_usuario = 58";

    $res2 = mysqli_query($conn, $sql);

    if (!$res2) {
        die(json_encode(array("error" => mysqli_error($conn))));
    }

    $registros = array();
    while ($row2 = mysqli_fetch_assoc($res2)) {
        $registros[] = array(
            'id' => $row2["id"],
            'idCoche' => $row2["id_coche"],
            'usuario' => $row2["usuario"],
            'area' => $row2["area"],
            'placa' => $row2["placa"],
            'modelo' => $row2["modelo"],
            'tipo' => $row2["tipo"],
            'color' => $row2["color"],
            'anio' => $row2["anio"],
            'fotoGeneral' => $row2["foto_general"],
            'estatus' => $row2["estatus"],
            'fechaRegistro' => $row2["fecha_registro"],
            'kmMantenimiento' => $row2["km_mantenimiento"],
            'referencia' => $row2["referencia"]
        );
    }

    if (empty($registros)) {
        echo json_encode(array("message" => "No records found."));
    } else {
        echo json_encode($registros);
    }
}

$id_checklist = $_POST['id_checklist'];
$id_coche = $_POST['id_coche'];
$fecha = $_POST['fecha'];
$id_usuario = $_POST['id_usuario'];
$id_revisor = $_POST['id_revisor'];
$motivo = $_POST['motivo'];

$si_no_asientos = $_POST['si_no_asientos'];
$observaciones_asientos = $_POST['observaciones_asientos'];
$foto_asientos = $_POST['foto_asientos'];

$id_doc = $_POST['id_doc'];
$t_documento = $_POST['t_documento'];
$entregado = $_POST['entregado'];
$vencimiento = $_POST['vencimiento'];
$no_tarjeta = $_POST['no_tarjeta'];
$observaciones_documentacion = $_POST['observaciones_documentacion'];
$foto_documentacion = $_POST['foto_documentacion'];

$si_no_espejos = $_POST['si_no_espejos'];
$observaciones_espejos = $_POST['observaciones_espejos'];
$foto_espejos = $_POST['foto_espejos'];

$cd_estereo = $_POST['cd_estereo'];
$si_no_estereos = $_POST['si_no_estereos'];
$observaciones_estereos = $_POST['observaciones_estereos'];
$foto_estereos = $_POST['foto_estereos'];

$si_no_faros = $_POST['si_no_faros'];
$observaciones_faros = $_POST['observaciones_faros'];
$foto_faros = $_POST['foto_faros'];


$si_no_golpes = $_POST['si_no_golpes'];
$observaciones_golpes = $_POST['observaciones_golpes'];
$foto_golpes = $_POST['foto_golpes'];


$si_no_limpiaParabrisas = $_POST['si_no_limpiaParabrisas'];
$observaciones_limpiaParabrisas = $_POST['observaciones_limpiaParabrisas'];
$foto_limpiaParabrisas = $_POST['foto_limpiaParabrisas'];

$si_no_limpieza = $_POST['si_no_limpieza'];
$observaciones_limpieza = $_POST['observaciones_limpieza'];
$foto_limpieza = $_POST['foto_limpieza'];

$si_no_llantas = $_POST['si_no_llantas'];
$no_rin = $_POST['no_rin'];
$medidas = $_POST['medidas'];
$observaciones_llantas = $_POST['observaciones_llantas'];
$foto_llantas = $_POST['foto_llantas'];

$si_no_placas = $_POST['si_no_placas'];
$observaciones_placas = $_POST['observaciones_placas'];
$foto_placas = $_POST['foto_placas'];

$si_no_puertas = $_POST['si_no_puertas'];
$duplicado_llaves = $_POST['duplicado_llaves'];
$observaciones_puertas = $_POST['observaciones_puertas'];
$foto_puertas = $_POST['foto_puertas'];

$id_documentacion_general = $_POST['id_documentacion_general'];
$tarjeta_circulacion = $_POST['tarjeta_circulacion'];
$refrendo_actual = $_POST['refrendo_actual'];
$seguro_auto = $_POST['seguro_auto'];
$verificacion_vigente = $_POST['verificacion_vigente'];


if ($tabla == 'checklist') {
    $sql = "INSERT INTO checklist (id_checklist, id_coche, fecha, id_usuario, id_revisor, motivo) 
        VALUES ('$id_checklist', '$id_coche', '$fecha', '$id_usuario', '$id_revisor', '$motivo')";
    $resultadoChecklist = mysqli_query($conn, $sql);
}

if ($tabla == 'checklist_asientos') {
    $sql = "INSERT INTO checklist_asientos (id, id_checklist, si_no, observaciones, foto) 
        VALUES ('$id_asientos', '$id_checklist', '$si_no_asientos', '$observaciones_asientos', '$foto_asientos')";
    $resultadoAsientos = mysqli_query($conn, $sql);
}

if ($tabla == 'checklist_documentacion') {
    $sql = "INSERT INTO checklist_documentacion (id, id_checklist, id_doc, t_documento, entregado, vencimiento, no_tarjeta, observaciones, foto) 
        VALUES ('$id_documentacion', '$id_checklist', '$id_doc', '$t_documento', '$entregado', '$vencimiento', '$no_tarjeta', '$observaciones_documentacion', '$foto_documentacion')";
    $resultadoDocumentacion = mysqli_query($conn, $sql);
}

if ($tabla == 'checklist_espejos_ventanas') {
    $sql = "INSERT INTO checklist_espejos_ventanas (id, id_checklist, si_no, observaciones, foto) 
        VALUES ('$id_espejos', '$id_checklist', '$si_no_espejos', '$observaciones_espejos', '$foto_espejos')";
    $resultadoEspejos = mysqli_query($conn, $sql);
}

if ($tabla == 'checklist_estereos_aire') {
    $sql = "INSERT INTO checklist_estereos_aire (id, id_checklist, cd_estereo, si_no, observaciones, foto) 
        VALUES ('$id_estereos', '$id_checklist', '$cd_estereo', '$si_no_estereos', '$observaciones_estereos', '$foto_estereos')";
    $resultadoEstereos = mysqli_query($conn, $sql);
}

if ($tabla == 'checklist_faros') {
    $sql = "INSERT INTO checklist_faros (id, id_checklist, si_no, observaciones, foto) 
        VALUES ('$id_faros', '$id_checklist', '$si_no_faros', '$observaciones_faros', '$foto_faros')";
    $resultadoFaros = mysqli_query($conn, $sql);
}

if ($tabla == 'checklist_golpes_exterior') {
    $sql = "INSERT INTO checklist_golpes_exterior (id, id_checklist, si_no, observaciones, foto) 
        VALUES ('$id_golpes', '$id_checklist', '$si_no_golpes', '$observaciones_golpes', '$foto_golpes')";
    $resultadoGolpes = mysqli_query($conn, $sql);
}

if ($tabla == 'checklist_limpiaParabrisas') {
    $sql = "INSERT INTO checklist_limpiaParabrisas (id, id_checklist, si_no, observaciones, foto) 
        VALUES ('$id_limpiaParabrisas', '$id_checklist', '$si_no_limpiaParabrisas', '$observaciones_limpiaParabrisas', '$foto_limpiaParabrisas')";
    $resultadoLimpiaParabrisas = mysqli_query($conn, $sql);
}

if ($tabla == 'checklist_limpieza') {
    $sql = "INSERT INTO checklist_limpieza (id, id_checklist, si_no, observaciones, foto) 
        VALUES ('$id_limpieza', '$id_checklist', '$si_no_limpieza', '$observaciones_limpieza', '$foto_limpieza')";
    $resultadoLimpieza = mysqli_query($conn, $sql);
}

if ($tabla == 'checklist_llantas') {
    $sql = "INSERT INTO checklist_llantas (id, id_checklist, si_no, no_rin, medidas, observaciones, foto) 
        VALUES ('$id_llantas', '$id_checklist', '$si_no_llantas', '$no_rin', '$medidas', '$observaciones_llantas', '$foto_llantas')";
    $resultadoLlantas = mysqli_query($conn, $sql);
}

if ($tabla == 'checklist_placas') {
    $sql = "INSERT INTO checklist_placas (id, id_checklist, si_no, observaciones, foto) 
        VALUES ('$id_placas', '$id_checklist', '$si_no_placas', '$observaciones_placas', '$foto_placas')";
    $resultadoPlacas = mysqli_query($conn, $sql);
}

if ($tabla == 'checklist_puertas_llave') {
    $sql = "INSERT INTO checklist_puertas_llave (id, id_checklist, si_no, duplicado_llaves, observaciones, foto) 
        VALUES ('$id_puertas', '$id_checklist', '$si_no_puertas', '$duplicado_llaves', '$observaciones_puertas', '$foto_puertas')";
    $resultadoPuertas = mysqli_query($conn, $sql);
}

if ($tabla == 'documentacion') {
    $sql = "INSERT INTO documentacion (id, id_coche, tarjeta_circulacion, refrendo_actual, seguro_auto, verificacion_vigente) 
        VALUES ('$id_documentacion_general', '$id_coche', '$tarjeta_circulacion', '$refrendo_actual', '$seguro_auto', '$verificacion_vigente')";
    $resultadoDocumentacionGeneral = mysqli_query($conn, $sql);
}
