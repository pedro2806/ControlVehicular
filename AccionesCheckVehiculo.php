<?php
include 'includes/api_bootstrap.php';

// Cuando la petición pesa más que post_max_size, PHP VACÍA $_POST y $_FILES antes de que
// este archivo corra: $opcion queda vacía, no entra a ningún bloque, el script termina
// sin imprimir nada y jQuery (dataType:'json') cae en el callback error con el mensaje
// genérico "No se pudo completar la solicitud", sin decir qué pasó. Aquí se detecta —
// cuerpo enviado pero sin datos parseados— y se responde algo accionable.
if (empty($_POST) && empty($_FILES) && intval($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    $limite = ini_get('post_max_size');
    error_log("Checklist: petición descartada por exceder post_max_size ($limite); CONTENT_LENGTH=" . $_SERVER['CONTENT_LENGTH']);
    echo json_encode([
        "error" => "Las fotos pesan más de lo que admite el servidor (límite $limite por envío). "
                 . "Guarda el avance con menos fotos a la vez o vuelve a tomarlas con menor resolución."
    ]);
    exit;
}

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

    // El JOIN a usuarios empareja con la fila de MAYOR id y no con id_usuario a secas:
    // usuarios.id_usuario NO es único (hay ids repetidos), así que un LEFT JOIN directo
    // puede DUPLICAR vehículos en la lista. Hoy no duplica con los datos actuales, pero
    // basta con que un vehículo apunte a un id_usuario repetido para que aparezca dos
    // veces en la tabla del QR.
    $joinUsuario = "LEFT JOIN usuarios u ON u.id = (SELECT MAX(u2.id) FROM usuarios u2 WHERE u2.id_usuario = i.id_usuario)";
    $campos = "i.id_vehiculo, i.id_usuario, i.usuario, i.area, i.placa, i.modelo, i.color, i.anio,
               i.foto_general, i.estatus, i.fecha_registro, i.km_mantenimiento, i.marca,
               u.nombre as asignado, '' as tipo, '' as referencia";

    if ($tieneAccesoTotal) {
        // El checklist que cuenta es el más reciente QUE TENGA DATOS, no el más reciente
        // a secas. Una cabecera sin filas de subárea marcaba el vehículo en verde como si
        // estuviera revisado: pasaba con checklists cuyas secciones se borraron y con
        // cabeceras creadas a mano. Es el mismo criterio que usa el semáforo del QR
        // (obtenerValidacionesVehiculo en acciones_qr.php).
        //
        // Se arma con un EXISTS por tabla unidos con OR, NO con un UNION: las tablas de
        // subárea no comparten colación (checklist_graficas difiere) y un UNION entre
        // ellas revienta con "Illegal mix of collations".
        $tablasConDatos = [
            'checklist_asientos', 'checklist_espejos_ventanas', 'checklist_estereos_aire',
            'checklist_faros', 'checklist_golpes_exterior', 'checklist_graficas',
            'checklist_limpiaparabrisas', 'checklist_llantas', 'checklist_placas',
            'checklist_puertas_llave'
        ];
        $tieneDatos = implode(' OR ', array_map(function ($t) {
            return "EXISTS (SELECT 1 FROM $t s WHERE s.id_checklist = ch.id_checklist)";
        }, $tablasConDatos));

        $sql = "SELECT $campos, c.estatus as estatusChecklist
                FROM inventario i
                $joinUsuario
                LEFT JOIN checklist c ON c.id_checklist = (
                    SELECT ch.id_checklist FROM checklist ch
                    WHERE ch.id_vehiculo = i.id_vehiculo AND ($tieneDatos)
                    ORDER BY ch.fecha DESC, ch.id_checklist DESC
                    LIMIT 1
                )
                WHERE i.estatus = 'Activo'";
        $stmtLista = $conn->prepare($sql);
    } else {
        // Sentencia preparada: antes el id_usuario de la cookie se concatenaba entre
        // comillas directo al SQL. Esa cookie la escribe el cliente, así que era una
        // inyección — la misma que ya se cerró en acciones_siniestro.php.
        $sql = "SELECT $campos, '' as estatusChecklist
                FROM inventario i
                $joinUsuario
                WHERE i.id_usuario = ? OR i.id_us_asignado = ?";
        $stmtLista = $conn->prepare($sql);
        if ($stmtLista) {
            $idUsr = intval($id_usuario);
            $stmtLista->bind_param("ii", $idUsr, $idUsr);
        }
    }

    if (!$stmtLista) { die(json_encode(array("error" => $conn->error))); }
    $stmtLista->execute();
    $res2 = $stmtLista->get_result();

    $registros = array();
    while ($row2 = $res2->fetch_assoc()) {
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

// La placa arma la carpeta y el nombre de archivo de TODAS las fotos del checklist
// (getFotoInfo). Cuando no llegaba, las rutas quedaban como
// "img_control_vehicular//checklist/faros/_checklist_Faros_20260819_110245.jpeg": sin
// carpeta de vehículo y sin prefijo, así que dos fotos del mismo apartado tomadas en el
// mismo segundo se pisaban entre vehículos distintos. El cliente ya la manda en un
// hidden, pero se resuelve también aquí desde la BD: es el dato de la tabla y no depende
// de que la vista lo mande bien. Mismo criterio que acciones_kilometraje.php.
if (trim($placa) === '' && intval($id_coche) > 0) {
    $stmtPlacaChk = $conn->prepare("SELECT placa FROM inventario WHERE id_vehiculo = ? LIMIT 1");
    if ($stmtPlacaChk) {
        $idVehChk = intval($id_coche);
        $stmtPlacaChk->bind_param("i", $idVehChk);
        $stmtPlacaChk->execute();
        $rowPlacaChk = $stmtPlacaChk->get_result()->fetch_assoc();
        $stmtPlacaChk->close();
        if ($rowPlacaChk && trim((string) $rowPlacaChk['placa']) !== '') {
            $placa = $rowPlacaChk['placa'];
        }
    }
    if (trim($placa) === '') {
        error_log("Checklist: no se pudo resolver la placa del vehículo $id_coche; las fotos se guardarían sin carpeta.");
    }
}

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

/**
 * Fotos que el navegador intentó subir y PHP rechazó, para devolverlas en la respuesta.
 *
 * Antes esto no existía y era el fallo más caro del módulo: una foto de celular de 3-8 MB
 * excede upload_max_filesize (2M), PHP la descarta, getFotoInfo() lo interpretaba como
 * "esta petición no traía foto", la columna se conservaba intacta y el endpoint respondía
 * success. El usuario veía "guardado" y la BD nunca recibía la ruta.
 */
$FOTOS_FALLIDAS = [];

/** Traduce el código de $_FILES a algo que el usuario pueda entender. */
function motivoErrorSubida($codigo) {
    switch ($codigo) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'la foto pesa más de lo que admite el servidor';
        case UPLOAD_ERR_PARTIAL:
            return 'la subida se interrumpió a media carga';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'el servidor no tiene carpeta temporal';
        case UPLOAD_ERR_CANT_WRITE:
            return 'el servidor no pudo escribir el archivo';
        case UPLOAD_ERR_EXTENSION:
            return 'una extensión de PHP bloqueó la subida';
        default:
            return 'error desconocido de subida (código ' . intval($codigo) . ')';
    }
}

function getFotoInfo($fileKey, $placa, $tipo, $subdir) {
    global $FOTOS_FALLIDAS;

    $archivo = $_FILES[$fileKey] ?? null;
    if ($archivo && $archivo['error'] == UPLOAD_ERR_OK) {
        $ext = pathinfo($archivo['name'], PATHINFO_EXTENSION);
        $nombre = "{$placa}_{$tipo}_" . date("Ymd_His") . ".{$ext}";
        $dir = "img_control_vehicular/{$placa}/checklist/{$subdir}";
        return ['ruta' => "{$dir}/{$nombre}", 'dir' => $dir, 'nombre' => $nombre, 'tmp' => $archivo['tmp_name'], 'subir' => true];
    }

    // El navegador mandó archivo pero PHP lo rechazó. NO es lo mismo que "no venía foto":
    // hay que decirlo, o el usuario se queda creyendo que la guardó.
    if ($archivo && $archivo['error'] != UPLOAD_ERR_NO_FILE) {
        $motivo = motivoErrorSubida($archivo['error']);
        $FOTOS_FALLIDAS[$fileKey] = $motivo;
        error_log("Checklist: falló la subida de $fileKey ($tipo) - $motivo");
    }

    $rutaExistente = !empty($_POST["ruta_{$fileKey}"]) ? $_POST["ruta_{$fileKey}"] : null;

    // Centinela que manda quitarFoto() en checkVehiculo.php. Distingue "el usuario quitó
    // la foto" de "esta petición simplemente no la incluye": sin esto, al conservar la
    // ruta en el segundo caso tampoco se podría borrar en el primero.
    if ($rutaExistente === '__BORRAR__') {
        return ['ruta' => '', 'dir' => null, 'nombre' => null, 'tmp' => null, 'subir' => false, 'limpiar' => true];
    }

    return ['ruta' => $rutaExistente ?? '', 'dir' => null, 'nombre' => null, 'tmp' => null, 'subir' => false];
}

/**
 * ¿Alcanza la memoria para abrir esta imagen con GD?
 *
 * GD descomprime a mapa de bits: ancho × alto × 4 bytes, más una copia para el
 * redimensionado. Se pide el doble de ese cálculo como margen. Superar memory_limit es un
 * error fatal, no una excepción: no se puede capturar, solo evitar.
 */
function hayMemoriaParaImagen($info) {
    $limite = ini_get('memory_limit');
    if ($limite === false || $limite === '' || (int) $limite === -1) return true;   // sin límite

    $mult = ['k' => 1024, 'm' => 1048576, 'g' => 1073741824];
    $u = strtolower(substr(trim($limite), -1));
    $bytesLimite = isset($mult[$u]) ? ((int) $limite) * $mult[$u] : (int) $limite;

    $necesarios = $info[0] * $info[1] * 4 * 2;
    return ($bytesLimite - memory_get_usage(true)) > $necesarios;
}

/**
 * Deja la foto en disco. Devuelve true solo si el archivo quedó escrito.
 *
 * Antes no devolvía nada y quien la llamaba ignoraba el resultado: si mkdir o la escritura
 * fallaban, la BD se quedaba con una ruta que apuntaba a un archivo inexistente.
 */
function subirImagenAsientos($rutaChecklist, $rutaImagen, $tempFilePath) {
    if (!is_dir($rutaChecklist) && !mkdir($rutaChecklist, 0775, true) && !is_dir($rutaChecklist)) {
        error_log("Checklist: no se pudo crear la carpeta $rutaChecklist");
        return false;
    }
    $destino = $rutaChecklist . "/" . basename($rutaImagen);
    if (!reducirPesoImagen($tempFilePath, $destino)) {
        error_log("Checklist: no se pudo escribir la foto en $destino");
        return false;
    }
    return true;
}

function reducirPesoImagen($origen, $destino, $calidad = 75) {
    $info = @getimagesize($origen);
    // Sin datos de imagen (HEIC de iPhone renombrado a .jpg, archivo corrupto) se mueve
    // tal cual: vale más guardarlo sin comprimir que perderlo.
    if ($info === false) { return move_uploaded_file($origen, $destino); }

    // Sin GD no se puede recomprimir: se guarda el archivo tal cual. Antes se llamaba a
    // imagecreatefromjpeg() a ciegas y, si la extensión no está cargada, eso es un error
    // fatal —"Call to undefined function"— que tumba la petición entera y el navegador
    // solo ve un HTTP 500 sin cuerpo.
    if (!function_exists('imagecreatefromjpeg') || !function_exists('imagejpeg')) {
        error_log('Checklist: GD no disponible, la foto se guarda sin recomprimir.');
        return move_uploaded_file($origen, $destino);
    }

    // Recomprimir carga la imagen entera en memoria (~4 bytes por píxel): una foto de 12 MP
    // pide unos 50 MB y con varias en la misma petición se agota memory_limit, que también
    // es fatal. Si no hay margen suficiente, se guarda sin tocar.
    if (!hayMemoriaParaImagen($info)) {
        error_log('Checklist: memoria insuficiente para recomprimir (' . $info[0] . 'x' . $info[1] . '), se guarda tal cual.');
        return move_uploaded_file($origen, $destino);
    }

    $mime = $info['mime'];
    // imagecreatefrom* devuelve false con un archivo que no corresponde a su mime, y en
    // PHP 8 pasarle ese false a imagejpeg lanza TypeError: petición muerta a media
    // iteración de secciones, con lo ya procesado guardado y el resto no. De ahí salían
    // checklists "completos" a los que les faltaban apartados.
    if ($mime == 'image/jpeg') {
        $image = @imagecreatefromjpeg($origen);
        if (!$image) { return move_uploaded_file($origen, $destino); }
        $ok = imagejpeg($image, $destino, $calidad);
        imagedestroy($image);
        return $ok;
    }
    if ($mime == 'image/png') {
        $image = @imagecreatefrompng($origen);
        if (!$image) { return move_uploaded_file($origen, $destino); }
        $ok = imagepng($image, $destino, 7);
        imagedestroy($image);
        return $ok;
    }
    return move_uploaded_file($origen, $destino);
}

function upsertChecklistSeccion($conn, $tabla, $id_checklist, $campos, $fotoKey, $placa, $fotoTipo, $subdir) {
    global $FOTOS_FALLIDAS;

    $foto = getFotoInfo($fotoKey, $placa, $fotoTipo, $subdir);

    // El archivo se escribe ANTES que la fila. Al revés (como estaba) la BD podía quedar
    // con la ruta de una foto que nunca llegó a disco, y nadie se enteraba porque el
    // resultado de la subida se descartaba.
    if ($foto['subir'] && !subirImagenAsientos($foto['dir'], $foto['nombre'], $foto['tmp'])) {
        $FOTOS_FALLIDAS[$fotoKey] = 'no se pudo guardar el archivo en el servidor';
        $foto['subir'] = false;
        $foto['ruta']  = '';   // deja $conservarFoto en true: no se pisa lo ya guardado
    }

    $fotoSql = $foto['ruta'] !== null ? "'" . mysqli_real_escape_string($conn, $foto['ruta']) . "'" : "NULL";

    // Si en esta petición no viene ni archivo nuevo ni ruta previa, la columna foto NO
    // se toca: se conserva lo que ya estuviera guardado. Antes se escribía cadena vacía,
    // así que cualquier guardado parcial BORRABA la ruta de una foto ya subida. Con el
    // guardado automático al cambiar de apartado eso pasaría en cada paso.
    $conservarFoto = !$foto['subir'] && empty($foto['limpiar']) && ($foto['ruta'] === '' || $foto['ruta'] === null);

    // Los valores se escapan: un apóstrofe en Observaciones o en "No. Rin" (rin 17')
    // rompía el UPDATE, la función devolvía false y el foreach de secciones abortaba con
    // die(), así que los apartados siguientes se quedaban sin guardar. Los nombres de
    // columna vienen del código, no del POST, por eso van tal cual.
    $idChk = intval($id_checklist);
    $esc = function ($v) use ($conn) { return mysqli_real_escape_string($conn, (string) $v); };

    $r = mysqli_query($conn, "SELECT id_checklist FROM $tabla WHERE id_checklist='$idChk'");
    if ($r && mysqli_num_rows($r) > 0) {
        $sets = [];
        foreach ($campos as $col => $val) { $sets[] = "$col='" . $esc($val) . "'"; }
        if (!$conservarFoto) $sets[] = "foto=$fotoSql";
        $sql = "UPDATE $tabla SET " . implode(', ', $sets) . " WHERE id_checklist='$idChk'";
    } else {
        $cols = array_keys($campos);
        $vals = array_map($esc, array_values($campos));
        $cols[] = 'foto';
        $colStr = 'id_checklist, ' . implode(', ', $cols);
        $valStr = "'$idChk', '" . implode("', '", $vals) . "', $fotoSql";
        $sql = "INSERT INTO $tabla ($colStr) VALUES ($valStr)";
    }
    if (mysqli_query($conn, $sql)) return true;

    error_log("Checklist: falló el guardado de $tabla (checklist $idChk) - " . mysqli_error($conn));
    return false;
}

/**
 * Literal SQL para una columna de checklist_documentacion.
 *
 * Esa tabla tiene dos columnas que NO son de texto, a diferencia de las secciones físicas
 * (donde si_no es varchar): `si_no` es int NOT NULL y `vencimiento` es date. Mandarles
 * cadena vacía —lo que devuelve getPostOrSR() cuando el usuario no respondió— falla en
 * cualquier MySQL con STRICT_TRANS_TABLES, que es lo normal en producción:
 * "Incorrect integer value: '' for column 'si_no'".
 *
 * En local no se veía porque ahí sql_mode viene vacío y MySQL convertía '' a 0 en
 * silencio (de ahí salieron los ceros que ya hay en la tabla). Y desde PHP 8.1 mysqli
 * lanza excepción ante un error de SQL en vez de devolver false, así que el fallo llegaba
 * al navegador como HTTP 500 sin cuerpo: el famoso "No se pudo completar la solicitud".
 */
function valorSqlDocumentacion($col, $val, $esc) {
    if ($col === 'si_no') {
        // La columna es NOT NULL, así que "sin responder" no se puede guardar como NULL.
        // Se usa 0, que es exactamente lo que MySQL venía escribiendo al convertir ''.
        return ($val === '' || $val === null) ? '0' : (string) intval($val);
    }
    if ($col === 'vencimiento') {
        // Fecha vacía = sin capturar. NULL, nunca '' ni '0000-00-00'.
        return ($val === '' || $val === null || $val === '0000-00-00') ? 'NULL' : "'" . $esc($val) . "'";
    }
    return "'" . $esc($val) . "'";
}

function upsertChecklistDocumentacion($conn, $id_checklist, $t_documento, $campos, $fotoKey, $placa, $fotoTipo, $subdir) {
    global $FOTOS_FALLIDAS;

    $foto = getFotoInfo($fotoKey, $placa, $fotoTipo, $subdir);

    // Igual que en upsertChecklistSeccion: primero el archivo, luego la fila.
    if ($foto['subir'] && !subirImagenAsientos($foto['dir'], $foto['nombre'], $foto['tmp'])) {
        $FOTOS_FALLIDAS[$fotoKey] = 'no se pudo guardar el archivo en el servidor';
        $foto['subir'] = false;
        $foto['ruta']  = '';
    }

    $fotoSql = $foto['ruta'] !== null ? "'" . mysqli_real_escape_string($conn, $foto['ruta']) . "'" : "NULL";

    // Mismo criterio que upsertChecklistSeccion: sin archivo nuevo ni ruta previa, la
    // columna foto se deja como está en vez de sobrescribirla con cadena vacía.
    $conservarFoto = !$foto['subir'] && empty($foto['limpiar']) && ($foto['ruta'] === '' || $foto['ruta'] === null);

    // Mismo escapado que upsertChecklistSeccion: sin él, un apóstrofe en cualquier campo
    // libre tiraba el guardado de este documento y de todos los siguientes.
    $idChk = intval($id_checklist);
    $esc = function ($v) use ($conn) { return mysqli_real_escape_string($conn, (string) $v); };
    $tDoc = $esc($t_documento);

    $r = mysqli_query($conn, "SELECT id_checklist FROM checklist_documentacion WHERE id_checklist='$idChk' AND t_documento='$tDoc'");
    if ($r && mysqli_num_rows($r) > 0) {
        $sets = [];
        foreach ($campos as $col => $val) { $sets[] = "$col=" . valorSqlDocumentacion($col, $val, $esc); }
        if (!$conservarFoto) $sets[] = "foto=$fotoSql";
        $sql = "UPDATE checklist_documentacion SET " . implode(', ', $sets) . " WHERE id_checklist='$idChk' AND t_documento='$tDoc'";
    } else {
        $allCols = ['id_checklist', 't_documento'];
        $allVals = ["'$idChk'", "'$tDoc'"];
        foreach ($campos as $col => $val) { $allCols[] = $col; $allVals[] = valorSqlDocumentacion($col, $val, $esc); }
        $allCols[] = 'foto';
        $allVals[] = $fotoSql;
        if (!array_key_exists('entregado', $campos))  { $allCols[] = 'entregado';  $allVals[] = "'S/R'"; }
        if (!array_key_exists('vencimiento', $campos)){ $allCols[] = 'vencimiento'; $allVals[] = "NULL"; }
        if (!array_key_exists('no_tarjeta', $campos)) { $allCols[] = 'no_tarjeta'; $allVals[] = "'S/R'"; }
        $sql = "INSERT INTO checklist_documentacion (" . implode(', ', $allCols) . ") VALUES (" . implode(', ', $allVals) . ")";
    }
    if (mysqli_query($conn, $sql)) return true;

    error_log("Checklist: falló el guardado de documentación '$t_documento' (checklist $idChk) - " . mysqli_error($conn));
    return false;
}

// ======== GUARDAR CHECKLIST ========

$TABLAS_CHECKLIST = [
    'checklist_asientos', 'checklist_espejos_ventanas', 'checklist_estereos_aire',
    'checklist_faros', 'checklist_golpes_exterior', 'checklist_graficas',
    'checklist_limpiaparabrisas', 'checklist_limpieza', 'checklist_llantas',
    'checklist_placas', 'checklist_puertas_llave', 'checklist_documentacion'
];

/**
 * ¿Este checklist tiene alguna foto guardada?
 *
 * Se usa antes de borrar borradores huérfanos. Dos guardados en paralelo llegaron a crear
 * dos checklists para el mismo vehículo y las fotos quedaron repartidas entre ambos; al
 * completar, el borrado de huérfanos se llevaba el que tenía fotos y esas rutas se perdían
 * sin vuelta atrás. Ante la duda se conserva: un borrador de más es recuperable, una foto
 * borrada no.
 */
function checklistTieneFotos($conn, $tablas, $id_checklist) {
    $id = intval($id_checklist);
    foreach ($tablas as $t) {
        $res = mysqli_query($conn, "SELECT 1 FROM $t WHERE id_checklist='$id' AND foto IS NOT NULL AND foto <> '' LIMIT 1");
        if ($res && mysqli_num_rows($res) > 0) return true;
    }
    return false;
}

if ($opcion == 'guardarCheckIn') {
    // A qué checklist escribir. Antes se buscaba siempre "el último borrador del
    // vehículo", así que empezar un checklist nuevo SOBRESCRIBÍA el anterior: la opción
    // "No, empezar de nuevo" del cliente no creaba nada, solo dejaba el formulario en
    // blanco y luego el guardado pisaba el borrador viejo.
    //
    //   id_checklist  -> el cliente ya sabe en cuál trabaja (se lo devolvimos al guardar)
    //   nuevo=1       -> el usuario eligió empezar de cero: se fuerza uno nuevo
    //   ninguno       -> comportamiento anterior: continuar el último borrador
    $id_checklist_post = intval($_POST['id_checklist'] ?? 0);
    $forzarNuevo       = !empty($_POST['nuevo']);
    $id_checklist      = 0;

    if ($id_checklist_post > 0) {
        $chk = mysqli_query($conn, "SELECT id_checklist FROM checklist WHERE id_checklist='$id_checklist_post' AND id_vehiculo='$id_coche' LIMIT 1");
        if ($chk && mysqli_num_rows($chk) > 0) $id_checklist = $id_checklist_post;
    }

    $resBorrador = null;
    if (!$id_checklist && !$forzarNuevo) {
        $resBorrador = mysqli_query($conn, "SELECT id_checklist FROM checklist WHERE id_vehiculo='$id_coche' AND estatus='borrador' ORDER BY fecha DESC LIMIT 1");
    }

    if ($id_checklist) {
        if (!mysqli_query($conn, "UPDATE checklist SET fecha=NOW(), motivo='$motivo', estatus='$estatus' WHERE id_checklist='$id_checklist'")) {
            die(json_encode(array("error" => "Failed to update checklist: " . mysqli_error($conn))));
        }
        if ($estatus === 'completo') {
            $resHuerfanos = mysqli_query($conn, "SELECT id_checklist FROM checklist WHERE id_vehiculo='$id_coche' AND estatus='borrador' AND id_checklist <> '$id_checklist'");
            while ($rowH = mysqli_fetch_assoc($resHuerfanos)) {
                $hId = $rowH['id_checklist'];
                // Un huérfano con fotos casi siempre es la otra mitad de este mismo
                // checklist (dos guardados simultáneos crearon dos filas). Borrarlo
                // destruye rutas de imágenes reales, así que se deja y se registra.
                if (checklistTieneFotos($conn, $TABLAS_CHECKLIST, $hId)) {
                    error_log("Checklist: no se borra el borrador huérfano $hId del vehículo $id_coche porque tiene fotos.");
                    continue;
                }
                foreach ($TABLAS_CHECKLIST as $t) { mysqli_query($conn, "DELETE FROM $t WHERE id_checklist='$hId'"); }
                mysqli_query($conn, "DELETE FROM checklist WHERE id_checklist='$hId'");
            }
        }
    } elseif ($resBorrador && mysqli_num_rows($resBorrador) > 0) {
        $rowBorrador = mysqli_fetch_assoc($resBorrador);
        $id_checklist = $rowBorrador['id_checklist'];
        if (!mysqli_query($conn, "UPDATE checklist SET fecha=NOW(), motivo='$motivo', estatus='$estatus' WHERE id_checklist='$id_checklist'")) {
            die(json_encode(array("error" => "Failed to update checklist: " . mysqli_error($conn))));
        }
        if ($estatus === 'completo') {
            $resHuerfanos = mysqli_query($conn, "SELECT id_checklist FROM checklist WHERE id_vehiculo='$id_coche' AND estatus='borrador'");
            while ($rowH = mysqli_fetch_assoc($resHuerfanos)) {
                $hId = $rowH['id_checklist'];
                // Un huérfano con fotos casi siempre es la otra mitad de este mismo
                // checklist (dos guardados simultáneos crearon dos filas). Borrarlo
                // destruye rutas de imágenes reales, así que se deja y se registra.
                if (checklistTieneFotos($conn, $TABLAS_CHECKLIST, $hId)) {
                    error_log("Checklist: no se borra el borrador huérfano $hId del vehículo $id_coche porque tiene fotos.");
                    continue;
                }
                foreach ($TABLAS_CHECKLIST as $t) { mysqli_query($conn, "DELETE FROM $t WHERE id_checklist='$hId'"); }
                mysqli_query($conn, "DELETE FROM checklist WHERE id_checklist='$hId'");
            }
        }
    } else {
        // id_vehiculo, id_usuario e id_revisor son columnas int: van como número, no
        // entrecomilladas. Si la cookie de usuario viniera vacía se mandaba '' a un int y,
        // con STRICT_TRANS_TABLES, eso es un error de MySQL (y desde PHP 8.1 una excepción
        // que tumba la petición). id_usuario admite NULL; id_vehiculo no, así que sin
        // vehículo no tiene caso intentar el INSERT.
        $idVehInsert = intval($id_coche);
        if ($idVehInsert <= 0) {
            die(json_encode(["error" => "No se pudo identificar el vehículo del checklist."]));
        }
        $idUsuarioInsert = ($id_usuario === null || $id_usuario === '') ? 'NULL' : intval($id_usuario);
        $idRevisorInsert = ($id_revisor === null || $id_revisor === '') ? 'NULL' : intval($id_revisor);
        $motivoEsc  = mysqli_real_escape_string($conn, (string) $motivo);
        $estatusEsc = mysqli_real_escape_string($conn, (string) $estatus);

        $sql = "INSERT INTO checklist (id_vehiculo, fecha, id_usuario, id_revisor, motivo, estatus)
                VALUES ($idVehInsert, NOW(), $idUsuarioInsert, $idRevisorInsert, '$motivoEsc', '$estatusEsc')";
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

    // Se devuelve el id para que el cliente sepa en qué checklist está trabajando y lo
    // reenvíe en los siguientes guardados. Sin esto, cada guardado volvía a adivinar
    // "el último borrador del vehículo" y podía escribir en otro distinto.
    //
    // fotos_fallidas viaja aunque el guardado haya salido bien: los datos SÍ se guardaron,
    // pero alguna foto no. Callarlo es lo que producía "en la pantalla estaba completo y
    // en la BD no estaba la ruta de la imagen".
    echo json_encode(array(
        "success"        => "Checklist and related data inserted successfully.",
        "id_checklist"   => $id_checklist,
        "fotos_fallidas" => $FOTOS_FALLIDAS
    ));
}

// ======== CARGAR BORRADOR ========

if ($opcion == 'cargarBorrador') {
    $id_coche_borrador = $_POST['id_coche'] ?? null;
    $resBorrador = mysqli_query($conn, "SELECT id_checklist, motivo FROM checklist WHERE id_vehiculo='$id_coche_borrador' AND estatus='borrador' ORDER BY fecha DESC LIMIT 1");

    if (!$resBorrador || mysqli_num_rows($resBorrador) == 0) {
        // No hay borrador, pero puede existir un checklist YA COMPLETO. Antes se
        // devolvía 'found:false' a secas y el cliente abría un formulario en blanco
        // sin avisar: quien acababa de terminar un checklist y volvía a entrar creía
        // que se había perdido. Se informa para que el usuario decida.
        $resp = ['found' => false];
        $stmtComp = $conn->prepare("SELECT id_checklist, fecha FROM checklist
                                    WHERE id_vehiculo = ? AND estatus = 'completo'
                                    ORDER BY fecha DESC, id_checklist DESC LIMIT 1");
        if ($stmtComp) {
            $idCocheComp = intval($id_coche_borrador);
            $stmtComp->bind_param("i", $idCocheComp);
            $stmtComp->execute();
            if ($rowComp = $stmtComp->get_result()->fetch_assoc()) {
                $resp['completo'] = [
                    'id_checklist' => intval($rowComp['id_checklist']),
                    'fecha'        => $rowComp['fecha']
                ];
            }
            $stmtComp->close();
        }
        echo json_encode($resp);
        exit;
    }

    $rowMain = mysqli_fetch_assoc($resBorrador);
    $id_checklist_borrador = $rowMain['id_checklist'];
    // id_checklist va en la respuesta para que el cliente sepa cuál está continuando y
    // lo reenvíe al guardar, en vez de dejar que el servidor lo adivine cada vez.
    $data = ['found' => true, 'id_checklist' => $id_checklist_borrador, 'motivo' => $rowMain['motivo']];

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
