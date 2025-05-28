<?php
include 'conn.php';

$opcion = $_POST['opcion'];
$id_usuario = $_COOKIE['id_usuario'];
if ($opcion == "llenaTVehiculosAsignados") {
    $sql = "SELECT i.id_vehiculo, i.id_usuario, i.usuario, i.area, i.placa, i.modelo, i.color, i.anio, i.foto_general, i.estatus, i.fecha_registro, i.km_mantenimiento, i.marca, u.nombre as asignado
            FROM inventario i
            INNER JOIN usuarios u ON i.id_usuario = u.id_usuario             
            WHERE i.id_usuario = $id_usuario";

    $res2 = mysqli_query($conn, $sql);

    if (!$res2) {
        die(json_encode(array("error" => mysqli_error($conn))));
    }

    $registros = array();
    while ($row2 = mysqli_fetch_assoc($res2)) {
        $registros[] = array(
            'id' => $row2["id"],
            'idCoche' => $row2["id_vehiculo"],
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
            'referencia' => $row2["referencia"],
            'marca' => $row2["marca"],
            'asignado' => $row2["asignado"]
        );
    }

    if (empty($registros)) {
        echo json_encode(array("message" => "No records found."));
    } else {
        echo json_encode($registros);
    }
}

if ($opcion == 'verChecks') {
    $id_coche = $_POST['idCoche'] ?? null;
    $sql = "SELECT * FROM checklist WHERE id_vehiculo = '$id_coche' ORDER BY fecha DESC";
    $res2 = mysqli_query($conn, $sql);

    if (!$res2) {
        die(json_encode(array("error" => mysqli_error($conn))));
    }

    $registros = array();
    while ($row2 = mysqli_fetch_assoc($res2)) {
        $registros[] = array(
            'id' => $row2["id_checklist"],
            'fecha' => $row2["fecha"],
            'id_usuario' => $row2["id_usuario"],
            'id_revisor' => $row2["id_revisor"],
            'motivo' => $row2["motivo"]
        );
    }

    if (empty($registros)) {
        echo json_encode(array("message" => "Sin registros."));
    } else {
        echo json_encode($registros);
    }
}

$id_checklist = $_POST['idCheck'] ?? null;

if ($opcion == 'checklist_asientos') {
    
    $sql = "SELECT * FROM checklist_asientos WHERE id_checklist = '$id_checklist'";
    $res = mysqli_query($conn, $sql);

    if (!$res) {
        die(json_encode(array("error" => mysqli_error($conn))));
    }

    $registros = array();
    while ($row = mysqli_fetch_assoc($res)) {
        $registros[] = array(
            'nombre_seccion' => 'Asientos',            
            'Si_No' => $row["si_no"],                        
            'Observaciones' => $row["obervaciones"],
            'Buen_estado' => $row["buen_estado"],
            'imagen' => $row["foto"]
        );
    }

    echo json_encode($registros);
}

if ($opcion == 'checklist_espejos_ventanas') {
    
    $sql = "SELECT * FROM checklist_espejos_ventanas WHERE id_checklist = '$id_checklist'";
    $res = mysqli_query($conn, $sql);

    if (!$res) {
        die(json_encode(array("error" => mysqli_error($conn))));
    }

    $registros = array();
    while ($row = mysqli_fetch_assoc($res)) {
        $registros[] = array(
            'nombre_seccion' => 'Espejos y Ventanas',            
            'Si_No' => $row["si_no"],                        
            'Observaciones' => $row["obervaciones"],
            'Buen_estado' => $row["buen_estado"],
            'imagen' => $row["foto"]
        );
    }

    echo json_encode($registros);
}

if ($opcion == 'checklist_estereos_aire') {
    
    $sql = "SELECT * FROM checklist_estereos_aire WHERE id_checklist = '$id_checklist'";
    $res = mysqli_query($conn, $sql);

    if (!$res) {
        die(json_encode(array("error" => mysqli_error($conn))));
    }

    $registros = array();
    while ($row = mysqli_fetch_assoc($res)) {
        $registros[] = array(
            'nombre_seccion' => 'Estereo y Aire Acondicionado',            
            'Si_No' => $row["si_no"],                        
            'Observaciones' => $row["obervaciones"],
            'Buen_estado' => $row["buen_estado"],
            'imagen' => $row["foto"]
        );
    }

    echo json_encode($registros);
}

if ($opcion == 'checklist_faros') {
    
    $sql = "SELECT * FROM checklist_faros WHERE id_checklist = '$id_checklist'";
    $res = mysqli_query($conn, $sql);

    if (!$res) {
        die(json_encode(array("error" => mysqli_error($conn))));
    }

    $registros = array();
    while ($row = mysqli_fetch_assoc($res)) {
        $registros[] = array(
            'nombre_seccion' => 'Faros',            
            'Si_No' => $row["si_no"],                        
            'Observaciones' => $row["obervaciones"],
            'Buen_estado' => $row["buen_estado"],
            'imagen' => $row["foto"]
        );
    }

    echo json_encode($registros);
}

if ($opcion == 'checklist_golpes_exterior') {
    
    $sql = "SELECT * FROM checklist_golpes_exterior WHERE id_checklist = '$id_checklist'";
    $res = mysqli_query($conn, $sql);

    if (!$res) {
        die(json_encode(array("error" => mysqli_error($conn))));
    }

    $registros = array();
    while ($row = mysqli_fetch_assoc($res)) {
        $registros[] = array(
            'nombre_seccion' => 'Golpes Exterior',            
            'Si_No' => $row["si_no"],                        
            'Observaciones' => $row["obervaciones"],
            'Buen_estado' => $row["buen_estado"],
            'imagen' => $row["foto"]
        );
    }

    echo json_encode($registros);
}

if ($opcion == 'checklist_graficas') {
    
    $sql = "SELECT * FROM checklist_graficas WHERE id_checklist = '$id_checklist'";
    $res = mysqli_query($conn, $sql);

    if (!$res) {
        die(json_encode(array("error" => mysqli_error($conn))));
    }

    $registros = array();
    while ($row = mysqli_fetch_assoc($res)) {
        $registros[] = array(
            'nombre_seccion' => 'Graficas',
            'Si_No' => $row["si_no"],                        
            'Observaciones' => $row["obervaciones"],
            'Buen_estado' => $row["buen_estado"],
            'imagen' => $row["foto"]
        );
    }

    echo json_encode($registros);
}

if ($opcion == 'checklist_limpiaparabrisas') {
    
    $sql = "SELECT * FROM checklist_limpiaparabrisas WHERE id_checklist = '$id_checklist'";
    $res = mysqli_query($conn, $sql);

    if (!$res) {
        die(json_encode(array("error" => mysqli_error($conn))));
    }

    $registros = array();
    while ($row = mysqli_fetch_assoc($res)) {
        $registros[] = array(
            'nombre_seccion' => 'Limpiaparabrisas',
            'Si_No' => $row["si_no"],                        
            'Observaciones' => $row["obervaciones"],
            'Buen_estado' => $row["buen_estado"],
            'imagen' => $row["foto"]
        );
    }

    echo json_encode($registros);
}

if ($opcion == 'checklist_limpieza') {
    
    $sql = "SELECT * FROM checklist_limpieza WHERE id_checklist = '$id_checklist'";
    $res = mysqli_query($conn, $sql);

    if (!$res) {
        die(json_encode(array("error" => mysqli_error($conn))));
    }

    $registros = array();
    while ($row = mysqli_fetch_assoc($res)) {
        $registros[] = array(
            'nombre_seccion' => 'Limpieza',
            'Si_No' => $row["si_no"],                        
            'Observaciones' => $row["obervaciones"],
            'Buen_estado' => $row["buen_estado"],
            'imagen' => $row["foto"]
        );
    }

    echo json_encode($registros);
}

if ($opcion == 'checklist_llantas') {
    
    $sql = "SELECT * FROM checklist_llantas WHERE id_checklist = '$id_checklist'";
    $res = mysqli_query($conn, $sql);

    if (!$res) {
        die(json_encode(array("error" => mysqli_error($conn))));
    }

    $registros = array();
    while ($row = mysqli_fetch_assoc($res)) {
        $registros[] = array(
            'nombre_seccion' => 'Llantas',            
            'Medidas' => $row["medidas"],
            'No_Rin' => $row["no_rin"],
            'Observaciones' => $row["obervaciones"],
            'Buen_estado' => $row["buen_estado"],
            'imagen' => $row["foto"]
        );
    }

    echo json_encode($registros);
}

if ($opcion == 'checklist_placas') {
    
    $sql = "SELECT * FROM checklist_placas WHERE id_checklist = '$id_checklist'";
    $res = mysqli_query($conn, $sql);

    if (!$res) {
        die(json_encode(array("error" => mysqli_error($conn))));
    }

    $registros = array();
    while ($row = mysqli_fetch_assoc($res)) {
        $registros[] = array(
            'nombre_seccion' => 'Placas',            
            'Si_No' => $row["si_no"],                        
            'Observaciones' => $row["obervaciones"],
            'Buen_estado' => $row["buen_estado"],
            'imagen' => $row["foto"]
        );
    }

    echo json_encode($registros);
}

if ($opcion == 'checklist_puertas_llaves') {
    
    $sql = "SELECT * FROM checklist_puertas_llave WHERE id_checklist = '$id_checklist'";
    $res = mysqli_query($conn, $sql);

    if (!$res) {
        die(json_encode(array("error" => mysqli_error($conn))));
    }

    $registros = array();
    while ($row = mysqli_fetch_assoc($res)) {
        $registros[] = array(
            'nombre_seccion' => 'Puertas y Llaves',            
            'Si_No' => $row["si_no"],                        
            'Observaciones' => $row["obervaciones"],
            'Buen_estado' => $row["buen_estado"],
            'imagen' => $row["foto"]
        );
    }

    echo json_encode($registros);
}

if ($opcion == 'checklist_documentacion') {
    
    $sql = "SELECT * FROM `checklist_documentacion` WHERE id_checklist = '$id_checklist'";
    $res = mysqli_query($conn, $sql);

    if (!$res) {
        die(json_encode(array("error" => mysqli_error($conn))));
    }

    $registros = array();
    while ($row = mysqli_fetch_assoc($res)) {
        $registros[] = array(
            'nombre_seccion' => $row["t_documento"],            
            'Si_No' => $row["si_no"],            
            'Observaciones' => $row["obervaciones"],
            'No_tarjeta' => $row["no_tarjeta"],
            'imagen' => $row["foto"]
        );
    }

    echo json_encode($registros);
}

//////// FUNCIONES Y  VARIABLES PARA GUARDAR CHECKLIST ////////////

    $placa = $_POST['placa'] ?? null;
    $id_coche = $_POST['id_coche'] ?? null; 
    $motivo = $_POST['motivo'] ?? null;

    $si_no_asientos = $_POST['si_no_Asientos'] ?? null;
    $buenEstado_Asientos = $_POST['buenEstado_Asientos'] ?? null;
    $observaciones_Asientos = $_POST['observaciones_Asientos'] ?? null;

    $si_no_Limpieza = $_POST['si_no_Limpieza'] ?? null;
    $buenEstado_Limpieza = $_POST['buenEstado_Limpieza'] ?? null;
    $observaciones_Limpieza = $_POST['observaciones_Limpieza'] ?? null;

    $si_no_Exterior = $_POST['si_no_Exterior'] ?? null;
    $buenEstado_Exterior = $_POST['buenEstado_Exterior'] ?? null;
    $observaciones_Exterior = $_POST['observaciones_Exterior'] ?? null;

    $si_no_Graficas = $_POST['si_no_Graficas'] ?? null;
    $buenEstado_Graficas = $_POST['buenEstado_Graficas'] ?? null;
    $observaciones_Graficas = $_POST['observaciones_Graficas'] ?? null;

    $si_no_Faros = $_POST['si_no_Faros'] ?? null;
    $buenEstado_Faros = $_POST['buenEstado_Faros'] ?? null;
    $observaciones_Faros = $_POST['observaciones_Faros'] ?? null;

    $si_no_Placas = $_POST['si_no_Placas'] ?? null;
    $buenEstado_Placas = $_POST['buenEstado_Placas'] ?? null;
    $observaciones_Placas = $_POST['observaciones_Placas'] ?? null;

    $si_no_Limpiaparabrisas = $_POST['si_no_Limpiaparabrisas'] ?? null;
    $buenEstado_Limpiaparabrisas = $_POST['buenEstado_Limpiaparabrisas'] ?? null;
    $observaciones_Limpiaparabrisas = $_POST['observaciones_Limpiaparabrisas'] ?? null;

    $si_no_espejos = $_POST['si_no_Espejos'] ?? null;
    $buenEstado_Espejos = $_POST['buenEstado_Espejos'] ?? null;
    $observaciones_Espejos = $_POST['observaciones_Espejos'] ?? null;

    $si_no_AireAcondicionado = $_POST['si_no_AireAcondicionado'] ?? null;
    $buenEstado_AireAcondicionado = $_POST['buenEstado_AireAcondicionado'] ?? null;
    $observaciones_AireAcondicionado = $_POST['observaciones_AireAcondicionado'] ?? null;
    $CEAireAcondicionado = $_POST['CEAireAcondicionado'] ?? null;

    $buenEstado_Llantas = $_POST['buenEstado_Llantas'] ?? null;
    $observaciones_Llantas = $_POST['observaciones_Llantas'] ?? null;
    $no_rin = $_POST['CE_Llantas'] ?? null;
    $medidas = $_POST['medidas_Llantas'] ?? null;

    $buenEstado_PuertasLlave = $_POST['buenEstado_PuertasLlave'] ?? null;
    $duplicado_PuertasLlave = $_POST['duplicado_PuertasLlave'] ?? null;
    $observaciones_PuertasLlave = $_POST['observaciones_PuertasLlave'] ?? null;

    $si_no_tarjetaC = $_POST['si_no_tarjetaC'] ?? null;
    $observaciones_tarjetaC = $_POST['observaciones_tarjetaC'] ?? null;

    $si_no_Refrendo = $_POST['si_no_Refrendo'] ?? null;
    $observaciones_Refrendo = $_POST['observaciones_Refrendo'] ?? null;

    $si_no_Seguro = $_POST['si_no_Seguro'] ?? null;
    $vencimiento_Seguro = $_POST['vencimiento_Seguro'] ?? null;
    $no_tarjeta_Seguro = $_POST['no_tarjeta_Seguro'] ?? null;
    $observaciones_Seguro = $_POST['observaciones_Seguro'] ?? null;

    $si_no_Verificacion = $_POST['si_no_Verificacion'] ?? null;
    $vencimiento_Verificacion = $_POST['vencimiento_Verificacion'] ?? null;
    $no_tarjeta_Verificacion = $_POST['no_tarjeta_Verificacion'] ?? null;
    $observaciones_Verificacion = $_POST['observaciones_Verificacion'] ?? null;

    $si_no_Licencia = $_POST['si_no_Licencia'] ?? null;
    $vencimiento_Licencia = $_POST['vencimiento_Licencia'] ?? null;
    $no_tarjeta_Licencia = $_POST['no_tarjeta_Licencia'] ?? null;
    $observaciones_Licencia = $_POST['observaciones_Licencia'] ?? null;

    $si_no_TarjetaEfe = $_POST['si_no_TarjetaEfe'] ?? null;
    $vencimiento_TarjetaEfe = $_POST['vencimiento_TarjetaEfe'] ?? null;
    $no_tarjeta_TarjetaEfe = $_POST['no_tarjeta_TarjetaEfe'] ?? null;
    $observaciones_TarjetaEfe = $_POST['observaciones_TarjetaEfe'] ?? null;

    $si_no_TarjetaIAVE = $_POST['si_no_TarjetaIAVE'] ?? null;
    $vencimiento_TarjetaIAVE = $_POST['vencimiento_TarjetaIAVE'] ?? null;
    $no_tarjeta_TarjetaIAVE = $_POST['no_tarjeta_TarjetaIAVE'] ?? null;
    $observaciones_TarjetaIAVE = $_POST['observaciones_TarjetaIAVE'] ?? null;
    $id_revisor = '0'; // Default value, can be updated later
    $opcion = $_POST['opcion'] ?? null;
    // Handle uploaded images
    $foto_Limpieza = isset($_FILES['foto_Limpieza']) ? file_get_contents($_FILES['foto_Limpieza']['tmp_name']) : null;
    $foto_Exterior = isset($_FILES['foto_Exterior']) ? file_get_contents($_FILES['foto_Exterior']['tmp_name']) : null;
    $foto_Graficas = isset($_FILES['foto_Graficas']) ? file_get_contents($_FILES['foto_Graficas']['tmp_name']) : null;
    $foto_Faros = isset($_FILES['foto_Faros']) ? file_get_contents($_FILES['foto_Faros']['tmp_name']) : null;
    $foto_Placas = isset($_FILES['foto_Placas']) ? file_get_contents($_FILES['foto_Placas']['tmp_name']) : null;
    $foto_Limpiaparabrisas = isset($_FILES['foto_Limpiaparabrisas']) ? file_get_contents($_FILES['foto_Limpiaparabrisas']['tmp_name']) : null;
    $foto_Espejos = isset($_FILES['foto_Espejos']) ? file_get_contents($_FILES['foto_Espejos']['tmp_name']) : null;
    $foto_AireAcondicionado = isset($_FILES['foto_AireAcondicionado']) ? file_get_contents($_FILES['foto_AireAcondicionado']['tmp_name']) : null;
    $foto_Llantas = isset($_FILES['foto_Llantas']) ? file_get_contents($_FILES['foto_Llantas']['tmp_name']) : null;
    $foto_PuertasLlave = isset($_FILES['foto_PuertasLlave']) ? file_get_contents($_FILES['foto_PuertasLlave']['tmp_name']) : null;
    $foto_tarjetaC = isset($_FILES['foto_tarjetaC']) ? file_get_contents($_FILES['foto_tarjetaC']['tmp_name']) : null;
    $foto_Refrendo = isset($_FILES['foto_Refrendo']) ? file_get_contents($_FILES['foto_Refrendo']['tmp_name']) : null;
    $foto_Seguro = isset($_FILES['foto_Seguro']) ? file_get_contents($_FILES['foto_Seguro']['tmp_name']) : null;
    $foto_Verificacion = isset($_FILES['foto_Verificacion']) ? file_get_contents($_FILES['foto_Verificacion']['tmp_name']) : null;
    $foto_Licencia = isset($_FILES['foto_Licencia']) ? file_get_contents($_FILES['foto_Licencia']['tmp_name']) : null;
    $foto_TarjetaEfe = isset($_FILES['foto_TarjetaEfe']) ? file_get_contents($_FILES['foto_TarjetaEfe']['tmp_name']) : null;
    $foto_TarjetaIAVE = isset($_FILES['foto_TarjetaIAVE']) ? file_get_contents($_FILES['foto_TarjetaIAVE']['tmp_name']) : null;

if ($opcion == 'guardarCheckIn') {
    // Insert into checklist table
    $sql = "INSERT INTO checklist (id_vehiculo, fecha, id_usuario, id_revisor, motivo) 
        VALUES ('$id_coche', NOW(), '$id_usuario', '$id_revisor', '$motivo')";        
    $resultadoChecklist = mysqli_query($conn, $sql);
    
    if (!$resultadoChecklist) {
        die(json_encode(array("error" => "Failed to insert checklist: " . mysqli_error($conn))));
    }

    // Use the inserted id_checklist for subsequent inserts
    $id_checklist = mysqli_insert_id($conn);

    // Insert into checklist_asientos
    $resultadoAsientos = insertChecklistAsientos($conn, $id_checklist, $si_no_asientos, $observaciones_Asientos, $buenEstado_Asientos, $placa);
    if (!$resultadoAsientos) {
        die(json_encode(array("error" => "Failed to insert checklist asientos: " . mysqli_error($conn))));
    }

    // Insert into checklist_espejos_ventanas
    $resultadoEspejos = insertChecklistEspejosVentanas($conn, $id_checklist, $si_no_espejos, $observaciones_Espejos, $placa, $buenEstado_Espejos);
    if (!$resultadoEspejos) {
        die(json_encode(array("error" => "Failed to insert checklist espejos: " . mysqli_error($conn))));
    }

    // Insert into checklist_estereos_aire
    $resultadoEstereos = insertChecklistEstereosAire($conn, $id_checklist, $CEAireAcondicionado, $si_no_AireAcondicionado, $observaciones_AireAcondicionado, $foto_AireAcondicionado, $placa, $buenEstado_AireAcondicionado);
    if (!$resultadoEstereos) {
        die(json_encode(array("error" => "Failed to insert checklist estereos: " . mysqli_error($conn))));
    }

    // Insert into checklist_faros
    $resultadoFaros = insertChecklistFaros($conn, $id_checklist, $si_no_Faros, $observaciones_Faros, $foto_Faros, $placa, $buenEstado_Faros);
    if (!$resultadoFaros) {
        die(json_encode(array("error" => "Failed to insert checklist faros: " . mysqli_error($conn))));
    }

    // Insert into checklist_golpes_exterior
    $resultadoGolpes = insertChecklistGolpesExterior($conn, $id_checklist, $si_no_Exterior, $observaciones_Exterior, $foto_Exterior, $placa, $buenEstado_Exterior);
    if (!$resultadoGolpes) {
        die(json_encode(array("error" => "Failed to insert checklist golpes: " . mysqli_error($conn))));
    }

    // Insert into checklist_limpiaParabrisas
    $resultadoLimpiaParabrisas = insertChecklistLimpiaParabrisas($conn, $id_checklist, $si_no_LimpiaParabrisas, $observaciones_LimpiaParabrisas, $foto_limpiaParabrisas, $placa, $buenEstado_Limpiaparabrisas);
    if (!$resultadoLimpiaParabrisas) {
        die(json_encode(array("error" => "Failed to insert checklist limpiaParabrisas: " . mysqli_error($conn))));
    }

    // Insert into checklist_limpieza
    $resultadoLimpieza = insertChecklistLimpieza($conn, $id_checklist, $si_no_Limpieza, $observaciones_Limpieza, $foto_Limpieza, $placa, $buenEstado_Limpieza);
    if (!$resultadoLimpieza) {
        die(json_encode(array("error" => "Failed to insert checklist limpieza: " . mysqli_error($conn))));
    }

    // Insert into checklist_llantas
    $resultadoLlantas = insertChecklistLlantas($conn, $id_checklist, $no_rin, $medidas, $observaciones_Llantas, $foto_llantas, $placa, $buenEstado_Llantas);
    if (!$resultadoLlantas) {
        die(json_encode(array("error" => "Failed to insert checklist llantas: " . mysqli_error($conn))));
    }

    // Insert into checklist_placas
    $resultadoPlacas = insertChecklistPlacas($conn, $id_checklist, $si_no_Placas, $observaciones_Placas, $foto_placas, $buenEstado_Placas, $placa);
    if (!$resultadoPlacas) {
        die(json_encode(array("error" => "Failed to insert checklist placas: " . mysqli_error($conn))));
    }

    // Insert into checklist_puertas_llave
    $resultadoPuertas = insertChecklistPuertasLlave($conn, $id_checklist, $buenEstado_PuertasLlave, $duplicado_PuertasLlave, $observaciones_PuertasLlave, $foto_PuertasLlave, $placa);
    if (!$resultadoPuertas) {
        die(json_encode(array("error" => "Failed to insert checklist puertas: " . mysqli_error($conn))));
    }

    $resultadoGraficas = insertChecklistGraficas($conn, $id_checklist, $si_no_Graficas, $observaciones_Graficas, $foto_Graficas, $placa, $buenEstado_Graficas);
    if (!$resultadoGraficas) {
        die(json_encode(array("error" => "Failed to insert checklist graficas: " . mysqli_error($conn))));
    }


//  INSERTS PARA EL APARTADO DE DOCUMENTACION
    // Insert into checklist_documentacion for Tarjeta de Circulacion
    $resultadoTarjetaC = insertChecklistDocumentaciontarjetaC($conn, $id_checklist, $si_no_tarjetaC, $observaciones_tarjetaC, $foto_tarjetaC, $placa);
    if (!$resultadoTarjetaC) {
        die(json_encode(array("error" => "Failed to insert checklist documentacion Tarjeta de Circulacion: " . mysqli_error($conn))));
    }

    // Insert into checklist_documentacion for Refrendo
    $resultadoRefrendo = insertChecklistDocumentacionRefrendo($conn, $id_checklist, $si_no_Refrendo, $observaciones_Refrendo, $foto_Refrendo, $placa);
    if (!$resultadoRefrendo) {
        die(json_encode(array("error" => "Failed to insert checklist documentacion Refrendo: " . mysqli_error($conn))));
    }

    // Insert into checklist_documentacion for Seguro de Auto
    $resultadoSeguro = insertChecklistDocumentacionSeguro($conn, $id_checklist, $si_no_Seguro, $vencimiento_Seguro, $no_tarjeta_Seguro, $observaciones_Seguro, $foto_Seguro, $placa);
    if (!$resultadoSeguro) {
        die(json_encode(array("error" => "Failed to insert checklist documentacion Seguro de Auto: " . mysqli_error($conn))));
    }

    // Insert into checklist_documentacion for Verificacion
    $resultadoVerificacion = insertChecklistDocumentacionVerificacion($conn, $id_checklist, $si_no_Verificacion, $vencimiento_Verificacion, $observaciones_Verificacion, $foto_Verificacion, $placa);
    if (!$resultadoVerificacion) {
        die(json_encode(array("error" => "Failed to insert checklist documentacion Verificacion: " . mysqli_error($conn))));
    }

    // Insert into checklist_documentacion for Licencia de Manejo
    $resultadoLicencia = insertChecklistDocumentacionLicencia($conn, $id_checklist, $si_no_Licencia, $vencimiento_Licencia, $observaciones_Licencia, $foto_Licencia, $placa);
    if (!$resultadoLicencia) {
        die(json_encode(array("error" => "Failed to insert checklist documentacion Licencia de Manejo: " . mysqli_error($conn))));
    }

    // Insert into checklist_documentacion for Tarjeta Efecticard
    $resultadoTarjetaEfe = insertChecklistDocumentacionTarjetaEfe($conn, $id_checklist, $si_no_TarjetaEfe, $vencimiento_TarjetaEfe, $no_tarjeta_TarjetaEfe, $observaciones_TarjetaEfe, $foto_TarjetaEfe, $placa);
    if (!$resultadoTarjetaEfe) {
        die(json_encode(array("error" => "Failed to insert checklist documentacion Tarjeta Efecticard: " . mysqli_error($conn))));
    }

    // Insert into checklist_documentacion for Tarjeta IAVE
    $resultadoTarjetaIAVE = insertChecklistDocumentacionTarjetaIAVE($conn, $id_checklist, $si_no_TarjetaIAVE, $vencimiento_TarjetaIAVE, $no_tarjeta_TarjetaIAVE, $observaciones_TarjetaIAVE, $foto_TarjetaIAVE, $placa);
    if (!$resultadoTarjetaIAVE) {
        die(json_encode(array("error" => "Failed to insert checklist documentacion Tarjeta IAVE: " . mysqli_error($conn))));
    }
    
    echo json_encode(array("success" => "Checklist and related data inserted successfully."));
}



function obtenerRutaImagen($placa, $tipo, $archivo) {
    if ($archivo && $archivo['error'] == UPLOAD_ERR_OK) {
        return $placa . "_" . $tipo . "_" . date("Ymd_his") . "." . pathinfo($archivo['name'], PATHINFO_EXTENSION);
    }
    return "S/R";
}

function subirImagenAsientos($rutaChecklist, $rutaImagen, $tempFilePath) {
    if (!is_dir($rutaChecklist)) {
        mkdir($rutaChecklist, 0775, true);
    }
    move_uploaded_file($tempFilePath, $rutaChecklist . "/" . basename($rutaImagen));
}


//FUNCIONES PARA INSERT DE LOS APARTADOS DEL CHECKLIST
function insertChecklistGraficas($conn, $id_checklist, $si_no_Graficas, $observaciones_Graficas, $foto_graficas, $placa, $buenEstado_Graficas) {
    $rutaImagen = obtenerRutaImagen($placa, "checklist_Graficas", $_FILES['foto_Graficas'] ?? null);
    $rutaChecklist = "img_control_vehicular/$placa/checklist/graficas/" . $rutaImagen;

    $sql = "INSERT INTO checklist_graficas (id_checklist, si_no, observaciones, foto, buen_estado) 
        VALUES ('$id_checklist', '$si_no_Graficas', '$observaciones_Graficas', '$rutaChecklist', '$buenEstado_Graficas')";
    
    if (mysqli_query($conn, $sql)) {
        if ($rutaImagen !== "S/R" && isset($_FILES['foto_Graficas'])) {
            $rutaChecklist = "img_control_vehicular/$placa/checklist/graficas";
            subirImagenAsientos($rutaChecklist, $rutaImagen, $_FILES['foto_Graficas']['tmp_name']);
        }
        return true;
    } else {
        return false;
    }
}

function insertChecklistAsientos($conn, $id_checklist, $si_no_asientos, $observaciones_Asientos, $buenEstado_Asientos, $placa) {
    $rutaImagen = obtenerRutaImagen($placa, "checklist_Asientos", $_FILES['foto_Asientos'] ?? null);
    $rutaChecklist = "img_control_vehicular/$placa/checklist/asientos/" . $rutaImagen;
    
    $sql = "INSERT INTO checklist_asientos (id_checklist, si_no, observaciones, foto, buen_estado) 
        VALUES ('$id_checklist', '$si_no_asientos', '$observaciones_Asientos', '$rutaChecklist', '$buenEstado_Asientos')";
    if (mysqli_query($conn, $sql)) {
        if ($rutaImagen !== "S/R" && isset($_FILES['foto_Asientos'])) {
            $rutaChecklist = "img_control_vehicular/$placa/checklist/asientos";
            subirImagenAsientos($rutaChecklist, $rutaImagen, $_FILES['foto_Asientos']['tmp_name']);
        }
        return true;
    } else {
        return false;
    }
}

function insertChecklistEspejosVentanas($conn, $id_checklist, $si_no_espejos, $observaciones_Espejos, $placa, $buenEstado_Espejos) {
    $rutaImagen = obtenerRutaImagen($placa, "checklist_Espejos", $_FILES['foto_Espejos'] ?? null);
    $rutaChecklist = "img_control_vehicular/$placa/checklist/espejos/" . $rutaImagen;

    $sql = "INSERT INTO checklist_espejos_ventanas (id_checklist, si_no, observaciones, foto, buen_estado) 
        VALUES ('$id_checklist', '$si_no_espejos', '$observaciones_Espejos', '$rutaChecklist', '$buenEstado_Espejos')";
    
    if (mysqli_query($conn, $sql)) {
        if ($rutaImagen !== "S/R" && isset($_FILES['foto_Espejos'])) {
            $rutaChecklist = "img_control_vehicular/$placa/checklist/espejos";
            subirImagenAsientos($rutaChecklist, $rutaImagen, $_FILES['foto_Espejos']['tmp_name']);
        }
        return true;
    } else {
        return false;
    }
}

function insertChecklistEstereosAire($conn, $id_checklist, $CEAireAcondicionado, $si_no_AireAcondicionado, $observaciones_AireAcondicionado, $foto_estereos, $placa, $buenEstado_AireAcondicionado) {
    $rutaImagen = obtenerRutaImagen($placa, "checklist_Estereos", $_FILES['foto_AireAcondicionado'] ?? null);
    $rutaChecklist = "img_control_vehicular/$placa/checklist/estereos/" . $rutaImagen;

    $sql = "INSERT INTO checklist_estereos_aire (id_checklist, cd_estereo, si_no, observaciones, foto, buen_estado) 
        VALUES ('$id_checklist', '$CEAireAcondicionado', '$si_no_AireAcondicionado', '$observaciones_AireAcondicionado', '$rutaChecklist', '$buenEstado_AireAcondicionado')";
    
    if (mysqli_query($conn, $sql)) {
        if ($rutaImagen !== "S/R" && isset($_FILES['foto_AireAcondicionado'])) {
            $rutaChecklist = "img_control_vehicular/$placa/checklist/estereos";
            subirImagenAsientos($rutaChecklist, $rutaImagen, $_FILES['foto_AireAcondicionado']['tmp_name']);
        }
        return true;
    } else {
        return false;
    }
}

function insertChecklistFaros($conn, $id_checklist, $si_no_faros, $observaciones_faros, $foto_faros, $placa, $buenEstado_Faros) {
    $rutaImagen = obtenerRutaImagen($placa, "checklist_Faros", $_FILES['foto_Faros'] ?? null);
    $rutaChecklist = "img_control_vehicular/$placa/checklist/faros/" . $rutaImagen;

    $sql = "INSERT INTO checklist_faros (id_checklist, si_no, observaciones, foto, buen_estado) 
        VALUES ('$id_checklist', '$si_no_faros', '$observaciones_faros', '$rutaChecklist', '$buenEstado_Faros')";
    
    if (mysqli_query($conn, $sql)) {
        if ($rutaImagen !== "S/R" && isset($_FILES['foto_Faros'])) {
            $rutaChecklist = "img_control_vehicular/$placa/checklist/faros";
            subirImagenAsientos($rutaChecklist, $rutaImagen, $_FILES['foto_Faros']['tmp_name']);
        }
        return true;
    } else {
        return false;
    }
}

function insertChecklistGolpesExterior($conn, $id_checklist, $si_no_golpes, $observaciones_golpes, $foto_golpes, $placa, $buenEstado_Exterior) {
    $rutaImagen = obtenerRutaImagen($placa, "checklist_GolpesExterior", $_FILES['foto_Exterior'] ?? null);
    $rutaChecklist = "img_control_vehicular/$placa/checklist/golpes_exterior/" . $rutaImagen;

    $sql = "INSERT INTO checklist_golpes_exterior (id_checklist, si_no, observaciones, foto, buen_estado) 
        VALUES ('$id_checklist', '$si_no_golpes', '$observaciones_golpes', '$rutaChecklist', '$buenEstado_Exterior')";
    if (mysqli_query($conn, $sql)) {
        if ($rutaImagen !== "S/R" && isset($_FILES['foto_Exterior'])) {
            $rutaChecklist = "img_control_vehicular/$placa/checklist/golpes_exterior";
            subirImagenAsientos($rutaChecklist, $rutaImagen, $_FILES['foto_Exterior']['tmp_name']);
        }
        return true;
    } else {
        return false;
    }
}

function insertChecklistLimpiaParabrisas($conn, $id_checklist, $si_no_LimpiaParabrisas, $observaciones_LimpiaParabrisas, $foto_limpiaParabrisas, $placa, $buenEstado_Limpiaparabrisas) {
    $rutaImagen = obtenerRutaImagen($placa, "checklist_LimpiaParabrisas", $_FILES['foto_Limpiaparabrisas'] ?? null);
    $rutaChecklist = "img_control_vehicular/$placa/checklist/limpiaParabrisas/" . $rutaImagen;

    $sql = "INSERT INTO checklist_limpiaParabrisas (id_checklist, si_no, observaciones, foto, buen_estado) 
        VALUES ('$id_checklist', '$si_no_LimpiaParabrisas', '$observaciones_LimpiaParabrisas', '$rutaChecklist', '$buenEstado_Limpiaparabrisas')";
    if (mysqli_query($conn, $sql)) {
        if ($rutaImagen !== "S/R" && isset($_FILES['foto_Limpiaparabrisas'])) {
            $rutaChecklist = "img_control_vehicular/$placa/checklist/limpiaParabrisas";
            subirImagenAsientos($rutaChecklist, $rutaImagen, $_FILES['foto_Limpiaparabrisas']['tmp_name']);
        }
        return true;
    } else {
        return false;
    }
}

function insertChecklistLimpieza($conn, $id_checklist, $si_no_limpieza, $observaciones_limpieza, $foto_limpieza, $placa, $buenEstado_Limpieza) {
    $rutaImagen = obtenerRutaImagen($placa, "checklist_Limpieza", $_FILES['foto_Limpieza'] ?? null);
    $rutaChecklist = "img_control_vehicular/$placa/checklist/limpieza/" . $rutaImagen;

    $sql = "INSERT INTO checklist_limpieza (id_checklist, si_no, observaciones, foto, buen_estado) 
        VALUES ('$id_checklist', '$si_no_limpieza', '$observaciones_limpieza', '$rutaChecklist', '$buenEstado_Limpieza')";
    if (mysqli_query($conn, $sql)) {
        if ($rutaImagen !== "S/R" && isset($_FILES['foto_Limpieza'])) {
            $rutaChecklist = "img_control_vehicular/$placa/checklist/limpieza";
            subirImagenAsientos($rutaChecklist, $rutaImagen, $_FILES['foto_Limpieza']['tmp_name']);
        }
        return true;
    } else {
        return false;
    }
}

function insertChecklistLlantas($conn, $id_checklist, $no_rin, $medidas, $observaciones_Llantas, $foto_llantas, $placa, $buenEstado_Llantas) {
    $rutaImagen = obtenerRutaImagen($placa, "checklist_Llantas", $_FILES['foto_Llantas'] ?? null);
    $rutaChecklist = "img_control_vehicular/$placa/checklist/llantas/" . $rutaImagen;

    $sql = "INSERT INTO checklist_llantas (id_checklist, buen_estado, no_rin, medidas, observaciones, foto) 
        VALUES ('$id_checklist', '$buenEstado_Llantas', '$no_rin', '$medidas', '$observaciones_Llantas', '$rutaChecklist')";
    if (mysqli_query($conn, $sql)) {
        if ($rutaImagen !== "S/R" && isset($_FILES['foto_Llantas'])) {
            $rutaChecklist = "img_control_vehicular/$placa/checklist/llantas";
            subirImagenAsientos($rutaChecklist, $rutaImagen, $_FILES['foto_Llantas']['tmp_name']);
        }
        return true;
    } else {
        return false;
    }
}

function insertChecklistPlacas($conn, $id_checklist, $si_no_Placas, $observaciones_Placas, $foto_placas, $buenEstado_Placas, $placa) {
    $rutaImagen = obtenerRutaImagen($placa, "checklist_Placas", $_FILES['foto_Placas'] ?? null);
    $rutaChecklist = "img_control_vehicular/$placa/checklist/placas/" . $rutaImagen;

    $sql = "INSERT INTO checklist_placas (id_checklist, si_no, observaciones, foto, buen_estado) 
        VALUES ('$id_checklist', '$si_no_Placas', '$observaciones_Placas', '$rutaChecklist', '$buenEstado_Placas')";
    if (mysqli_query($conn, $sql)) {
        if ($rutaImagen !== "S/R" && isset($_FILES['foto_Placas'])) {
            $rutaChecklist = "img_control_vehicular/$placa/checklist/placas";
            subirImagenAsientos($rutaChecklist, $rutaImagen, $_FILES['foto_Placas']['tmp_name']);
        }
        return true;
    } else {
        return false;
    }
}

function insertChecklistPuertasLlave($conn, $id_checklist, $buenEstado_PuertasLlave, $duplicado_PuertasLlave, $observaciones_PuertasLlave, $foto_PuertasLlave, $placa) {
    $rutaImagen = obtenerRutaImagen($placa, "checklist_PuertasLlave", $_FILES['foto_PuertasLlave'] ?? null);
    $rutaChecklist = "img_control_vehicular/$placa/checklist/puertas_llave/" . $rutaImagen;

    $sql = "INSERT INTO checklist_puertas_llave (id_checklist, buen_estado, duplicado_llaves, observaciones, foto) 
        VALUES ('$id_checklist', '$buenEstado_PuertasLlave', '$duplicado_PuertasLlave', '$observaciones_PuertasLlave', '$rutaChecklist')";
    if (mysqli_query($conn, $sql)) {
        if ($rutaImagen !== "S/R" && isset($_FILES['foto_PuertasLlave'])) {
            $rutaChecklist = "img_control_vehicular/$placa/checklist/puertas_llave";
            subirImagenAsientos($rutaChecklist, $rutaImagen, $_FILES['foto_PuertasLlave']['tmp_name']);
        }
        return true;
    } else {
        return false;
    }
}

// FUNCIONES PARA DOCUMENTACION

function insertChecklistDocumentaciontarjetaC($conn, $id_checklist, $si_no_tarjetaC, $observaciones_documentacion_tarjetaC, $foto_documentacion_tarjetaC, $placa) {
    $rutaImagen = obtenerRutaImagen($placa, "checklist_TarjetaC", $_FILES['foto_tarjetaC'] ?? null);
    $rutaChecklist = "img_control_vehicular/$placa/checklist/tarjetaC/" . $rutaImagen;

    $sql = "INSERT INTO checklist_documentacion (id_checklist, si_no, t_documento, observaciones, foto) 
        VALUES ('$id_checklist', '$si_no_tarjetaC', 'Tarjeta de Circulacion', '$observaciones_documentacion_tarjetaC', '$rutaChecklist')";
    if (mysqli_query($conn, $sql)) {
        if ($rutaImagen !== "S/R" && isset($_FILES['foto_tarjetaC'])) {
            $rutaChecklist = "img_control_vehicular/$placa/checklist/tarjetaC";
            subirImagenAsientos($rutaChecklist, $rutaImagen, $_FILES['foto_tarjetaC']['tmp_name']);
        }
        return true;
    } else {
        return false;
    }
}

function insertChecklistDocumentacionRefrendo($conn, $id_checklist, $si_no_refrendo, $observaciones_documentacion_refrendo, $foto_documentacion_refrendo, $placa) {
    $rutaImagen = obtenerRutaImagen($placa, "checklist_Refrendo", $_FILES['foto_Refrendo'] ?? null);
    $rutaChecklist = "img_control_vehicular/$placa/checklist/refrendo/" . $rutaImagen;

    $sql = "INSERT INTO checklist_documentacion (id_checklist, si_no, t_documento, observaciones, foto) 
        VALUES ('$id_checklist', '$si_no_refrendo', 'Refrendo', '$observaciones_documentacion_refrendo', '$rutaChecklist')";
    if (mysqli_query($conn, $sql)) {
        if ($rutaImagen !== "S/R" && isset($_FILES['foto_Refrendo'])) {
            $rutaChecklist = "img_control_vehicular/$placa/checklist/refrendo";
            subirImagenAsientos($rutaChecklist, $rutaImagen, $_FILES['foto_Refrendo']['tmp_name']);
        }
        return true;
    } else {
        return false;
    }
}

function insertChecklistDocumentacionSeguro($conn, $id_checklist, $si_no_seguro, $vencimiento_seguro, $no_tarjeta_seguro, $observaciones_documentacion_seguro, $foto_documentacion_seguro, $placa) {
    $rutaImagen = obtenerRutaImagen($placa, "checklist_Seguro", $_FILES['foto_Seguro'] ?? null);
    $rutaChecklist = "img_control_vehicular/$placa/checklist/seguro/" . $rutaImagen;

    $sql = "INSERT INTO checklist_documentacion (id_checklist, si_no, t_documento, vencimiento, no_tarjeta, observaciones, foto) 
        VALUES ('$id_checklist', '$si_no_seguro', 'Seguro de Auto', '$vencimiento_seguro', '$no_tarjeta_seguro', '$observaciones_documentacion_seguro', '$rutaChecklist')";
    if (mysqli_query($conn, $sql)) {
        if ($rutaImagen !== "S/R" && isset($_FILES['foto_Seguro'])) {
            $rutaChecklist = "img_control_vehicular/$placa/checklist/seguro";
            subirImagenAsientos($rutaChecklist, $rutaImagen, $_FILES['foto_Seguro']['tmp_name']);
        }
        return true;
    } else {
        return false;
    }
}

function insertChecklistDocumentacionVerificacion($conn, $id_checklist, $si_no_verificacion, $vencimiento_verificacion, $observaciones_documentacion_verificacion, $foto_documentacion_verificacion, $placa) {
    $rutaImagen = obtenerRutaImagen($placa, "checklist_Verificacion", $_FILES['foto_Verificacion'] ?? null);
    $rutaChecklist = "img_control_vehicular/$placa/checklist/verificacion/" . $rutaImagen;

    $sql = "INSERT INTO checklist_documentacion (id_checklist, si_no, t_documento, vencimiento, observaciones, foto) 
        VALUES ('$id_checklist', '$si_no_verificacion', 'Verificacion', '$vencimiento_verificacion', '$observaciones_documentacion_verificacion', '$rutaChecklist')";
    if (mysqli_query($conn, $sql)) {
        if ($rutaImagen !== "S/R" && isset($_FILES['foto_Verificacion'])) {
            $rutaChecklist = "img_control_vehicular/$placa/checklist/verificacion";
            subirImagenAsientos($rutaChecklist, $rutaImagen, $_FILES['foto_Verificacion']['tmp_name']);
        }
        return true;
    } else {
        return false;
    }
}

function insertChecklistDocumentacionLicencia($conn, $id_checklist, $si_no_licencia, $vencimiento_licencia, $observaciones_documentacion_licencia, $foto_documentacion_licencia, $placa) {
    $rutaImagen = obtenerRutaImagen($placa, "checklist_Licencia", $_FILES['foto_Licencia'] ?? null);
    $rutaChecklist = "img_control_vehicular/$placa/checklist/licencia/" . $rutaImagen;

    $sql = "INSERT INTO checklist_documentacion (id_checklist, si_no, t_documento, vencimiento, observaciones, foto) 
        VALUES ('$id_checklist', '$si_no_licencia', 'Licencia de Manejo', '$vencimiento_licencia', '$observaciones_documentacion_licencia', '$rutaChecklist')";
    if (mysqli_query($conn, $sql)) {
        if ($rutaImagen !== "S/R" && isset($_FILES['foto_Licencia'])) {
            $rutaChecklist = "img_control_vehicular/$placa/checklist/licencia";
            subirImagenAsientos($rutaChecklist, $rutaImagen, $_FILES['foto_Licencia']['tmp_name']);
        }
        return true;
    } else {
        return false;
    }
}

function insertChecklistDocumentacionTarjetaEfe($conn, $id_checklist, $si_no_tarjetaEfe, $vencimiento_tarjetaEfe, $no_tarjeta_tarjetaEfe, $observaciones_documentacion_tarjetaEfe, $foto_documentacion_tarjetaEfe, $placa) {
    $rutaImagen = obtenerRutaImagen($placa, "checklist_TarjetaEfe", $_FILES['foto_TarjetaEfe'] ?? null);
    $rutaChecklist = "img_control_vehicular/$placa/checklist/tarjetaEfe/" . $rutaImagen;

    $sql = "INSERT INTO checklist_documentacion (id_checklist, si_no, t_documento, vencimiento, no_tarjeta, observaciones, foto) 
        VALUES ('$id_checklist', '$si_no_tarjetaEfe', 'Tarjeta Efecticard', '$vencimiento_tarjetaEfe', '$no_tarjeta_tarjetaEfe', '$observaciones_documentacion_tarjetaEfe', '$rutaChecklist')";
    if (mysqli_query($conn, $sql)) {
        if ($rutaImagen !== "S/R" && isset($_FILES['foto_TarjetaEfe'])) {
            $rutaChecklist = "img_control_vehicular/$placa/checklist/tarjetaEfe";
            subirImagenAsientos($rutaChecklist, $rutaImagen, $_FILES['foto_TarjetaEfe']['tmp_name']);
        }
        return true;
    } else {
        return false;
    }
}

function insertChecklistDocumentacionTarjetaIAVE($conn, $id_checklist, $si_no_tarjetaIAVE, $vencimiento_tarjetaIAVE, $no_tarjeta_tarjetaIAVE, $observaciones_documentacion_tarjetaIAVE, $foto_documentacion_tarjetaIAVE, $placa) {
    $rutaImagen = obtenerRutaImagen($placa, "checklist_TarjetaIAVE", $_FILES['foto_TarjetaIAVE'] ?? null);
    $rutaChecklist = "img_control_vehicular/$placa/checklist/tarjetaIAVE/" . $rutaImagen;

    $sql = "INSERT INTO checklist_documentacion (id_checklist, si_no, t_documento, vencimiento, no_tarjeta, observaciones, foto) 
        VALUES ('$id_checklist', '$si_no_tarjetaIAVE', 'Tarjeta IAVE', '$vencimiento_tarjetaIAVE', '$no_tarjeta_tarjetaIAVE', '$observaciones_documentacion_tarjetaIAVE', '$rutaChecklist')";
    
    if (mysqli_query($conn, $sql)) {
        if ($rutaImagen !== "S/R" && isset($_FILES['foto_TarjetaIAVE'])) {
            $rutaChecklist = "img_control_vehicular/$placa/checklist/tarjetaIAVE";
            subirImagenAsientos($rutaChecklist, $rutaImagen, $_FILES['foto_TarjetaIAVE']['tmp_name']);
        }
        return true;
    } else {
        return false;
    }
}
