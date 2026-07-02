<?php
include 'includes/api_bootstrap.php';

$opcion = $_POST['opcion'] ?? '';
$id_usuario = $_COOKIE['id_usuario'] ?? null;
$no_empleado = intval($_POST['cookieNoEmpleado'] ?? ($_COOKIE['noEmpleado'] ?? 0));

if ($opcion == "llenaTVehiculosAsignados") {
    $tieneAccesoTotal = false;
    $stmtAcc = $conn->prepare("SELECT id FROM mess_rrhh.accesos_especiales WHERE noEmpleado = ? AND sistema = 'ctrlVehicular' AND opcion = 'verTodosVehiculo' AND estatus = 1 LIMIT 1");
    if ($stmtAcc) {
        $stmtAcc->bind_param("i", $no_empleado);
        $stmtAcc->execute();
        $tieneAccesoTotal = $stmtAcc->get_result()->num_rows > 0;
    }

    if ($tieneAccesoTotal) {
    $sql = "SELECT i.id_vehiculo, i.id_usuario, i.usuario, i.area, i.placa, i.modelo, i.color, i.anio, i.foto_general, i.estatus, i.fecha_registro, i.km_mantenimiento, i.marca, u.nombre as asignado, '' as tipo, '' as referencia, c.estatus as estatusChecklist
        FROM inventario i
        LEFT JOIN usuarios u ON i.id_usuario = u.id_usuario
        LEFT JOIN checklist c ON c.id_checklist = (
            SELECT id_checklist FROM checklist
            WHERE id_vehiculo = i.id_vehiculo
            ORDER BY fecha DESC, id_checklist DESC
            LIMIT 1
        )
        WHERE i.estatus = 'Activo'";
    } else {
        $sql = "SELECT i.id_vehiculo, i.id_usuario, i.usuario, i.area, i.placa, i.modelo, i.color, i.anio, i.foto_general, i.estatus, i.fecha_registro, i.km_mantenimiento, i.marca, u.nombre as asignado, '' as tipo, '' as referencia, '' as estatusChecklist
                FROM inventario i
                LEFT JOIN usuarios u ON i.id_usuario = u.id_usuario
                WHERE i.id_usuario = '$id_usuario' OR i.id_us_asignado = '$id_usuario'";
    }

    $res2 = mysqli_query($conn, $sql);
    if (!$res2) { die(json_encode(array("error" => mysqli_error($conn)))); }

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
            'estatusChecklist' => $row2["estatusChecklist"] ?? null
        );
    }
    echo json_encode($registros);
}

if ($opcion == 'verChecks') {
    $id_coche = $_POST['idCoche'] ?? null;
    $sql = "SELECT * FROM checklist WHERE id_vehiculo = '$id_coche' ORDER BY fecha DESC";
    $res2 = mysqli_query($conn, $sql);
    if (!$res2) { die(json_encode(array("error" => mysqli_error($conn)))); }

    $registros = array();
    while ($row2 = mysqli_fetch_assoc($res2)) {
        $registros[] = array(
            'id' => $row2["id_checklist"],
            'estatus' => $row2["estatus"],
            'fecha' => $row2["fecha"],
            'id_usuario' => $row2["id_usuario"],
            'id_revisor' => $row2["id_revisor"],
            'motivo' => $row2["motivo"]
        );
    }
    echo json_encode(empty($registros) ? array("message" => "Sin registros.") : $registros);
}

// ======== CONSULTAS DE SECCIONES DE CHECKLIST (genérica) ========

$id_checklist = $_POST['idCheck'] ?? null;

$SECCIONES_CHECK = [
    'checklist_asientos'         => ['nombre' => 'Asientos',                     'tipo' => 'standard'],
    'checklist_espejos_ventanas' => ['nombre' => 'Espejos y Ventanas',           'tipo' => 'standard'],
    'checklist_estereos_aire'    => ['nombre' => 'Estereo y Aire Acondicionado', 'tipo' => 'standard'],
    'checklist_faros'            => ['nombre' => 'Faros',                        'tipo' => 'standard'],
    'checklist_golpes_exterior'  => ['nombre' => 'Golpes Exterior',              'tipo' => 'standard'],
    'checklist_graficas'         => ['nombre' => 'Graficas',                     'tipo' => 'standard'],
    'checklist_limpiaparabrisas' => ['nombre' => 'Limpiaparabrisas',             'tipo' => 'standard'],
    'checklist_limpieza'         => ['nombre' => 'Limpieza',                     'tipo' => 'standard'],
    'checklist_placas'           => ['nombre' => 'Placas',                       'tipo' => 'standard'],
    'checklist_llantas'          => ['nombre' => 'Llantas',                      'tipo' => 'llantas'],
    'checklist_documentacion'    => ['nombre' => null,                           'tipo' => 'documentacion'],
];

// checklist_puertas_llaves usa tabla checklist_puertas_llave (sin s)
$SECCIONES_CHECK_ALIAS = ['checklist_puertas_llaves' => ['tabla' => 'checklist_puertas_llave', 'nombre' => 'Puertas y Llaves', 'tipo' => 'standard']];

function consultarSeccionChecklist($conn, $tabla, $config, $id_checklist) {
    $sql = "SELECT * FROM $tabla WHERE id_checklist = '$id_checklist'";
    $res = mysqli_query($conn, $sql);
    if (!$res) { die(json_encode(["error" => mysqli_error($conn)])); }

    $registros = [];
    while ($row = mysqli_fetch_assoc($res)) {
        switch ($config['tipo']) {
            case 'standard':
                $registros[] = [
                    'nombre_seccion' => $config['nombre'],
                    'Si_No'          => $row["si_no"] ?? null,
                    'Observaciones'  => $row["obervaciones"] ?? null,
                    'Buen_estado'    => $row["buen_estado"] ?? null,
                    'imagen'         => $row["foto"] ?? null
                ];
                break;
            case 'llantas':
                $registros[] = [
                    'nombre_seccion' => 'Llantas',
                    'Medidas'        => $row["medidas"] ?? null,
                    'No_Rin'         => $row["no_rin"] ?? null,
                    'Observaciones'  => $row["obervaciones"] ?? null,
                    'Buen_estado'    => $row["buen_estado"] ?? null,
                    'imagen'         => $row["foto"] ?? null
                ];
                break;
            case 'documentacion':
                $registros[] = [
                    'nombre_seccion' => $row["t_documento"] ?? null,
                    'Si_No'          => $row["si_no"] ?? null,
                    'Observaciones'  => $row["obervaciones"] ?? null,
                    'No_tarjeta'     => $row["no_tarjeta"] ?? null,
                    'imagen'         => $row["foto"] ?? null
                ];
                break;
        }
    }
    echo json_encode($registros);
}

if (isset($SECCIONES_CHECK[$opcion])) {
    consultarSeccionChecklist($conn, $opcion, $SECCIONES_CHECK[$opcion], $id_checklist);
} elseif (isset($SECCIONES_CHECK_ALIAS[$opcion])) {
    $alias = $SECCIONES_CHECK_ALIAS[$opcion];
    consultarSeccionChecklist($conn, $alias['tabla'], $alias, $id_checklist);
}

// ======== FUNCIONES HELPER Y VARIABLES POST ========

function getPostOrSR($key) {
    return (isset($_POST[$key]) && $_POST[$key] !== null && $_POST[$key] !== '') ? $_POST[$key] : '';
}

function checklistEsCompleto($campos) {
    foreach ($campos as $v) {
        if ($v === null || $v === '') return false;
    }
    return true;
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
$no_rin = getPostOrSR('CELlantas');
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
$vencimiento_TarjetaIAVE = getPostOrSR('vencimiento_TarjetaIAVE');
$no_tarjeta_TarjetaIAVE = $_POST['no_tarjeta_TarjetaIAVE'] ?? null;
$observaciones_TarjetaIAVE = $_POST['observaciones_TarjetaIAVE'] ?? null;
$id_revisor = '0';
$estatus = isset($_POST['estatus']) ? $_POST['estatus'] : 'completo';
$opcion = $_POST['opcion'] ?? null;

// ======== FUNCIONES GENÉRICAS DE UPSERT ========

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
        return ['ruta' => "{$dir}/{$nombre}", 'dir' => $dir, 'nombre' => $nombre, 'tmp' => $archivo['tmp_name'], 'subir' => true];
    }
    $rutaExistente = !empty($_POST["ruta_{$fileKey}"]) ? $_POST["ruta_{$fileKey}"] : null;
    return ['ruta' => $rutaExistente ?? '', 'dir' => null, 'nombre' => null, 'tmp' => null, 'subir' => false];
}

function subirImagenAsientos($rutaChecklist, $rutaImagen, $tempFilePath) {
    if (!is_dir($rutaChecklist)) { mkdir($rutaChecklist, 0775, true); }
    $destino = $rutaChecklist . "/" . basename($rutaImagen);
    reducirPesoImagen($tempFilePath, $destino);
}

function reducirPesoImagen($origen, $destino, $calidad = 75) {
    $info = getimagesize($origen);
    if ($info === false) { move_uploaded_file($origen, $destino); return; }
    $mime = $info['mime'];
    if ($mime == 'image/jpeg') {
        $image = imagecreatefromjpeg($origen);
        imagejpeg($image, $destino, $calidad);
        imagedestroy($image);
    } elseif ($mime == 'image/png') {
        $image = imagecreatefrompng($origen);
        imagepng($image, $destino, 7);
        imagedestroy($image);
    } else {
        move_uploaded_file($origen, $destino);
    }
}

function upsertChecklistSeccion($conn, $tabla, $id_checklist, $campos, $fotoKey, $placa, $fotoTipo, $subdir) {
    $foto = getFotoInfo($fotoKey, $placa, $fotoTipo, $subdir);
    $fotoSql = $foto['ruta'] !== null ? "'{$foto['ruta']}'" : "NULL";

    $r = mysqli_query($conn, "SELECT id_checklist FROM $tabla WHERE id_checklist='$id_checklist'");
    if ($r && mysqli_num_rows($r) > 0) {
        $sets = [];
        foreach ($campos as $col => $val) { $sets[] = "$col='$val'"; }
        $sets[] = "foto=$fotoSql";
        $sql = "UPDATE $tabla SET " . implode(', ', $sets) . " WHERE id_checklist='$id_checklist'";
    } else {
        $cols = array_keys($campos);
        $vals = array_values($campos);
        $cols[] = 'foto';
        $colStr = 'id_checklist, ' . implode(', ', $cols);
        $valStr = "'$id_checklist', '" . implode("', '", $vals) . "', $fotoSql";
        $sql = "INSERT INTO $tabla ($colStr) VALUES ($valStr)";
    }
    if (mysqli_query($conn, $sql)) {
        if ($foto['subir']) subirImagenAsientos($foto['dir'], $foto['nombre'], $foto['tmp']);
        return true;
    }
    return false;
}

function upsertChecklistDocumentacion($conn, $id_checklist, $t_documento, $campos, $fotoKey, $placa, $fotoTipo, $subdir) {
    $foto = getFotoInfo($fotoKey, $placa, $fotoTipo, $subdir);
    $fotoSql = $foto['ruta'] !== null ? "'{$foto['ruta']}'" : "NULL";

    $r = mysqli_query($conn, "SELECT id_checklist FROM checklist_documentacion WHERE id_checklist='$id_checklist' AND t_documento='$t_documento'");
    if ($r && mysqli_num_rows($r) > 0) {
        $sets = [];
        foreach ($campos as $col => $val) { $sets[] = "$col='$val'"; }
        $sets[] = "foto=$fotoSql";
        $sql = "UPDATE checklist_documentacion SET " . implode(', ', $sets) . " WHERE id_checklist='$id_checklist' AND t_documento='$t_documento'";
    } else {
        $allCols = ['id_checklist', 't_documento'];
        $allVals = ["'$id_checklist'", "'$t_documento'"];
        foreach ($campos as $col => $val) { $allCols[] = $col; $allVals[] = "'$val'"; }
        $allCols[] = 'foto';
        $allVals[] = $fotoSql;
        if (!array_key_exists('entregado', $campos))  { $allCols[] = 'entregado';  $allVals[] = "'S/R'"; }
        if (!array_key_exists('vencimiento', $campos)){ $allCols[] = 'vencimiento'; $allVals[] = "NULL"; }
        if (!array_key_exists('no_tarjeta', $campos)) { $allCols[] = 'no_tarjeta'; $allVals[] = "'S/R'"; }
        $sql = "INSERT INTO checklist_documentacion (" . implode(', ', $allCols) . ") VALUES (" . implode(', ', $allVals) . ")";
    }
    if (mysqli_query($conn, $sql)) {
        if ($foto['subir']) subirImagenAsientos($foto['dir'], $foto['nombre'], $foto['tmp']);
        return true;
    }
    return false;
}

// ======== GUARDAR CHECKLIST ========

$TABLAS_CHECKLIST = [
    'checklist_asientos', 'checklist_espejos_ventanas', 'checklist_estereos_aire',
    'checklist_faros', 'checklist_golpes_exterior', 'checklist_graficas',
    'checklist_limpiaparabrisas', 'checklist_limpieza', 'checklist_llantas',
    'checklist_placas', 'checklist_puertas_llave', 'checklist_documentacion'
];

if ($opcion == 'guardarCheckIn') {
    $resBorrador = mysqli_query($conn, "SELECT id_checklist FROM checklist WHERE id_vehiculo='$id_coche' AND estatus='borrador' ORDER BY fecha DESC LIMIT 1");

    if ($resBorrador && mysqli_num_rows($resBorrador) > 0) {
        $rowBorrador = mysqli_fetch_assoc($resBorrador);
        $id_checklist = $rowBorrador['id_checklist'];
        if (!mysqli_query($conn, "UPDATE checklist SET fecha=NOW(), motivo='$motivo', estatus='$estatus' WHERE id_checklist='$id_checklist'")) {
            die(json_encode(array("error" => "Failed to update checklist: " . mysqli_error($conn))));
        }
        if ($estatus === 'completo') {
            $resHuerfanos = mysqli_query($conn, "SELECT id_checklist FROM checklist WHERE id_vehiculo='$id_coche' AND estatus='borrador'");
            while ($rowH = mysqli_fetch_assoc($resHuerfanos)) {
                $hId = $rowH['id_checklist'];
                foreach ($TABLAS_CHECKLIST as $t) { mysqli_query($conn, "DELETE FROM $t WHERE id_checklist='$hId'"); }
                mysqli_query($conn, "DELETE FROM checklist WHERE id_checklist='$hId'");
            }
        }
    } else {
        $sql = "INSERT INTO checklist (id_vehiculo, fecha, id_usuario, id_revisor, motivo, estatus) VALUES ('$id_coche', NOW(), '$id_usuario', '$id_revisor', '$motivo', '$estatus')";
        $resultadoChecklist = mysqli_query($conn, $sql);
        if (!$resultadoChecklist) { die(json_encode(array("error" => "Failed to insert checklist: " . mysqli_error($conn)))); }
        $id_checklist = mysqli_insert_id($conn);
    }

    // Secciones físicas — todas usan upsertChecklistSeccion
    $secciones = [
        ['checklist_asientos',         ['si_no' => $si_no_asientos, 'observaciones' => $observaciones_Asientos, 'buen_estado' => $buenEstado_Asientos],                                          'foto_Asientos',         'checklist_Asientos',         'asientos'],
        ['checklist_espejos_ventanas', ['si_no' => $si_no_espejos, 'observaciones' => $observaciones_Espejos, 'buen_estado' => $buenEstado_Espejos],                                             'foto_Espejos',          'checklist_Espejos',          'espejos'],
        ['checklist_estereos_aire',    ['cd_estereo' => $CEAireAcondicionado, 'si_no' => $si_no_AireAcondicionado, 'observaciones' => $observaciones_AireAcondicionado, 'buen_estado' => $buenEstado_AireAcondicionado], 'foto_AireAcondicionado', 'checklist_Estereos', 'estereos'],
        ['checklist_faros',            ['si_no' => $si_no_Faros, 'observaciones' => $observaciones_Faros, 'buen_estado' => $buenEstado_Faros],                                                    'foto_Faros',            'checklist_Faros',            'faros'],
        ['checklist_golpes_exterior',  ['si_no' => $si_no_Exterior, 'observaciones' => $observaciones_Exterior, 'buen_estado' => $buenEstado_Exterior],                                           'foto_Exterior',         'checklist_GolpesExterior',   'golpes_exterior'],
        ['checklist_graficas',         ['si_no' => $si_no_Graficas, 'observaciones' => $observaciones_Graficas, 'buen_estado' => $buenEstado_Graficas],                                           'foto_Graficas',         'checklist_Graficas',         'graficas'],
        ['checklist_limpiaparabrisas', ['si_no' => $si_no_Limpiaparabrisas, 'observaciones' => $observaciones_Limpiaparabrisas, 'buen_estado' => $buenEstado_Limpiaparabrisas],                    'foto_Limpiaparabrisas', 'checklist_LimpiaParabrisas', 'limpiaParabrisas'],
        ['checklist_limpieza',         ['si_no' => $si_no_Limpieza, 'observaciones' => $observaciones_Limpieza, 'buen_estado' => $buenEstado_Limpieza],                                           'foto_Limpieza',         'checklist_Limpieza',         'limpieza'],
        ['checklist_llantas',          ['buen_estado' => $buenEstado_Llantas, 'no_rin' => $no_rin, 'medidas' => $medidas, 'observaciones' => $observaciones_Llantas],                              'foto_Llantas',          'checklist_Llantas',          'llantas'],
        ['checklist_placas',           ['si_no' => $si_no_Placas, 'observaciones' => $observaciones_Placas, 'buen_estado' => $buenEstado_Placas],                                                 'foto_Placas',           'checklist_Placas',           'placas'],
        ['checklist_puertas_llave',    ['buen_estado' => $buenEstado_PuertasLlave, 'duplicado_llaves' => $duplicado_PuertasLlave, 'observaciones' => $observaciones_PuertasLlave],                 'foto_PuertasLlave',     'checklist_PuertasLlave',     'puertas_llave'],
    ];

    foreach ($secciones as $s) {
        $resultado = upsertChecklistSeccion($conn, $s[0], $id_checklist, $s[1], $s[2], $placa, $s[3], $s[4]);
        if (!$resultado) { die(json_encode(["error" => "Failed to insert $s[0]: " . mysqli_error($conn)])); }
    }

    // Documentación — todas usan upsertChecklistDocumentacion
    $documentos = [
        ['Tarjeta de Circulacion', ['si_no' => $si_no_tarjetaC, 'observaciones' => $observaciones_tarjetaC],                                                                  'foto_tarjetaC',    'checklist_TarjetaC',    'tarjetaC'],
        ['Refrendo',               ['si_no' => $si_no_Refrendo, 'observaciones' => $observaciones_Refrendo],                                                                  'foto_Refrendo',    'checklist_Refrendo',    'refrendo'],
        ['Seguro de Auto',         ['si_no' => $si_no_Seguro, 'vencimiento' => $vencimiento_Seguro, 'no_tarjeta' => $no_tarjeta_Seguro, 'observaciones' => $observaciones_Seguro],              'foto_Seguro',      'checklist_Seguro',      'seguro'],
        ['Verificacion',           ['si_no' => $si_no_Verificacion, 'vencimiento' => $vencimiento_Verificacion, 'observaciones' => $observaciones_Verificacion],               'foto_Verificacion','checklist_Verificacion','verificacion'],
        ['Licencia de Manejo',     ['si_no' => $si_no_Licencia, 'vencimiento' => $vencimiento_Licencia, 'observaciones' => $observaciones_Licencia],                           'foto_Licencia',    'checklist_Licencia',    'licencia'],
        ['Tarjeta Efecticard',     ['si_no' => $si_no_TarjetaEfe, 'vencimiento' => $vencimiento_TarjetaEfe, 'no_tarjeta' => $no_tarjeta_TarjetaEfe, 'observaciones' => $observaciones_TarjetaEfe], 'foto_TarjetaEfe',  'checklist_TarjetaEfe',  'tarjetaEfe'],
        ['Tarjeta IAVE',           ['si_no' => $si_no_TarjetaIAVE, 'vencimiento' => $vencimiento_TarjetaIAVE, 'no_tarjeta' => $no_tarjeta_TarjetaIAVE, 'observaciones' => $observaciones_TarjetaIAVE], 'foto_TarjetaIAVE', 'checklist_TarjetaIAVE', 'tarjetaIAVE'],
    ];

    foreach ($documentos as $d) {
        $resultado = upsertChecklistDocumentacion($conn, $id_checklist, $d[0], $d[1], $d[2], $placa, $d[3], $d[4]);
        if (!$resultado) { die(json_encode(["error" => "Failed to insert documentacion $d[0]: " . mysqli_error($conn)])); }
    }

    echo json_encode(array("success" => "Checklist and related data inserted successfully."));
}

// ======== CARGAR BORRADOR ========

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
        $data[$key] = ($r && mysqli_num_rows($r) > 0) ? mysqli_fetch_assoc($r) : null;
    }

    $rDocs = mysqli_query($conn, "SELECT * FROM checklist_documentacion WHERE id_checklist='$id_checklist_borrador'");
    $docs = [];
    while ($rowDoc = mysqli_fetch_assoc($rDocs)) { $docs[$rowDoc['t_documento']] = $rowDoc; }
    $data['documentacion'] = $docs;

    echo json_encode($data);
    exit;
}
