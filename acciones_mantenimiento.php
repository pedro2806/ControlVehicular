<?php
include 'includes/api_bootstrap.php';

$accion = $_POST["accion"] ?? '';

$id_mantenimiento = $_POST["id_mantenimiento"] ?? null;
$id_vehiculo = $_POST["id_vehiculo"] ?? null;
$fecha_registro = date("Y-m-d H:i:s");
$kilometraje = $_POST["kilometraje"] ?? null;
$gasolina = $_POST["gasolina"] ?? null;
$tipo_mantenimiento = $_POST["tipo_mantenimiento"] ?? null;
$descripcion = $_POST["descripcion"] ?? null;
$solicitante = $_POST["solicitante"] ?? null;
$VoBo_jefe = $_POST["VoBo_jefe"] ?? null;
$fecha_proxi = $_POST["fecha_proxi"] ?? null;
$km_proxi = $_POST["km_proxi"] ?? null;
$tipo_carro = $_POST["tipo_carro"] ?? null;
$id_dueno = $_POST["id_dueno"] ?? null;

$noEmpleado = $_COOKIE['noEmpleado'] ?? null;
$id_usuario = $_COOKIE['id_usuario'] ?? null;
$rol = $_COOKIE['rol'] ?? null;

$foto = $_POST["rutaImagen"] ?? null;
$placa = $_POST["placa"] ?? null;
$folioOC = $_POST["folioOC"] ?? null;
$costo = $_POST["costo"] ?? null;
$proveedor = $_POST["proveedor"] ?? null;
$contacto_proveedor = $_POST["contacto_proveedor"] ?? null;

$notas = $_POST['comentario'] ?? null;
$fecha_programada = $_POST['fecha_programada'] ?? null;
$estatus = $_POST['estatus'] ?? null;


//Registro de Mantenimiento
if ($accion == "RegistrarMantenimiento") {

    $sqlregistro = "INSERT INTO mantenimientos
                    (id_vehiculo, fecha_registro, kilometraje, gasolina, proveedor, contacto_proveedor, tipo_mantenimiento, descripcion, solicitante, VoBo_jefe,
                    fecha_proxi, km_proxi, tipo_carro, id_dueno, foto)
                    VALUES ('$id_vehiculo', '$fecha_registro', '$kilometraje', '$gasolina', '$proveedor', '$contacto_proveedor', '$tipo_mantenimiento', '$descripcion', '$solicitante', 'PENDIENTE',
                    NULL, NULL, '$tipo_carro', '$id_dueno', '$foto')";                   
    $resultregistro = $conn->query($sqlregistro);
    if ($resultregistro) {
        echo json_encode(["success" => true, "message" => "Mantenimiento registrado exitosamente."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al registrar el mantenimiento: " . $conn->error]);
    }
    exit;
}

// Info de llantas desde el último checklist del vehículo
if ($accion == "infoLlantas") {
    $sql = "SELECT cl.no_rin, cl.medidas
            FROM checklist_llantas cl
            INNER JOIN checklist c ON cl.id_checklist = c.id_checklist
            WHERE c.id_vehiculo = '$id_vehiculo'
            ORDER BY c.fecha DESC
            LIMIT 1";
    $res = $conn->query($sql);
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        echo json_encode(["found" => true, "no_rin" => $row['no_rin'], "medidas" => $row['medidas']]);
    } else {
        echo json_encode(["found" => false]);
    }
    exit;
}

// Último km y gasolina registrados para el vehículo
if ($accion == "ultimoRegistroVehiculo") {
    $sqlKm  = "SELECT km_actual FROM carga_gasolina WHERE id_vehiculo = '$id_vehiculo' ORDER BY fecha_registro DESC LIMIT 1";
    $sqlGas = "SELECT gasolina FROM mantenimientos WHERE id_vehiculo = '$id_vehiculo' ORDER BY fecha_registro DESC LIMIT 1";

    $resKm  = $conn->query($sqlKm);
    $resGas = $conn->query($sqlGas);

    $km      = ($resKm  && $resKm->num_rows  > 0) ? $resKm->fetch_assoc()['km_actual'] : '';
    $gasolina = ($resGas && $resGas->num_rows > 0) ? $resGas->fetch_assoc()['gasolina'] : '';

    echo json_encode(["km_actual" => $km, "gasolina" => $gasolina]);
    exit;
}

//Creacion de carpeta
if ($accion == "manejarCarpetasYFoto") {
    
    $rutaBase = "img_control_vehicular";
    $rutaPlaca = $rutaBase . "/" . $placa;
    $rutaMantenimiento = $rutaPlaca . "/Mantenimiento";
    
    if (!file_exists($rutaPlaca)) {
        mkdir($rutaPlaca, 0777, true);
    }
    if (!file_exists($rutaMantenimiento)) {
        mkdir($rutaMantenimiento, 0777, true);
    }

    // Manejar la subida de la imagen
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $rutaImagen = $rutaMantenimiento . "/" . $placa . "_Mantenimiento_" . date("Ymd_his") . "." . pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $rutaTemporal = $_FILES['foto']['tmp_name'];
        $rutaDestino = $rutaImagen;
        
        if (move_uploaded_file($rutaTemporal, $rutaDestino)) {
            echo json_encode([
                "success" => true,
                "rutaImagen" => $rutaImagen
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Error al mover la imagen al destino."
            ]);
        }
    } else {
        echo json_encode([
            "success" => false,
            "message" => "No se recibió ninguna imagen o hubo un error al subirla."
        ]);
    }
    exit;
}

//Consulta de Usuarios
if ($accion == "consultarUsuarios") {
    $sql = "SELECT id_usuario, nombre FROM usuarios";
    $result = $conn->query($sql);

    $usuarios = [];
    while ($row = $result->fetch_assoc()) {
        $usuarios[] = $row;
    }

    echo json_encode($usuarios);
    exit;
}

// Verificar si el usuario tiene acceso para autorizar mantenimientos
if ($accion == "verificarAccesoAutorizar") {
    $stmt = $conn->prepare("SELECT inf_adicional FROM mess_rrhh.accesos_especiales WHERE noEmpleado = ? AND sistema = 'ctrlVehicular' AND opcion = 'autorizaMantenimiento' AND estatus = 1 LIMIT 1");
    $tieneAcceso = false;
    if ($stmt) {
        $stmt->bind_param("i", $noEmpleado);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $tieneAcceso = (bool)$row;
        $stmt->close();
    }
    echo json_encode(["puedeAutorizar" => $tieneAcceso]);
    exit;
}

//Consulta de Mantenimientos
if ($accion == "consultarMantenimientos") {
    
    $infAdicionalAut = null;
    $tieneAccesoAut = false;
    $stmtAut = $conn->prepare("SELECT inf_adicional FROM mess_rrhh.accesos_especiales WHERE noEmpleado = ? AND sistema = 'ctrlVehicular' AND opcion = 'autorizaMantenimiento' AND estatus = 1 LIMIT 1");
    if ($stmtAut) {
        $stmtAut->bind_param("i", $noEmpleado);
        $stmtAut->execute();
        $rowAut = $stmtAut->get_result()->fetch_assoc();
        if ($rowAut) {
            $tieneAccesoAut = true;
            $infAdicionalAut = !empty($rowAut['inf_adicional']) && $rowAut['inf_adicional'] !== '-' ? trim($rowAut['inf_adicional']) : null;
        }
        $stmtAut->close();
    }

    if ($tieneAccesoAut && ($infAdicionalAut === null || $infAdicionalAut === 'TODAS')) {
        $sqlConsulta = "SELECT mant.id_mantenimiento, mant.id_vehiculo, mant.fecha_registro, mant.kilometraje, mant.gasolina, mant.tipo_mantenimiento,
                        mant.descripcion, mant.VoBo_jefe, inv.placa, inv.modelo, inv.marca, inv.color, us.nombre AS nombre_usuario
                        FROM mantenimientos mant
                        INNER JOIN inventario inv ON mant.id_vehiculo = inv.id_vehiculo
                        INNER JOIN usuarios us ON mant.solicitante = us.id_usuario
                        WHERE mant.VoBo_jefe = '$estatus'
                        ORDER BY mant.fecha_registro DESC";
    } elseif ($tieneAccesoAut && $infAdicionalAut) {
        $areasEsc = implode("','", array_map(fn($a) => $conn->real_escape_string($a), array_map('trim', explode(',', $infAdicionalAut))));
        $sqlConsulta = "SELECT mant.id_mantenimiento, mant.id_vehiculo, mant.fecha_registro, mant.kilometraje, mant.gasolina, mant.tipo_mantenimiento,
                        mant.descripcion, mant.VoBo_jefe, inv.placa, inv.modelo, inv.marca, inv.color, us.nombre AS nombre_usuario
                        FROM mantenimientos mant
                        INNER JOIN inventario inv ON mant.id_vehiculo = inv.id_vehiculo
                        INNER JOIN usuarios us ON mant.solicitante = us.id_usuario
                        WHERE mant.VoBo_jefe = '$estatus' AND inv.area IN ('$areasEsc')
                        ORDER BY mant.fecha_registro DESC";
    } else {

        $infAdicional = null;
        $stmtReg = $conn->prepare("SELECT inf_adicional FROM mess_rrhh.accesos_especiales WHERE noEmpleado = ? AND sistema = 'ctrlVehicular' AND opcion = 'registrarMantenimiento' AND estatus = 1 LIMIT 1");
        if ($stmtReg) {
            $stmtReg->bind_param("i", $noEmpleado);
            $stmtReg->execute();
            $rowReg = $stmtReg->get_result()->fetch_assoc();
            if ($rowReg && !empty($rowReg['inf_adicional']) && $rowReg['inf_adicional'] !== '-') {
                $infAdicional = trim($rowReg['inf_adicional']);
            }
            $stmtReg->close();
        }

        if ($infAdicional === 'TODAS') {
            $sqlConsulta = "SELECT mant.id_mantenimiento, mant.id_vehiculo, mant.fecha_registro, mant.kilometraje, mant.gasolina, mant.tipo_mantenimiento,
                            mant.descripcion, mant.VoBo_jefe, inv.placa, inv.modelo, inv.marca, inv.color, us.nombre AS nombre_usuario
                            FROM mantenimientos mant
                            INNER JOIN inventario inv ON mant.id_vehiculo = inv.id_vehiculo
                            INNER JOIN usuarios us ON mant.solicitante = us.id_usuario
                            WHERE mant.VoBo_jefe = '$estatus'
                            ORDER BY mant.fecha_registro DESC";
        } elseif ($infAdicional) {
            $areas = [];
            $deptos = [];
            foreach (array_map('trim', explode(',', $infAdicional)) as $item) {
                if (stripos($item, 'LAB:') === 0) {
                    $id = (int) substr($item, 4);
                    if ($id > 0) $deptos[] = $id;
                } else {
                    $areas[] = $conn->real_escape_string($item);
                }
            }

            $conditions = ["inv.id_usuario = '$id_usuario'"];
            if (!empty($areas)) {
                $areasEsc = implode("','", $areas);
                $conditions[] = "inv.area IN ('$areasEsc')";
            }
            if (!empty($deptos)) {
                $deptosEsc = implode(',', $deptos);
                $conditions[] = "inv.id_usuario IN (SELECT id_usuario FROM mess_rrhh.usuarios WHERE departamento IN ($deptosEsc))";
            }
            $whereExtra = implode(' OR ', $conditions);

            $sqlConsulta = "SELECT mant.id_mantenimiento, mant.id_vehiculo, mant.fecha_registro, mant.kilometraje, mant.gasolina, mant.tipo_mantenimiento,
                            mant.descripcion, mant.VoBo_jefe, inv.placa, inv.modelo, inv.marca, inv.color, us.nombre AS nombre_usuario
                            FROM mantenimientos mant
                            INNER JOIN inventario inv ON mant.id_vehiculo = inv.id_vehiculo
                            INNER JOIN usuarios us ON mant.solicitante = us.id_usuario
                            WHERE mant.VoBo_jefe = '$estatus' AND ($whereExtra)
                            ORDER BY mant.fecha_registro DESC";
        } else {
            $sqlConsulta = "SELECT mant.id_mantenimiento, mant.id_vehiculo, mant.fecha_registro, mant.kilometraje, mant.gasolina, mant.tipo_mantenimiento,
                            mant.descripcion, mant.VoBo_jefe, inv.placa, inv.modelo, inv.marca, inv.color, us.nombre AS nombre_usuario
                            FROM mantenimientos mant
                            INNER JOIN inventario inv ON mant.id_vehiculo = inv.id_vehiculo
                            INNER JOIN usuarios us ON mant.solicitante = us.id_usuario
                            WHERE mant.VoBo_jefe = '$estatus' AND inv.id_usuario = '$id_usuario'
                            ORDER BY mant.fecha_registro DESC";
        }
    }
    $result = $conn->query($sqlConsulta);

    $mantenimientos = [];
    while ($row = $result->fetch_assoc()) {
        $mantenimientos[] = $row;
    }

    echo json_encode($mantenimientos);
}

//Aprobar Mantenimiento
if ($accion == "autorizarMantenimiento") {
    $sqlAutoriza = "UPDATE mantenimientos 
            SET VoBo_jefe = 'AUTORIZADO', notas = '$notas', fecha_programada = '$fecha_programada', folio = '$folioOC', costo = '$costo'
            WHERE id_mantenimiento = $id_mantenimiento";
    $resultAutoriza = $conn->query($sqlAutoriza);
    echo json_encode(["success" => true]);
}

//Denegar Mantenimiento
if ($accion == "denegarMantenimiento") {
    $sqlDenegar = "UPDATE mantenimientos 
            SET VoBo_jefe = 'DENEGADO', notas = '$notas', fecha_programada = '$fecha_programada' 
            WHERE id_mantenimiento = '$id_mantenimiento'";
    $resultDenegar = $conn->query($sqlDenegar);

    echo json_encode(["success" => true]);
}
?>