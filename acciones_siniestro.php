<?php
include 'includes/api_bootstrap.php';

$accion = $_POST["accion"] ?? '';

$id_vehiculo = $_POST["id_vehiculo"] ?? null;
$fecha = $_POST["fecha"] ?? null;
$hora = $_POST["hora"] ?? null;
$kilometraje = $_POST["kilometraje"] ?? null;
$gasolina = $_POST["gasolina"] ?? null;
$origen = $_POST["origen"] ?? null;
$destino = $_POST["destino"] ?? null;
$lugar = $_POST["lugar"] ?? null;
$empresa = $_POST["empresa"] ?? null;
$servicio = $_POST["servicio"] ?? null;
$coordenadas = $_POST["coordenadas"] ?? null;
$descripcion = $_POST["descripcion"] ?? null;
$partes_dañadas = $_POST["daños"] ?? null;
$ubicacion_vehiculo = $_POST["ubicacion"] ?? null;
$contacto = $_POST["contacto"] ?? null;

$tipo_carro = $_POST["tipo_carro"] ?? null;
$id_dueno = $_POST["id_dueno"] ?? null;
$fecha_registro = date("Y-m-d H:i:s");
$placa = $_POST["placa"] ?? null;
$marca = $_POST["marca"] ?? null;
$modelo = $_POST["modelo"] ?? null;
$color = $_POST["color"] ?? null;
$anio = $_POST["anio"] ?? null;
$noEmpleado = $_COOKIE['noEmpleado'] ?? null;
// intval y no el valor crudo de la cookie: este archivo lo concatena directo en ~8
// consultas. Con la cookie vacía quedaba "WHERE inv.id_usuario = " y MySQL tiraba un
// fatal en HTML; el AJAX no podía parsearlo y en pantalla solo salía "Hubo un problema
// al cargar los datos". Además la cookie la escribe el cliente, así que sin intval era
// inyección SQL directa.
$id_usuario = intval($_COOKIE['id_usuario'] ?? 0);
$rutasImagenes = $_POST["rutasImagenes"] ?? null;

$id_formato = $_POST["id_formato"] ?? null;
$formato = (isset($_POST["formato"]) && $_POST["formato"] !== null && $_POST["formato"] !== '') ? $_POST["formato"] : null;
/*----------------------------------------------------------------------------*/
include_once 'includes/subir_imagenes.php';

//Registro de Siniestro
if ($accion == "registroSiniestro") {
    $sqlregistro = "INSERT INTO siniestros
                                (id_vehiculo, fecha_registro, fecha, hora, tipo_carro, id_dueno, lugar,
                                coordenadas, descripcion, partes_dañadas, ubicacion_vehiculo)
                    VALUES ('$id_vehiculo', '$fecha_registro', '$fecha', '$hora', '$tipo_carro', '$id_usuario', '$lugar',
                            '$coordenadas', '$descripcion', '$partes_dañadas', '$ubicacion_vehiculo')";
    if ($conn->query($sqlregistro) === TRUE) {

            $consultaUltimaActividad = "SELECT id_siniestro FROM siniestros WHERE id_dueno = '" . $_COOKIE['id_usuario'] . "' ORDER BY fecha_registro DESC, id_siniestro DESC LIMIT 1";
            $resultUltimaActividad = $conn->query($consultaUltimaActividad);
            $idUltimaActividad = null;

            if ($resultUltimaActividad && $resultUltimaActividad->num_rows > 0) {
                $rowUltimaActividad = $resultUltimaActividad->fetch_assoc();
                $idUltimaActividad = $rowUltimaActividad['id_siniestro'];
            }
            // Procesar imágenes solo si se enviaron archivos
            if (isset($_FILES['foto']) && !empty($_FILES['foto']['name'][0])) {
                subirImagenesCheckin($_FILES['foto'], $idUltimaActividad, $id_vehiculo, $conn, $placa, 'siniestro');
            }




        echo json_encode(["success" => true, "id_formato" => $id_formato, "message" => "Siniestro registrado exitosamente."]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al registrar el siniestro."]);
    }
    exit;
}

// Subir imágenes
if ($accion == "subirImagenes") {
    $rutaBase = "img_control_vehicular/$placa/Siniestro";
    if (!file_exists($rutaBase)) {
        mkdir($rutaBase, 0777, true);
        error_log("Carpeta creada: $rutaBase");
    }

    $contador = 1; 
    foreach ($_FILES['fotos']['tmp_name'] as $key => $tmp_name) {
        $file_name = $_FILES['fotos']['name'][$key];
        $file_tmp = $_FILES['fotos']['tmp_name'][$key];
        $extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $nuevoNombre = "{$placa}_Siniestro_" . date("Ymd_His") . "_{$contador}.$extension";
        $rutaDestino = $rutaBase ."/". $nuevoNombre; 

        // Mover el archivo a la ruta destino
        if (move_uploaded_file($file_tmp, $rutaDestino)) {
            error_log("Archivo movido a: $rutaDestino");

            // Insertar la ruta en la tabla "fotos"
            $sql = "INSERT INTO fotos (formato, id_formato, id_vehiculo, imagen) 
                    VALUES ('Siniestro', '$id_formato', '$id_vehiculo', '$rutaDestino')";
            if ($conn->query($sql) === TRUE) {
                error_log("Imagen registrada en la base de datos: $rutaDestino");
            } else {
                error_log("Error al registrar la imagen en la base de datos: " . $conn->error);
            }
        } else {
            error_log("Error al mover el archivo: $nuevoNombre");
        }

        $contador++; // Incrementar el contador para la siguiente imagen
    }

    echo json_encode(["success" => true, "message" => "Imágenes subidas exitosamente."]);
    exit;
}

// Consulta para obtener los vehiculos asignados al usuario
if ($accion == "consultarInventario") {

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
        $sqlConsultaVehiculos = "SELECT id_vehiculo, placa, modelo, marca, color, anio, usuario FROM inventario ORDER BY placa";
    } else {
        $areas = [];
        $deptos = [];
        if ($infAdicional) {
            foreach (array_map('trim', explode(',', $infAdicional)) as $item) {
                if (stripos($item, 'LAB:') === 0) {
                    $id = (int) substr($item, 4);
                    if ($id > 0) $deptos[] = $id;
                } else {
                    $areas[] = $conn->real_escape_string($item);
                }
            }
        }

        $sqlConsultaVehiculos = "SELECT inv.id_vehiculo, inv.placa, inv.modelo, inv.marca, inv.color, inv.anio, inv.usuario
                                FROM inventario inv
                                WHERE inv.id_us_asignado = $id_usuario OR inv.id_usuario = $id_usuario
                                UNION
                                SELECT inv.id_vehiculo, inv.placa, inv.modelo, inv.marca, inv.color, inv.anio, inv.usuario
                                FROM prestamos p
                                INNER JOIN inventario inv ON p.id_vehiculo = inv.id_vehiculo
                                WHERE p.id_usuario = $id_usuario AND p.estatus= 'AUTORIZADO'";

        if (!empty($areas)) {
            $areasEsc = implode("','", $areas);
            $sqlConsultaVehiculos .= " UNION
                                SELECT inv.id_vehiculo, inv.placa, inv.modelo, inv.marca, inv.color, inv.anio, inv.usuario
                                FROM inventario inv
                                WHERE inv.area IN ('$areasEsc')";
        }

        if (!empty($deptos)) {
            $deptosEsc = implode(',', $deptos);
            $sqlConsultaVehiculos .= " UNION
                                SELECT inv.id_vehiculo, inv.placa, inv.modelo, inv.marca, inv.color, inv.anio, inv.usuario
                                FROM inventario inv
                                INNER JOIN usuarios us ON inv.id_usuario = us.id_usuario
                                WHERE us.departamento IN ($deptosEsc)";
        }
    }

    $result = $conn->query($sqlConsultaVehiculos);

    $vehiculos = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $vehiculos[] = $row;
        }
    }
    echo json_encode($vehiculos);
}

// Consulta para obtener los vehiculos en general
if ($accion == "consultarInventarioGeneral") {
    // Sin sesión no se puede resolver qué vehículos le tocan al usuario. Se responde un
    // JSON con motivo en vez de dejar que reviente la consulta: así el front puede decir
    // qué pasó en lugar de un "error" pelón.
    if ($id_usuario <= 0) {
        echo json_encode(['error' => 'Tu sesión expiró. Vuelve a iniciar sesión para ver los vehículos.']);
        exit;
    }

    $rol = $_COOKIE['rol'] ?? null;
    // Default para cualquier rol no contemplado (o cookie vacía): antes se quedaba sin
    // definir $sqlConsultaVehiculosG y el archivo moría en el $conn->query().
    $sqlConsultaVehiculosG = "SELECT inv.id_vehiculo, inv.placa, inv.modelo, inv.marca, inv.color, inv.anio, inv.usuario, inv.id_usuario, 'AREA' as tipo
                            FROM inventario inv
                            WHERE inv.id_usuario = $id_usuario OR inv.id_us_asignado = $id_usuario
                            ORDER BY inv.usuario";
    if ($rol == '3' || $rol == '4'  || $rol == '2') { // 3: Gerente, 4: Administrador
        $sqlConsultaVehiculosG ="SELECT inv.id_vehiculo, inv.placa, inv.modelo, inv.marca, inv.color, inv.anio, inv.usuario, inv.id_usuario, 'AREA' as tipo
                            FROM inventario inv
                            WHERE id_usuario = $id_usuario  OR inv.id_usuario = $id_usuario
                            UNION
                            SELECT inv.id_vehiculo, inv.placa, inv.modelo, inv.marca, inv.color, inv.anio, inv.usuario, inv.id_usuario, 'EXTERNO' as tipo
                            FROM inventario inv
                            WHERE inv.id_usuario != $id_usuario";
    } 
    if ($rol == '1') { 
        $sqlConsultaVehiculosG ="(SELECT inv.id_vehiculo, inv.placa, inv.modelo, inv.marca, inv.color, inv.anio, inv.usuario, inv.id_usuario, 'AREA' as tipo
                            FROM inventario inv
                            INNER JOIN usuarios u ON $id_usuario = u.id_usuario
                            WHERE inv.id_usuario = $id_usuario
                            OR inv.id_usuario IN (SELECT id_usuario FROM usuarios WHERE jefe = u.jefe UNION ALL SELECT id_usuario FROM usuarios WHERE noEmpleado =  u.jefe) ORDER BY inv.usuario)
                            UNION
                            (SELECT inv.id_vehiculo, inv.placa, inv.modelo, inv.marca, inv.color, inv.anio, inv.usuario, inv.id_usuario, 'EXTERNO' as tipo
                            FROM inventario inv
                            WHERE inv.id_usuario != $id_usuario ORDER BY inv.usuario)";
    }
    

    $result = $conn->query($sqlConsultaVehiculosG);

    $vehiculos = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $vehiculos[] = $row;
        }
    }
    echo json_encode($vehiculos);
}

// Consulta para obtener los vehiculos en general
if ($accion == "consultarInventarioAutoriza") {
        $sqlConsultaVehiculosG ="SELECT inv.id_vehiculo, inv.placa, inv.modelo, inv.marca, inv.color, inv.anio, inv.usuario, inv.id_usuario, 'AREA' as tipo
                            FROM inventario inv
                            WHERE inv.id_usuario = $id_usuario";



    $result = $conn->query($sqlConsultaVehiculosG);

    $vehiculos = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $vehiculos[] = $row;
        }
    }
    echo json_encode($vehiculos);
}

// Feed cronológico de todos los siniestros
if ($accion == "obtenerFeedSiniestros") {
    // Antes esto dependía de la cookie `rol`: cualquier valor distinto de '1' veía TODOS
    // los siniestros de la empresa. Como esa cookie la escribe el cliente y no va firmada,
    // bastaba con editarla en el navegador para saltarse el filtro. Ahora el acceso total
    // se valida en servidor contra accesos_especiales; quien no lo tenga sigue viendo solo
    // los siniestros de sus vehículos (propios, asignados o en préstamo autorizado).
    $verTodos = tieneAccesoEspecial($conn, $noEmpleado, 'verHistorialSiniestro');

    $whereClause = '';
    if (!$verTodos) {
        $idU = intval($id_usuario);
        $whereClause = "WHERE s.id_vehiculo IN (
            SELECT inv.id_vehiculo FROM inventario inv WHERE inv.id_us_asignado = $idU OR inv.id_usuario = $idU
            UNION
            SELECT p.id_vehiculo FROM prestamos p WHERE p.id_usuario = $idU AND p.estatus = 'AUTORIZADO'
        )";
    }

    $sql = "SELECT s.id_siniestro, s.fecha, s.hora, s.fecha_registro,
                   s.lugar, s.descripcion, s.partes_dañadas, s.ubicacion_vehiculo, s.coordenadas,
                   inv.placa, inv.modelo, inv.marca, inv.color, inv.anio, inv.id_vehiculo,
                   IFNULL(NULLIF(TRIM(CONCAT(IFNULL(rrhh.nombres,''),' ',IFNULL(rrhh.apellidos,''))),'' ), u.nombre) AS nombre_usuario,
                   COUNT(f.id_foto) AS num_fotos
            FROM siniestros s
            INNER JOIN inventario inv ON s.id_vehiculo = inv.id_vehiculo
            LEFT JOIN usuarios u ON s.id_dueno = u.id_usuario
            LEFT JOIN mess_rrhh.usuarios rrhh ON rrhh.noEmpleado = u.noEmpleado
            LEFT JOIN fotos f ON f.formato = 'Siniestro' AND f.id_formato = s.id_siniestro
            $whereClause
            GROUP BY s.id_siniestro
            ORDER BY s.fecha_registro DESC";

    $result = $conn->query($sql);
    $siniestros = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $siniestros[] = $row;
        }
    }
    echo json_encode($siniestros);
    exit;
}

// Detalle completo de un siniestro (con imágenes)
if ($accion == "obtenerDetallesSiniestro") {
    $id_sin = intval($_POST['id_siniestro'] ?? 0);
    if (!$id_sin) { echo json_encode(["error" => "ID inválido"]); exit; }

    $stmt = $conn->prepare(
        "SELECT s.id_siniestro, s.fecha, s.hora, s.fecha_registro,
                s.lugar, s.descripcion, s.partes_dañadas, s.ubicacion_vehiculo, s.coordenadas,
                inv.placa, inv.modelo, inv.marca, inv.color, inv.anio,
                IFNULL(NULLIF(TRIM(CONCAT(IFNULL(rrhh.nombres,''),' ',IFNULL(rrhh.apellidos,''))),'' ), u.nombre) AS nombre_usuario
         FROM siniestros s
         INNER JOIN inventario inv ON s.id_vehiculo = inv.id_vehiculo
         LEFT JOIN usuarios u ON s.id_dueno = u.id_usuario
         LEFT JOIN mess_rrhh.usuarios rrhh ON rrhh.noEmpleado = u.noEmpleado
         WHERE s.id_siniestro = ?"
    );
    if (!$stmt) { echo json_encode(["error" => "Error DB"]); exit; }
    $stmt->bind_param("i", $id_sin);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) { echo json_encode(["error" => "No encontrado"]); exit; }

    $stmtF = $conn->prepare("SELECT imagen FROM fotos WHERE formato = 'Siniestro' AND id_formato = ?");
    $stmtF->bind_param("i", $id_sin);
    $stmtF->execute();
    $resF = $stmtF->get_result();
    $imagenes = [];
    while ($fRow = $resF->fetch_assoc()) { $imagenes[] = $fRow['imagen']; }
    $stmtF->close();

    $row['imagenes'] = $imagenes;
    echo json_encode($row);
    exit;
}

?>