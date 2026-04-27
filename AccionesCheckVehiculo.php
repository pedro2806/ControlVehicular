<?php
include 'conn.php';

$opcion = $_POST['opcion'];
$id_usuario = $_COOKIE['id_usuario'];
$no_empleado = intval($_POST['cookieNoEmpleado'] ?? ($_COOKIE['noEmpleado'] ?? 0));

if ($opcion == "llenaTVehiculosAsignados") {
    if ($no_empleado == 100 || $no_empleado == 386 || $no_empleado == 523) {
    $sql = "SELECT i.id_vehiculo, i.id_usuario, i.usuario, i.area, i.placa, i.modelo, i.color, i.anio, i.foto_general, i.estatus, i.fecha_registro, i.km_mantenimiento, i.marca, u.nombre as asignado, '' as tipo, '' as referencia, COUNT(c.id_checklist) as countChecklists
        FROM inventario i
        LEFT JOIN usuarios u ON i.id_usuario = u.id_usuario
        LEFT JOIN checklist c ON i.id_vehiculo = c.id_vehiculo
        WHERE i.estatus = 'Activo'
        GROUP BY i.id_vehiculo";
    } else {
        $sql = "SELECT i.id_vehiculo, i.id_usuario, i.usuario, i.area, i.placa, i.modelo, i.color, i.anio, i.foto_general, i.estatus, i.fecha_registro, i.km_mantenimiento, i.marca, u.nombre as asignado
                FROM inventario i
                INNER JOIN usuarios u ON i.id_usuario = u.id_usuario             
                WHERE i.id_usuario = $id_usuario OR i.id_us_asignado = $id_usuario";
    }

    $res2 = mysqli_query($conn, $sql);

    if (!$res2) {
        die(json_encode(array("error" => mysqli_error($conn))));
    }

    $registros = array();
    while ($row2 = mysqli_fetch_assoc($res2)) {
        $registros[] = array(
            'id' => $row2["id_vehiculo"],
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
            'asignado' => $row2["asignado"],
            'tieneChecklist' => intval($row2["countChecklists"]) > 0 ? true : false
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
    // Helper function to get POST value or 'S/R' if null/empty
    function getPostOrSR($key) {
        return (isset($_POST[$key]) && $_POST[$key] !== null && $_POST[$key] !== '') ? $_POST[$key] : 'S/R';
    }
    $placa = getPostOrSR('placa');
    $id_coche = getPostOrSR('id_coche');
    $motivo = getPostOrSR('motivo');

    $si_no_asientos = getPostOrSR('si_no_Asientos');
    $buenEstado_Asientos = getPostOrSR('buenEstado_Asientos');
    $observaciones_Asientos = getPostOrSR('observaciones_Asientos');

    $si_no_Limpieza = getPostOrSR('si_no_Limpieza');
    $buenEstado_Limpieza = getPostOrSR('buenEstado_Limpieza');
    $observaciones_Limpieza = getPostOrSR('observaciones_Limpieza');

    $si_no_Exterior = getPostOrSR('si_no_Exterior');
    $buenEstado_Exterior = getPostOrSR('buenEstado_Exterior');
    $observaciones_Exterior = getPostOrSR('observaciones_Exterior');

    $si_no_Graficas = getPostOrSR('si_no_Graficas');
    $buenEstado_Graficas = getPostOrSR('buenEstado_Graficas');
    $observaciones_Graficas = getPostOrSR('observaciones_Graficas');

    $si_no_Faros = getPostOrSR('si_no_Faros');
    $buenEstado_Faros = getPostOrSR('buenEstado_Faros');
    $observaciones_Faros = getPostOrSR('observaciones_Faros');

    $si_no_Placas = getPostOrSR('si_no_Placas');
    $buenEstado_Placas = getPostOrSR('buenEstado_Placas');
    $observaciones_Placas = getPostOrSR('observaciones_Placas');

    $si_no_Limpiaparabrisas = getPostOrSR('si_no_Limpiaparabrisas');
    $buenEstado_Limpiaparabrisas = getPostOrSR('buenEstado_Limpiaparabrisas');
    $observaciones_Limpiaparabrisas = getPostOrSR('observaciones_Limpiaparabrisas');

    $si_no_espejos = getPostOrSR('si_no_Espejos');
    $buenEstado_Espejos = getPostOrSR('buenEstado_Espejos');
    $observaciones_Espejos = getPostOrSR('observaciones_Espejos');

    $si_no_AireAcondicionado = getPostOrSR('si_no_AireAcondicionado');
    $buenEstado_AireAcondicionado = getPostOrSR('buenEstado_AireAcondicionado');
    $observaciones_AireAcondicionado = getPostOrSR('observaciones_AireAcondicionado');
    $CEAireAcondicionado = getPostOrSR('CEAireAcondicionado');

    $buenEstado_Llantas = getPostOrSR('buenEstado_Llantas');
    $observaciones_Llantas = getPostOrSR('observaciones_Llantas');
    $no_rin = getPostOrSR('CE_Llantas');
    $medidas = getPostOrSR('medidas_Llantas');

    $buenEstado_PuertasLlave = getPostOrSR('buenEstado_PuertasLlave');
    $duplicado_PuertasLlave = getPostOrSR('duplicado_PuertasLlave');
    $observaciones_PuertasLlave = getPostOrSR('observaciones_PuertasLlave');

    $si_no_tarjetaC = getPostOrSR('si_no_tarjetaC');
    $observaciones_tarjetaC = getPostOrSR('observaciones_tarjetaC');

    $si_no_Refrendo = getPostOrSR('si_no_Refrendo');
    $observaciones_Refrendo = getPostOrSR('observaciones_Refrendo');

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
    $vencimiento_TarjetaIAVE = getPostOrSR('vencimiento_TarjetaIAVE'); //$_POST['vencimiento_TarjetaIAVE'] ?? null;
    $no_tarjeta_TarjetaIAVE = $_POST['no_tarjeta_TarjetaIAVE'] ?? null;
    $observaciones_TarjetaIAVE = $_POST['observaciones_TarjetaIAVE'] ?? null;
    $id_revisor = '0'; // Default value, can be updated later
    $estatus = isset($_POST['estatus']) ? $_POST['estatus'] : 'completo';
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
    // Buscar borrador existente del vehículo (más reciente)
    $resBorrador = mysqli_query($conn, "SELECT id_checklist FROM checklist WHERE id_vehiculo='$id_coche' AND estatus='borrador' ORDER BY fecha DESC LIMIT 1");

    if ($resBorrador && mysqli_num_rows($resBorrador) > 0) {
        $rowBorrador = mysqli_fetch_assoc($resBorrador);
        $id_checklist = $rowBorrador['id_checklist'];
        if (!mysqli_query($conn, "UPDATE checklist SET fecha=NOW(), motivo='$motivo', estatus='$estatus' WHERE id_checklist='$id_checklist'")) {
            die(json_encode(array("error" => "Failed to update checklist: " . mysqli_error($conn))));
        }
        // Al completar, eliminar borradores huérfanos (el actual ya cambió a 'completo')
        if ($estatus === 'completo') {
            $resHuerfanos = mysqli_query($conn, "SELECT id_checklist FROM checklist WHERE id_vehiculo='$id_coche' AND estatus='borrador'");
            while ($rowH = mysqli_fetch_assoc($resHuerfanos)) {
                $hId = $rowH['id_checklist'];
                mysqli_query($conn, "DELETE FROM checklist_asientos WHERE id_checklist='$hId'");
                mysqli_query($conn, "DELETE FROM checklist_espejos_ventanas WHERE id_checklist='$hId'");
                mysqli_query($conn, "DELETE FROM checklist_estereos_aire WHERE id_checklist='$hId'");
                mysqli_query($conn, "DELETE FROM checklist_faros WHERE id_checklist='$hId'");
                mysqli_query($conn, "DELETE FROM checklist_golpes_exterior WHERE id_checklist='$hId'");
                mysqli_query($conn, "DELETE FROM checklist_graficas WHERE id_checklist='$hId'");
                mysqli_query($conn, "DELETE FROM checklist_limpiaparabrisas WHERE id_checklist='$hId'");
                mysqli_query($conn, "DELETE FROM checklist_limpieza WHERE id_checklist='$hId'");
                mysqli_query($conn, "DELETE FROM checklist_llantas WHERE id_checklist='$hId'");
                mysqli_query($conn, "DELETE FROM checklist_placas WHERE id_checklist='$hId'");
                mysqli_query($conn, "DELETE FROM checklist_puertas_llave WHERE id_checklist='$hId'");
                mysqli_query($conn, "DELETE FROM checklist_documentacion WHERE id_checklist='$hId'");
                mysqli_query($conn, "DELETE FROM checklist WHERE id_checklist='$hId'");
            }
        }
    } else {
        $sql = "INSERT INTO checklist (id_vehiculo, fecha, id_usuario, id_revisor, motivo, estatus)
            VALUES ('$id_coche', NOW(), '$id_usuario', '$id_revisor', '$motivo', '$estatus')";
        $resultadoChecklist = mysqli_query($conn, $sql);
        if (!$resultadoChecklist) {
            die(json_encode(array("error" => "Failed to insert checklist: " . mysqli_error($conn))));
        }
        $id_checklist = mysqli_insert_id($conn);
    }

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
    $resultadoLimpiaParabrisas = insertChecklistLimpiaParabrisas($conn, $id_checklist, $si_no_Limpiaparabrisas, $observaciones_Limpiaparabrisas, $foto_limpiaparabrisas, $placa, $buenEstado_Limpiaparabrisas);
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

if ($opcion == 'cargarBorrador') {
    $id_coche_borrador = $_POST['id_coche'] ?? null;

    $resBorrador = mysqli_query($conn, "SELECT id_checklist, motivo FROM checklist WHERE id_vehiculo='$id_coche_borrador' AND estatus='borrador' ORDER BY fecha DESC LIMIT 1");

    if (!$resBorrador || mysqli_num_rows($resBorrador) == 0) {
        echo json_encode(['found' => false]);
        exit;
    }

    $rowMain = mysqli_fetch_assoc($resBorrador);
    $id_checklist_borrador = $rowMain['id_checklist'];
    $data = ['found' => true, 'motivo' => $rowMain['motivo']];

    $seccionesFisicas = [
        'asientos'        => 'checklist_asientos',
        'espejos'         => 'checklist_espejos_ventanas',
        'estereos'        => 'checklist_estereos_aire',
        'faros'           => 'checklist_faros',
        'golpes'          => 'checklist_golpes_exterior',
        'graficas'        => 'checklist_graficas',
        'limpiaparabrisas'=> 'checklist_limpiaparabrisas',
        'limpieza'        => 'checklist_limpieza',
        'llantas'         => 'checklist_llantas',
        'placas'          => 'checklist_placas',
        'puertas'         => 'checklist_puertas_llave',
    ];

    foreach ($seccionesFisicas as $key => $tabla) {
        $r = mysqli_query($conn, "SELECT * FROM $tabla WHERE id_checklist='$id_checklist_borrador' LIMIT 1");
        if ($r && mysqli_num_rows($r) > 0) {
            $data[$key] = mysqli_fetch_assoc($r);
        }
    }

    $rDocs = mysqli_query($conn, "SELECT * FROM checklist_documentacion WHERE id_checklist='$id_checklist_borrador'");
    $docs = [];
    while ($rowDoc = mysqli_fetch_assoc($rDocs)) {
        $docs[$rowDoc['t_documento']] = $rowDoc;
    }
    $data['documentacion'] = $docs;

    echo json_encode($data);
    exit;
}

function obtenerRutaImagen($placa, $tipo, $archivo) {
    if ($archivo && $archivo['error'] == UPLOAD_ERR_OK) {
        return $placa . "_" . $tipo . "_" . date("Ymd_his") . "." . pathinfo($archivo['name'], PATHINFO_EXTENSION);
    }
    return "S-R.jpg";
}

function getFotoInfo($fileKey, $placa, $tipo, $subdir) {
    $archivo = $_FILES[$fileKey] ?? null;
    if ($archivo && $archivo['error'] == UPLOAD_ERR_OK) {
        $ext = pathinfo($archivo['name'], PATHINFO_EXTENSION);
        $nombre = "{$placa}_{$tipo}_" . date("Ymd_His") . ".{$ext}";
        $dir = "img_control_vehicular/{$placa}/checklist/{$subdir}";
        return [
            'ruta'   => "{$dir}/{$nombre}",
            'dir'    => $dir,
            'nombre' => $nombre,
            'tmp'    => $archivo['tmp_name'],
            'subir'  => true
        ];
    }
    $rutaExistente = !empty($_POST["ruta_{$fileKey}"]) ? $_POST["ruta_{$fileKey}"] : null;
    return [
        'ruta'   => $rutaExistente ?? '',
        'dir'    => null,
        'nombre' => null,
        'tmp'    => null,
        'subir'  => false
    ];
}

function subirImagenAsientos($rutaChecklist, $rutaImagen, $tempFilePath) {
    if (!is_dir($rutaChecklist)) {
        mkdir($rutaChecklist, 0775, true);
    }
    $destino = $rutaChecklist . "/" . basename($rutaImagen);
    // Reducir el peso de la imagen antes de moverla
    reducirPesoImagen($tempFilePath, $destino);
}

/**
 * Reduce el peso de una imagen JPEG o PNG.
 * @param string $origen Ruta temporal de la imagen subida.
 * @param string $destino Ruta final donde se guardará la imagen optimizada.
 * @param int $calidad Calidad de compresión (por defecto 75).
 */
function reducirPesoImagen($origen, $destino, $calidad = 75) {
    $info = getimagesize($origen);
    if ($info === false) {
        // Si no es una imagen válida, solo mover el archivo
        move_uploaded_file($origen, $destino);
        return;
    }

    $mime = $info['mime'];
    if ($mime == 'image/jpeg') {
        $image = imagecreatefromjpeg($origen);
        imagejpeg($image, $destino, $calidad);
        imagedestroy($image);
    } elseif ($mime == 'image/png') {
        $image = imagecreatefrompng($origen);
        // PNG usa calidad inversa: 0 (sin compresión) a 9 (máxima compresión)
        imagepng($image, $destino, 7);
        imagedestroy($image);
    } else {
        // Otros formatos, solo mover
        move_uploaded_file($origen, $destino);
    }
}


//FUNCIONES PARA UPSERT DE LOS APARTADOS DEL CHECKLIST
function insertChecklistGraficas($conn, $id_checklist, $si_no_Graficas, $observaciones_Graficas, $foto_graficas, $placa, $buenEstado_Graficas) {
    $foto = getFotoInfo('foto_Graficas', $placa, 'checklist_Graficas', 'graficas');
    $fotoSql = $foto['ruta'] !== null ? "'{$foto['ruta']}'" : "NULL";
    $r = mysqli_query($conn, "SELECT id_checklist FROM checklist_graficas WHERE id_checklist='$id_checklist'");
    if ($r && mysqli_num_rows($r) > 0) {
        $sql = "UPDATE checklist_graficas SET si_no='$si_no_Graficas', observaciones='$observaciones_Graficas', foto=$fotoSql, buen_estado='$buenEstado_Graficas' WHERE id_checklist='$id_checklist'";
    } else {
        $sql = "INSERT INTO checklist_graficas (id_checklist, si_no, observaciones, foto, buen_estado) VALUES ('$id_checklist', '$si_no_Graficas', '$observaciones_Graficas', $fotoSql, '$buenEstado_Graficas')";
    }
    if (mysqli_query($conn, $sql)) {
        if ($foto['subir']) subirImagenAsientos($foto['dir'], $foto['nombre'], $foto['tmp']);
        return true;
    }
    return false;
}

function insertChecklistAsientos($conn, $id_checklist, $si_no_asientos, $observaciones_Asientos, $buenEstado_Asientos, $placa) {
    $foto = getFotoInfo('foto_Asientos', $placa, 'checklist_Asientos', 'asientos');
    $fotoSql = $foto['ruta'] !== null ? "'{$foto['ruta']}'" : "NULL";
    $r = mysqli_query($conn, "SELECT id_checklist FROM checklist_asientos WHERE id_checklist='$id_checklist'");
    if ($r && mysqli_num_rows($r) > 0) {
        $sql = "UPDATE checklist_asientos SET si_no='$si_no_asientos', observaciones='$observaciones_Asientos', foto=$fotoSql, buen_estado='$buenEstado_Asientos' WHERE id_checklist='$id_checklist'";
    } else {
        $sql = "INSERT INTO checklist_asientos (id_checklist, si_no, observaciones, foto, buen_estado) VALUES ('$id_checklist', '$si_no_asientos', '$observaciones_Asientos', $fotoSql, '$buenEstado_Asientos')";
    }
    if (mysqli_query($conn, $sql)) {
        if ($foto['subir']) subirImagenAsientos($foto['dir'], $foto['nombre'], $foto['tmp']);
        return true;
    }
    return false;
}

function insertChecklistEspejosVentanas($conn, $id_checklist, $si_no_espejos, $observaciones_Espejos, $placa, $buenEstado_Espejos) {
    $foto = getFotoInfo('foto_Espejos', $placa, 'checklist_Espejos', 'espejos');
    $fotoSql = $foto['ruta'] !== null ? "'{$foto['ruta']}'" : "NULL";
    $r = mysqli_query($conn, "SELECT id_checklist FROM checklist_espejos_ventanas WHERE id_checklist='$id_checklist'");
    if ($r && mysqli_num_rows($r) > 0) {
        $sql = "UPDATE checklist_espejos_ventanas SET si_no='$si_no_espejos', observaciones='$observaciones_Espejos', foto=$fotoSql, buen_estado='$buenEstado_Espejos' WHERE id_checklist='$id_checklist'";
    } else {
        $sql = "INSERT INTO checklist_espejos_ventanas (id_checklist, si_no, observaciones, foto, buen_estado) VALUES ('$id_checklist', '$si_no_espejos', '$observaciones_Espejos', $fotoSql, '$buenEstado_Espejos')";
    }
    if (mysqli_query($conn, $sql)) {
        if ($foto['subir']) subirImagenAsientos($foto['dir'], $foto['nombre'], $foto['tmp']);
        return true;
    }
    return false;
}

function insertChecklistEstereosAire($conn, $id_checklist, $CEAireAcondicionado, $si_no_AireAcondicionado, $observaciones_AireAcondicionado, $foto_estereos, $placa, $buenEstado_AireAcondicionado) {
    $foto = getFotoInfo('foto_AireAcondicionado', $placa, 'checklist_Estereos', 'estereos');
    $fotoSql = $foto['ruta'] !== null ? "'{$foto['ruta']}'" : "NULL";
    $r = mysqli_query($conn, "SELECT id_checklist FROM checklist_estereos_aire WHERE id_checklist='$id_checklist'");
    if ($r && mysqli_num_rows($r) > 0) {
        $sql = "UPDATE checklist_estereos_aire SET cd_estereo='$CEAireAcondicionado', si_no='$si_no_AireAcondicionado', observaciones='$observaciones_AireAcondicionado', foto=$fotoSql, buen_estado='$buenEstado_AireAcondicionado' WHERE id_checklist='$id_checklist'";
    } else {
        $sql = "INSERT INTO checklist_estereos_aire (id_checklist, cd_estereo, si_no, observaciones, foto, buen_estado) VALUES ('$id_checklist', '$CEAireAcondicionado', '$si_no_AireAcondicionado', '$observaciones_AireAcondicionado', $fotoSql, '$buenEstado_AireAcondicionado')";
    }
    if (mysqli_query($conn, $sql)) {
        if ($foto['subir']) subirImagenAsientos($foto['dir'], $foto['nombre'], $foto['tmp']);
        return true;
    }
    return false;
}

function insertChecklistFaros($conn, $id_checklist, $si_no_faros, $observaciones_faros, $foto_faros, $placa, $buenEstado_Faros) {
    $foto = getFotoInfo('foto_Faros', $placa, 'checklist_Faros', 'faros');
    $fotoSql = $foto['ruta'] !== null ? "'{$foto['ruta']}'" : "NULL";
    $r = mysqli_query($conn, "SELECT id_checklist FROM checklist_faros WHERE id_checklist='$id_checklist'");
    if ($r && mysqli_num_rows($r) > 0) {
        $sql = "UPDATE checklist_faros SET si_no='$si_no_faros', observaciones='$observaciones_faros', foto=$fotoSql, buen_estado='$buenEstado_Faros' WHERE id_checklist='$id_checklist'";
    } else {
        $sql = "INSERT INTO checklist_faros (id_checklist, si_no, observaciones, foto, buen_estado) VALUES ('$id_checklist', '$si_no_faros', '$observaciones_faros', $fotoSql, '$buenEstado_Faros')";
    }
    if (mysqli_query($conn, $sql)) {
        if ($foto['subir']) subirImagenAsientos($foto['dir'], $foto['nombre'], $foto['tmp']);
        return true;
    }
    return false;
}

function insertChecklistGolpesExterior($conn, $id_checklist, $si_no_golpes, $observaciones_golpes, $foto_golpes, $placa, $buenEstado_Exterior) {
    $foto = getFotoInfo('foto_Exterior', $placa, 'checklist_GolpesExterior', 'golpes_exterior');
    $fotoSql = $foto['ruta'] !== null ? "'{$foto['ruta']}'" : "NULL";
    $r = mysqli_query($conn, "SELECT id_checklist FROM checklist_golpes_exterior WHERE id_checklist='$id_checklist'");
    if ($r && mysqli_num_rows($r) > 0) {
        $sql = "UPDATE checklist_golpes_exterior SET si_no='$si_no_golpes', observaciones='$observaciones_golpes', foto=$fotoSql, buen_estado='$buenEstado_Exterior' WHERE id_checklist='$id_checklist'";
    } else {
        $sql = "INSERT INTO checklist_golpes_exterior (id_checklist, si_no, observaciones, foto, buen_estado) VALUES ('$id_checklist', '$si_no_golpes', '$observaciones_golpes', $fotoSql, '$buenEstado_Exterior')";
    }
    if (mysqli_query($conn, $sql)) {
        if ($foto['subir']) subirImagenAsientos($foto['dir'], $foto['nombre'], $foto['tmp']);
        return true;
    }
    return false;
}

function insertChecklistLimpiaParabrisas($conn, $id_checklist, $si_no_LimpiaParabrisas, $observaciones_LimpiaParabrisas, $foto_limpiaParabrisas, $placa, $buenEstado_Limpiaparabrisas) {
    $foto = getFotoInfo('foto_Limpiaparabrisas', $placa, 'checklist_LimpiaParabrisas', 'limpiaParabrisas');
    $fotoSql = $foto['ruta'] !== null ? "'{$foto['ruta']}'" : "NULL";
    $r = mysqli_query($conn, "SELECT id_checklist FROM checklist_limpiaparabrisas WHERE id_checklist='$id_checklist'");
    if ($r && mysqli_num_rows($r) > 0) {
        $sql = "UPDATE checklist_limpiaparabrisas SET si_no='$si_no_LimpiaParabrisas', observaciones='$observaciones_LimpiaParabrisas', foto=$fotoSql, buen_estado='$buenEstado_Limpiaparabrisas' WHERE id_checklist='$id_checklist'";
    } else {
        $sql = "INSERT INTO checklist_limpiaparabrisas (id_checklist, si_no, observaciones, foto, buen_estado) VALUES ('$id_checklist', '$si_no_LimpiaParabrisas', '$observaciones_LimpiaParabrisas', $fotoSql, '$buenEstado_Limpiaparabrisas')";
    }
    if (mysqli_query($conn, $sql)) {
        if ($foto['subir']) subirImagenAsientos($foto['dir'], $foto['nombre'], $foto['tmp']);
        return true;
    }
    return false;
}

function insertChecklistLimpieza($conn, $id_checklist, $si_no_limpieza, $observaciones_limpieza, $foto_limpieza, $placa, $buenEstado_Limpieza) {
    $foto = getFotoInfo('foto_Limpieza', $placa, 'checklist_Limpieza', 'limpieza');
    $fotoSql = $foto['ruta'] !== null ? "'{$foto['ruta']}'" : "NULL";
    $r = mysqli_query($conn, "SELECT id_checklist FROM checklist_limpieza WHERE id_checklist='$id_checklist'");
    if ($r && mysqli_num_rows($r) > 0) {
        $sql = "UPDATE checklist_limpieza SET si_no='$si_no_limpieza', observaciones='$observaciones_limpieza', foto=$fotoSql, buen_estado='$buenEstado_Limpieza' WHERE id_checklist='$id_checklist'";
    } else {
        $sql = "INSERT INTO checklist_limpieza (id_checklist, si_no, observaciones, foto, buen_estado) VALUES ('$id_checklist', '$si_no_limpieza', '$observaciones_limpieza', $fotoSql, '$buenEstado_Limpieza')";
    }
    if (mysqli_query($conn, $sql)) {
        if ($foto['subir']) subirImagenAsientos($foto['dir'], $foto['nombre'], $foto['tmp']);
        return true;
    }
    return false;
}

function insertChecklistLlantas($conn, $id_checklist, $no_rin, $medidas, $observaciones_Llantas, $foto_llantas, $placa, $buenEstado_Llantas) {
    $foto = getFotoInfo('foto_Llantas', $placa, 'checklist_Llantas', 'llantas');
    $fotoSql = $foto['ruta'] !== null ? "'{$foto['ruta']}'" : "NULL";
    $r = mysqli_query($conn, "SELECT id_checklist FROM checklist_llantas WHERE id_checklist='$id_checklist'");
    if ($r && mysqli_num_rows($r) > 0) {
        $sql = "UPDATE checklist_llantas SET buen_estado='$buenEstado_Llantas', no_rin='$no_rin', medidas='$medidas', observaciones='$observaciones_Llantas', foto=$fotoSql WHERE id_checklist='$id_checklist'";
    } else {
        $sql = "INSERT INTO checklist_llantas (id_checklist, buen_estado, no_rin, medidas, observaciones, foto) VALUES ('$id_checklist', '$buenEstado_Llantas', '$no_rin', '$medidas', '$observaciones_Llantas', $fotoSql)";
    }
    if (mysqli_query($conn, $sql)) {
        if ($foto['subir']) subirImagenAsientos($foto['dir'], $foto['nombre'], $foto['tmp']);
        return true;
    }
    return false;
}

function insertChecklistPlacas($conn, $id_checklist, $si_no_Placas, $observaciones_Placas, $foto_placas, $buenEstado_Placas, $placa) {
    $foto = getFotoInfo('foto_Placas', $placa, 'checklist_Placas', 'placas');
    $fotoSql = $foto['ruta'] !== null ? "'{$foto['ruta']}'" : "NULL";
    $r = mysqli_query($conn, "SELECT id_checklist FROM checklist_placas WHERE id_checklist='$id_checklist'");
    if ($r && mysqli_num_rows($r) > 0) {
        $sql = "UPDATE checklist_placas SET si_no='$si_no_Placas', observaciones='$observaciones_Placas', foto=$fotoSql, buen_estado='$buenEstado_Placas' WHERE id_checklist='$id_checklist'";
    } else {
        $sql = "INSERT INTO checklist_placas (id_checklist, si_no, observaciones, foto, buen_estado) VALUES ('$id_checklist', '$si_no_Placas', '$observaciones_Placas', $fotoSql, '$buenEstado_Placas')";
    }
    if (mysqli_query($conn, $sql)) {
        if ($foto['subir']) subirImagenAsientos($foto['dir'], $foto['nombre'], $foto['tmp']);
        return true;
    }
    return false;
}

function insertChecklistPuertasLlave($conn, $id_checklist, $buenEstado_PuertasLlave, $duplicado_PuertasLlave, $observaciones_PuertasLlave, $foto_PuertasLlave, $placa) {
    $foto = getFotoInfo('foto_PuertasLlave', $placa, 'checklist_PuertasLlave', 'puertas_llave');
    $fotoSql = $foto['ruta'] !== null ? "'{$foto['ruta']}'" : "NULL";
    $r = mysqli_query($conn, "SELECT id_checklist FROM checklist_puertas_llave WHERE id_checklist='$id_checklist'");
    if ($r && mysqli_num_rows($r) > 0) {
        $sql = "UPDATE checklist_puertas_llave SET buen_estado='$buenEstado_PuertasLlave', duplicado_llaves='$duplicado_PuertasLlave', observaciones='$observaciones_PuertasLlave', foto=$fotoSql WHERE id_checklist='$id_checklist'";
    } else {
        $sql = "INSERT INTO checklist_puertas_llave (id_checklist, buen_estado, duplicado_llaves, observaciones, foto) VALUES ('$id_checklist', '$buenEstado_PuertasLlave', '$duplicado_PuertasLlave', '$observaciones_PuertasLlave', $fotoSql)";
    }
    if (mysqli_query($conn, $sql)) {
        if ($foto['subir']) subirImagenAsientos($foto['dir'], $foto['nombre'], $foto['tmp']);
        return true;
    }
    return false;
}

// FUNCIONES PARA DOCUMENTACION

function insertChecklistDocumentaciontarjetaC($conn, $id_checklist, $si_no_tarjetaC, $observaciones_documentacion_tarjetaC, $foto_documentacion_tarjetaC, $placa) {
    $foto = getFotoInfo('foto_tarjetaC', $placa, 'checklist_TarjetaC', 'tarjetaC');
    $fotoSql = $foto['ruta'] !== null ? "'{$foto['ruta']}'" : "NULL";
    $r = mysqli_query($conn, "SELECT id_checklist FROM checklist_documentacion WHERE id_checklist='$id_checklist' AND t_documento='Tarjeta de Circulacion'");
    if ($r && mysqli_num_rows($r) > 0) {
        $sql = "UPDATE checklist_documentacion SET si_no='$si_no_tarjetaC', observaciones='$observaciones_documentacion_tarjetaC', foto=$fotoSql WHERE id_checklist='$id_checklist' AND t_documento='Tarjeta de Circulacion'";
    } else {
        $sql = "INSERT INTO checklist_documentacion (id_checklist, si_no, t_documento, observaciones, foto, entregado, vencimiento, no_tarjeta) VALUES ('$id_checklist', '$si_no_tarjetaC', 'Tarjeta de Circulacion', '$observaciones_documentacion_tarjetaC', $fotoSql, 'S/R', NULL, 'S/R')";
    }
    if (mysqli_query($conn, $sql)) {
        if ($foto['subir']) subirImagenAsientos($foto['dir'], $foto['nombre'], $foto['tmp']);
        return true;
    }
    return false;
}

function insertChecklistDocumentacionRefrendo($conn, $id_checklist, $si_no_refrendo, $observaciones_documentacion_refrendo, $foto_documentacion_refrendo, $placa) {
    $foto = getFotoInfo('foto_Refrendo', $placa, 'checklist_Refrendo', 'refrendo');
    $fotoSql = $foto['ruta'] !== null ? "'{$foto['ruta']}'" : "NULL";
    $r = mysqli_query($conn, "SELECT id_checklist FROM checklist_documentacion WHERE id_checklist='$id_checklist' AND t_documento='Refrendo'");
    if ($r && mysqli_num_rows($r) > 0) {
        $sql = "UPDATE checklist_documentacion SET si_no='$si_no_refrendo', observaciones='$observaciones_documentacion_refrendo', foto=$fotoSql WHERE id_checklist='$id_checklist' AND t_documento='Refrendo'";
    } else {
        $sql = "INSERT INTO checklist_documentacion (id_checklist, si_no, t_documento, observaciones, foto, entregado, vencimiento, no_tarjeta) VALUES ('$id_checklist', '$si_no_refrendo', 'Refrendo', '$observaciones_documentacion_refrendo', $fotoSql, 'S/R', NULL, 'S/R')";
    }
    if (mysqli_query($conn, $sql)) {
        if ($foto['subir']) subirImagenAsientos($foto['dir'], $foto['nombre'], $foto['tmp']);
        return true;
    }
    return false;
}

function insertChecklistDocumentacionSeguro($conn, $id_checklist, $si_no_seguro, $vencimiento_seguro, $no_tarjeta_seguro, $observaciones_documentacion_seguro, $foto_documentacion_seguro, $placa) {
    $foto = getFotoInfo('foto_Seguro', $placa, 'checklist_Seguro', 'seguro');
    $fotoSql = $foto['ruta'] !== null ? "'{$foto['ruta']}'" : "NULL";
    $r = mysqli_query($conn, "SELECT id_checklist FROM checklist_documentacion WHERE id_checklist='$id_checklist' AND t_documento='Seguro de Auto'");
    if ($r && mysqli_num_rows($r) > 0) {
        $sql = "UPDATE checklist_documentacion SET si_no='$si_no_seguro', vencimiento='$vencimiento_seguro', no_tarjeta='$no_tarjeta_seguro', observaciones='$observaciones_documentacion_seguro', foto=$fotoSql WHERE id_checklist='$id_checklist' AND t_documento='Seguro de Auto'";
    } else {
        $sql = "INSERT INTO checklist_documentacion (id_checklist, si_no, t_documento, vencimiento, no_tarjeta, observaciones, foto, entregado) VALUES ('$id_checklist', '$si_no_seguro', 'Seguro de Auto', '$vencimiento_seguro', '$no_tarjeta_seguro', '$observaciones_documentacion_seguro', $fotoSql, 'S/R')";
    }
    if (mysqli_query($conn, $sql)) {
        if ($foto['subir']) subirImagenAsientos($foto['dir'], $foto['nombre'], $foto['tmp']);
        return true;
    }
    return false;
}

function insertChecklistDocumentacionVerificacion($conn, $id_checklist, $si_no_verificacion, $vencimiento_verificacion, $observaciones_documentacion_verificacion, $foto_documentacion_verificacion, $placa) {
    $foto = getFotoInfo('foto_Verificacion', $placa, 'checklist_Verificacion', 'verificacion');
    $fotoSql = $foto['ruta'] !== null ? "'{$foto['ruta']}'" : "NULL";
    $r = mysqli_query($conn, "SELECT id_checklist FROM checklist_documentacion WHERE id_checklist='$id_checklist' AND t_documento='Verificacion'");
    if ($r && mysqli_num_rows($r) > 0) {
        $sql = "UPDATE checklist_documentacion SET si_no='$si_no_verificacion', vencimiento='$vencimiento_verificacion', observaciones='$observaciones_documentacion_verificacion', foto=$fotoSql WHERE id_checklist='$id_checklist' AND t_documento='Verificacion'";
    } else {
        $sql = "INSERT INTO checklist_documentacion (id_checklist, si_no, t_documento, vencimiento, observaciones, foto, entregado, no_tarjeta) VALUES ('$id_checklist', '$si_no_verificacion', 'Verificacion', '$vencimiento_verificacion', '$observaciones_documentacion_verificacion', $fotoSql, 'S/R', 'S/R')";
    }
    if (mysqli_query($conn, $sql)) {
        if ($foto['subir']) subirImagenAsientos($foto['dir'], $foto['nombre'], $foto['tmp']);
        return true;
    }
    return false;
}

function insertChecklistDocumentacionLicencia($conn, $id_checklist, $si_no_licencia, $vencimiento_licencia, $observaciones_documentacion_licencia, $foto_documentacion_licencia, $placa) {
    $foto = getFotoInfo('foto_Licencia', $placa, 'checklist_Licencia', 'licencia');
    $fotoSql = $foto['ruta'] !== null ? "'{$foto['ruta']}'" : "NULL";
    $r = mysqli_query($conn, "SELECT id_checklist FROM checklist_documentacion WHERE id_checklist='$id_checklist' AND t_documento='Licencia de Manejo'");
    if ($r && mysqli_num_rows($r) > 0) {
        $sql = "UPDATE checklist_documentacion SET si_no='$si_no_licencia', vencimiento='$vencimiento_licencia', observaciones='$observaciones_documentacion_licencia', foto=$fotoSql WHERE id_checklist='$id_checklist' AND t_documento='Licencia de Manejo'";
    } else {
        $sql = "INSERT INTO checklist_documentacion (id_checklist, si_no, t_documento, vencimiento, observaciones, foto, entregado, no_tarjeta) VALUES ('$id_checklist', '$si_no_licencia', 'Licencia de Manejo', '$vencimiento_licencia', '$observaciones_documentacion_licencia', $fotoSql, 'S/R', 'S/R')";
    }
    if (mysqli_query($conn, $sql)) {
        if ($foto['subir']) subirImagenAsientos($foto['dir'], $foto['nombre'], $foto['tmp']);
        return true;
    }
    return false;
}

function insertChecklistDocumentacionTarjetaEfe($conn, $id_checklist, $si_no_tarjetaEfe, $vencimiento_tarjetaEfe, $no_tarjeta_tarjetaEfe, $observaciones_documentacion_tarjetaEfe, $foto_documentacion_tarjetaEfe, $placa) {
    $foto = getFotoInfo('foto_TarjetaEfe', $placa, 'checklist_TarjetaEfe', 'tarjetaEfe');
    $fotoSql = $foto['ruta'] !== null ? "'{$foto['ruta']}'" : "NULL";
    $r = mysqli_query($conn, "SELECT id_checklist FROM checklist_documentacion WHERE id_checklist='$id_checklist' AND t_documento='Tarjeta Efecticard'");
    if ($r && mysqli_num_rows($r) > 0) {
        $sql = "UPDATE checklist_documentacion SET si_no='$si_no_tarjetaEfe', vencimiento='$vencimiento_tarjetaEfe', no_tarjeta='$no_tarjeta_tarjetaEfe', observaciones='$observaciones_documentacion_tarjetaEfe', foto=$fotoSql WHERE id_checklist='$id_checklist' AND t_documento='Tarjeta Efecticard'";
    } else {
        $sql = "INSERT INTO checklist_documentacion (id_checklist, si_no, t_documento, vencimiento, no_tarjeta, observaciones, foto, entregado) VALUES ('$id_checklist', '$si_no_tarjetaEfe', 'Tarjeta Efecticard', '$vencimiento_tarjetaEfe', '$no_tarjeta_tarjetaEfe', '$observaciones_documentacion_tarjetaEfe', $fotoSql, 'S/R')";
    }
    if (mysqli_query($conn, $sql)) {
        if ($foto['subir']) subirImagenAsientos($foto['dir'], $foto['nombre'], $foto['tmp']);
        return true;
    }
    return false;
}

function insertChecklistDocumentacionTarjetaIAVE($conn, $id_checklist, $si_no_tarjetaIAVE, $vencimiento_tarjetaIAVE, $no_tarjeta_tarjetaIAVE, $observaciones_documentacion_tarjetaIAVE, $foto_documentacion_tarjetaIAVE, $placa) {
    $foto = getFotoInfo('foto_TarjetaIAVE', $placa, 'checklist_TarjetaIAVE', 'tarjetaIAVE');
    $fotoSql = $foto['ruta'] !== null ? "'{$foto['ruta']}'" : "NULL";
    $r = mysqli_query($conn, "SELECT id_checklist FROM checklist_documentacion WHERE id_checklist='$id_checklist' AND t_documento='Tarjeta IAVE'");
    if ($r && mysqli_num_rows($r) > 0) {
        $sql = "UPDATE checklist_documentacion SET si_no='$si_no_tarjetaIAVE', vencimiento='$vencimiento_tarjetaIAVE', no_tarjeta='$no_tarjeta_tarjetaIAVE', observaciones='$observaciones_documentacion_tarjetaIAVE', foto=$fotoSql WHERE id_checklist='$id_checklist' AND t_documento='Tarjeta IAVE'";
    } else {
        $sql = "INSERT INTO checklist_documentacion (id_checklist, si_no, t_documento, vencimiento, no_tarjeta, observaciones, foto, entregado) VALUES ('$id_checklist', '$si_no_tarjetaIAVE', 'Tarjeta IAVE', '$vencimiento_tarjetaIAVE', '$no_tarjeta_tarjetaIAVE', '$observaciones_documentacion_tarjetaIAVE', $fotoSql, 'S/R')";
    }
    if (mysqli_query($conn, $sql)) {
        if ($foto['subir']) subirImagenAsientos($foto['dir'], $foto['nombre'], $foto['tmp']);
        return true;
    }
    return false;
}
