<?php
include 'includes/api_bootstrap.php';

$id_usuario = $_COOKIE['id_usuario'] ?? null;
$noEmpleado = $_COOKIE['noEmpleado'] ?? null;
$id_vehiculo = isset($_POST['id_vehiculo']) ? $_POST['id_vehiculo'] : null;

$accion = isset($_POST['accion']) ? $_POST['accion'] : null;

$placa = isset($_POST['placa']) ? $_POST['placa'] : null;
$monto = isset($_POST['monto']) ? $_POST['monto'] : null;
$pagos = isset($_POST['pagos']) ? $_POST['pagos'] : null;
$saldo = isset($_POST['saldo']) ? $_POST['saldo'] : null;
$km_actual = isset($_POST['km_actual']) ? $_POST['km_actual'] : null;
$fecha_carga = isset($_POST['fecha_carga']) ? $_POST['fecha_carga'] : null;
$fecha_registro = isset($_POST['fecha_registro']) ? $_POST['fecha_registro'] : null;

    /**
     * Conjunto de vehículos que el usuario puede consultar en gasolina.
     * Replica el criterio de consultarInventario (acciones_siniestro.php):
     * super usuario = acceso registrarMantenimiento con inf_adicional='TODAS'.
     * Devuelve ['todas'=>bool, 'ids'=>int[]].
     */
    function vehiculosPermitidosGas($conn, $id_usuario, $noEmpleado) {
        $id_usuario = intval($id_usuario);
        $noEmpleado = intval($noEmpleado);

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
            return ['todas' => true, 'ids' => []];
        }

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

        $sql = "SELECT inv.id_vehiculo FROM inventario inv
                WHERE inv.id_us_asignado = $id_usuario OR inv.id_usuario = $id_usuario
                UNION
                SELECT inv.id_vehiculo FROM prestamos p
                INNER JOIN inventario inv ON p.id_vehiculo = inv.id_vehiculo
                WHERE p.id_usuario = $id_usuario AND p.estatus = 'AUTORIZADO'";
        if (!empty($areas)) {
            $areasEsc = implode("','", $areas);
            $sql .= " UNION SELECT inv.id_vehiculo FROM inventario inv WHERE inv.area IN ('$areasEsc')";
        }
        if (!empty($deptos)) {
            $deptosEsc = implode(',', $deptos);
            $sql .= " UNION SELECT inv.id_vehiculo FROM inventario inv INNER JOIN usuarios us ON inv.id_usuario = us.id_usuario WHERE us.departamento IN ($deptosEsc)";
        }

        $ids = [];
        $res = $conn->query($sql);
        if ($res) { while ($r = $res->fetch_assoc()) { $ids[] = intval($r['id_vehiculo']); } }
        return ['todas' => false, 'ids' => $ids];
    }

    /* =========================== CICLOS DE TARJETA ===========================
     *
     * Un ciclo es el periodo de vida de un crédito: desde que se abona dinero a la
     * tarjeta hasta que se abona el siguiente. Cada abono abre un ciclo nuevo, también
     * los parciales.
     *
     * Antes esto se adivinaba de dos formas, las dos frágiles: el correo de solicitud
     * buscaba la última carga con `monto >= 4000`, y el detalle de la solicitud tomaba
     * una ventana de fechas entre solicitudes aprobadas. Ninguna de las dos aguanta una
     * recarga parcial, y la de los 4000 además ignora las tarjetas que nunca se llenan
     * (en la BD hay ciclos que arrancan en 748.77 y en 1493.92).
     *
     * Recordar que en carga_gasolina `monto` es el saldo ANTES de la carga: por eso un
     * abono se detecta cuando el monto de una carga supera el saldo que dejó la anterior.
     */

    /**
     * Crédito estándar de una tarjeta de gasolina.
     *
     * Es el tope al que se repone. Sirve para decidir si una recarga dejó la solicitud
     * satisfecha o solo a medias. El mismo número está en verPlaca() (js/global/vehiculos.js)
     * y en el diálogo de aprobación de validar_recargas.php: si cambia, cambiarlo en los tres.
     */
    const CREDITO_TARJETA = 4000;

    /**
     * Lo que se lleva abonado a una solicitud, sumando todas sus recargas.
     *
     * Una solicitud puede recibir varios abonos parciales antes de quedar cubierta, y cada
     * abono abre su propio ciclo, así que el acumulado sale de ahí.
     */
    function abonadoDeSolicitud($conn, $idSolicitud) {
        $stmt = $conn->prepare("SELECT IFNULL(SUM(monto_abonado), 0) AS total
                                FROM ciclos_tarjeta WHERE id_solicitud = ?");
        if (!$stmt) return 0.0;
        $stmt->bind_param("i", $idSolicitud);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (float) ($row['total'] ?? 0);
    }

    /** Ciclo abierto del vehículo, o null si no tiene. */
    function cicloAbiertoTarjeta($conn, $id_vehiculo) {
        $stmt = $conn->prepare(
            "SELECT id_ciclo, saldo_inicial, fecha_inicio FROM ciclos_tarjeta
             WHERE id_vehiculo = ? AND estatus = 'ABIERTO'
             ORDER BY fecha_inicio DESC, id_ciclo DESC LIMIT 1"
        );
        if (!$stmt) return null;
        $stmt->bind_param("i", $id_vehiculo);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /**
     * Cierra el ciclo abierto (si hay) y abre uno nuevo. Devuelve el id del nuevo.
     *
     * $origen: 'APROBACION' cuando lo dispara una recarga autorizada, 'DETECTADO' cuando
     * se dedujo de una carga cuyo monto subió, 'INICIAL' para el primer ciclo.
     */
    function abrirCicloTarjeta($conn, $id_vehiculo, $fecha, $saldoInicial, $montoAbonado, $origen, $idSolicitud = null, $idUsuario = null) {
        $abierto = cicloAbiertoTarjeta($conn, $id_vehiculo);
        if ($abierto) {
            $cerrar = $conn->prepare("UPDATE ciclos_tarjeta SET fecha_fin = ?, estatus = 'CERRADO' WHERE id_ciclo = ?");
            $cerrar->bind_param("si", $fecha, $abierto['id_ciclo']);
            $cerrar->execute();
            $cerrar->close();
        }

        $stmt = $conn->prepare(
            "INSERT INTO ciclos_tarjeta
                (id_vehiculo, fecha_inicio, saldo_inicial, monto_abonado, id_solicitud, id_usuario, origen, estatus)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'ABIERTO')"
        );
        if (!$stmt) return 0;
        $stmt->bind_param("isddiis", $id_vehiculo, $fecha, $saldoInicial, $montoAbonado, $idSolicitud, $idUsuario, $origen);
        $stmt->execute();
        $id = $conn->insert_id;
        $stmt->close();
        return intval($id);
    }

    /**
     * Ciclo al que pertenece una carga que se está registrando.
     *
     * Red de seguridad: si el monto capturado es mayor al saldo con el que venía el ciclo
     * abierto, hubo un abono que nadie registró (una recarga hecha fuera del sistema), así
     * que se abre un ciclo DETECTADO. Sin esto, las cargas posteriores a una recarga
     * manual se irían a engrosar el ciclo anterior y el "total gastado" saldría inflado.
     */
    function resolverCicloParaCarga($conn, $id_vehiculo, $monto, $fecha, $idUsuario) {
        $abierto = cicloAbiertoTarjeta($conn, $id_vehiculo);

        if (!$abierto) {
            return abrirCicloTarjeta($conn, $id_vehiculo, $fecha, $monto, null, 'INICIAL', null, $idUsuario);
        }

        // Saldo con el que quedó la última carga del ciclo; si aún no tiene cargas, el
        // saldo inicial del propio ciclo.
        $stmt = $conn->prepare(
            "SELECT saldo FROM carga_gasolina WHERE id_ciclo = ?
             ORDER BY fecha_carga DESC, id DESC LIMIT 1"
        );
        $stmt->bind_param("i", $abierto['id_ciclo']);
        $stmt->execute();
        $ult = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $saldoPrevio = $ult ? (float) $ult['saldo'] : (float) $abierto['saldo_inicial'];

        // El 0.01 es tolerancia de redondeo de DECIMAL(10,2), no un umbral de negocio.
        if ((float) $monto - $saldoPrevio > 0.01) {
            return abrirCicloTarjeta($conn, $id_vehiculo, $fecha,
                (float) $monto, round((float) $monto - $saldoPrevio, 2), 'DETECTADO', null, $idUsuario);
        }

        return intval($abierto['id_ciclo']);
    }

    /**
     * Busca clientes por nombre para el autocompletado del modal de gasolina.
     *
     * La tabla tiene 8,376 clientes, así que no se mandan todos al navegador ni cabe un
     * <select>: se busca desde 2 letras y se devuelve un máximo de 20. Se ordena poniendo
     * primero los que EMPIEZAN con lo tecleado, que es lo que la gente espera.
     */
    if ($accion == 'buscarClientes') {
        $q = trim($_POST['q'] ?? '');
        if (mb_strlen($q) < 2) { echo json_encode([]); exit; }

        $like = '%' . $q . '%';
        $prefijo = $q . '%';
        $stmt = $conn->prepare(
            "SELECT IDCLTE, CLIENTE, CIUDAD FROM clientes
             WHERE CLIENTE LIKE ?
             ORDER BY CASE WHEN CLIENTE LIKE ? THEN 0 ELSE 1 END, CLIENTE
             LIMIT 20"
        );
        if (!$stmt) { echo json_encode([]); exit; }
        $stmt->bind_param("ss", $like, $prefijo);
        $stmt->execute();
        $res = $stmt->get_result();
        $out = [];
        while ($row = $res->fetch_assoc()) { $out[] = $row; }
        $stmt->close();

        echo json_encode($out);
        exit;
    }

    //FUNCION PARA REGISTRAR CARGA
    if ($accion == 'registraGas'){
        // Validación y normalización en servidor. Antes se concatenaba todo directo al
        // INSERT y se confiaba en que MySQL acomodara los tipos: en local funciona
        // porque sql_mode está vacío y MySQL convierte en silencio, pero con
        // STRICT_TRANS_TABLES (lo normal en producción) el mismo INSERT falla. Como el
        // front daba por buena cualquier respuesta HTTP 200, el usuario veía "Guardado"
        // sin que se hubiera guardado nada.
        $idVeh = intval($id_vehiculo);
        $idUsr = intval($id_usuario);
        if ($idVeh <= 0 || $idUsr <= 0) {
            echo json_encode(["status" => "error", "message" => "Falta el vehículo o la sesión no es válida."]);
            exit;
        }

        // km_actual es INT en la tabla: se rechaza el decimal en vez de dejar que MySQL
        // lo redondee sin avisar (un odómetro no lleva fracciones).
        if ($km_actual === null || $km_actual === '' || !is_numeric($km_actual)) {
            echo json_encode(["status" => "error", "message" => "El kilometraje es obligatorio y debe ser un número."]);
            exit;
        }
        if (floor((float)$km_actual) != (float)$km_actual) {
            echo json_encode(["status" => "error", "message" => "El kilometraje debe ser un número entero, sin decimales."]);
            exit;
        }
        $km = intval($km_actual);
        if ($km < 0) {
            echo json_encode(["status" => "error", "message" => "El kilometraje no puede ser negativo."]);
            exit;
        }

        // fecha_carga es DATE, pero el input es datetime-local y manda "2026-08-20T12:43".
        // Se normaliza a Y-m-d; la hora se descarta porque la columna no la guarda.
        $fechaLimpia = null;
        if (!empty($fecha_carga)) {
            $ts = strtotime(str_replace('T', ' ', $fecha_carga));
            if ($ts) $fechaLimpia = date('Y-m-d', $ts);
        }
        if (!$fechaLimpia) {
            echo json_encode(["status" => "error", "message" => "La fecha de carga es obligatoria y debe tener un formato válido."]);
            exit;
        }

        $montoNum = is_numeric($monto) ? (float)$monto : 0;
        $pagosNum = is_numeric($pagos) ? (float)$pagos : 0;
        $saldoNum = is_numeric($saldo) ? (float)$saldo : ($montoNum - $pagosNum);

        // Ciclo de tarjeta al que pertenece esta carga. Si el monto viene por encima del
        // saldo del ciclo abierto, resolverCicloParaCarga() abre uno nuevo solo.
        $idCiclo = resolverCicloParaCarga($conn, $idVeh, $montoNum, $fechaLimpia . ' 00:00:00', $idUsr);

        // A qué fue el viaje. Los dos son opcionales: no toda carga de gasolina va ligada
        // a una visita. id_cliente se guarda como NULL (no 0) cuando no se eligió, para
        // que el LEFT JOIN a clientes no intente emparejar un id inexistente.
        $idCliente = isset($_POST['id_cliente']) && intval($_POST['id_cliente']) > 0
            ? intval($_POST['id_cliente']) : null;
        $otCarga = trim($_POST['ot'] ?? '');
        if ($otCarga === '') { $otCarga = null; }

        // Si capturaron un OT/OV pero no eligieron cliente, se resuelve solo, igual que
        // hace el check-in del QR: resolverCliente() busca el número primero en la tabla
        // OT y luego en OV. Se reutiliza la función de calcular_ruta.php en vez de
        // duplicar la consulta, para que las dos vías den el mismo cliente.
        // El archivo solo declara funciones y constantes al incluirse (su parte
        // ejecutable está detrás de un guard de "me están corriendo directo").
        if ($idCliente === null && $otCarga !== null) {
            require_once __DIR__ . '/calcular_ruta.php';
            $idCliente = resolverCliente($conn, $otCarga);
        }

        $stmt = $conn->prepare(
            "INSERT INTO carga_gasolina
                (id_usuario, id_vehiculo, id_ciclo, monto, pagos, saldo, km_actual, id_cliente, ot, fecha_carga, fecha_registro)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        if (!$stmt) {
            echo json_encode(["status" => "error", "message" => "Error al preparar el registro: " . $conn->error]);
            exit;
        }
        $stmt->bind_param("iiidddiiss", $idUsr, $idVeh, $idCiclo, $montoNum, $pagosNum, $saldoNum, $km, $idCliente, $otCarga, $fechaLimpia);
        $ok = $stmt->execute();
        $err = $stmt->error;
        $stmt->close();

        if ($ok) {
            echo json_encode(["status" => "success", "message" => "Carga de gasolina registrada correctamente.", "saldo" => $saldoNum]);
        } else {
            echo json_encode(["status" => "error", "message" => "Error al registrar la carga de gasolina: " . $err]);
        }
        exit;
    }

    //FUNCION PARA OBTENER DATOS DE LA ULTIMA CARGA
    if ($accion == 'obtenerRegistrosGas'){
        $sqlU = "SELECT ca.*, CONCAT(inv.placa, ' - ', inv.modelo, ' - ', inv.marca) AS Vehiculo,
                        IFNULL(NULLIF(TRIM(CONCAT(IFNULL(rrhh.nombres,''),' ',IFNULL(rrhh.apellidos,''))),'' ), inv.usuario) AS usuario
                FROM carga_gasolina ca
                INNER JOIN inventario inv ON inv.id_vehiculo = ca.id_vehiculo
                LEFT JOIN usuarios cv_u ON cv_u.id_usuario = inv.id_usuario
                LEFT JOIN mess_rrhh.usuarios rrhh ON rrhh.noEmpleado = cv_u.noEmpleado
                WHERE inv.id_vehiculo IN (SELECT id_vehiculo FROM inventario WHERE id_usuario = '".$_COOKIE['id_usuario']."')
                ORDER BY ca.id DESC";
        
        $resultU = $conn->query($sqlU);
        $registros = array();
        if ($resultU) {
            while ($row = $resultU->fetch_assoc()) {
                $registros[] = $row;
            }
            echo json_encode($registros);
        } else {
            echo json_encode(array("status" => "error", "message" => "Error al obtener la última carga de gasolina: " . $conn->error));
        }
        exit;
    }
        //FUNCION PARA OBTENER TODOS LOS REGISTROS DE GASOLINA
        if ($accion == 'obtenerRegistrosGasTodos'){
        $sqlU = "SELECT ca.*, CONCAT(inv.placa, ' - ', inv.modelo, ' - ', inv.marca) AS Vehiculo,
                        IFNULL(NULLIF(TRIM(CONCAT(IFNULL(rrhh.nombres,''),' ',IFNULL(rrhh.apellidos,''))),'' ), inv.usuario) AS usuario
                FROM carga_gasolina ca
                INNER JOIN inventario inv ON inv.id_vehiculo = ca.id_vehiculo
                LEFT JOIN usuarios cv_u ON cv_u.id_usuario = inv.id_usuario
                LEFT JOIN mess_rrhh.usuarios rrhh ON rrhh.noEmpleado = cv_u.noEmpleado
                ORDER BY ca.id DESC";

        $resultU = $conn->query($sqlU);
        $registros = array();
        if ($resultU) {
            while ($row = $resultU->fetch_assoc()) {
                $registros[] = $row;
            }
            echo json_encode($registros);
        } else {
            echo json_encode(array("status" => "error", "message" => "Error al obtener la última carga de gasolina: " . $conn->error));
        }
        exit;
    }

    // Historial de UN vehículo (tarjeta de gas por vehículo) con km_consumidos calculado
    if ($accion == 'obtenerHistorialGas') {
        $idVeh = isset($_POST['id_vehiculo']) ? intval($_POST['id_vehiculo']) : 0;

        // La vista es por-vehículo: sin vehículo no se devuelve nada.
        if ($idVeh <= 0) { echo json_encode([]); exit; }

        // Salvaguarda anti-IDOR: solo vehículos permitidos (asignados, o todos si super).
        $perm = vehiculosPermitidosGas($conn, $id_usuario, $noEmpleado);
        if (!$perm['todas'] && !in_array($idVeh, $perm['ids'], true)) {
            echo json_encode([]);
            exit;
        }

        $sqlU = "SELECT
                    cg.*,
                    CONCAT(inv.placa, ' - ', inv.modelo, ' ', inv.marca) AS Vehiculo,
                    inv.placa,
                    IFNULL(NULLIF(TRIM(CONCAT(IFNULL(rrhh.nombres,''),' ',IFNULL(rrhh.apellidos,''))),'' ), u.nombre) AS nombre_usuario,
                    (cg.km_actual - IFNULL(
                        (SELECT prev.km_actual FROM carga_gasolina prev
                         WHERE prev.id_vehiculo = cg.id_vehiculo AND prev.id < cg.id
                         ORDER BY prev.id DESC LIMIT 1),
                        cg.km_actual
                    )) AS km_consumidos,
                    cli.CLIENTE AS cliente
                 FROM carga_gasolina cg
                 INNER JOIN inventario inv ON inv.id_vehiculo = cg.id_vehiculo
                 LEFT JOIN usuarios u ON u.id_usuario = cg.id_usuario
                 LEFT JOIN mess_rrhh.usuarios rrhh ON rrhh.noEmpleado = u.noEmpleado
                 LEFT JOIN clientes cli ON cli.IDCLTE = cg.id_cliente
                 WHERE cg.id_vehiculo = ?
                 ORDER BY cg.id DESC";

        $registros = [];
        $stmt = $conn->prepare($sqlU);
        if ($stmt) {
            $stmt->bind_param("i", $idVeh);
            $stmt->execute();
            $resultU = $stmt->get_result();
            while ($row = $resultU->fetch_assoc()) {
                $registros[] = $row;
            }
            $stmt->close();
            echo json_encode($registros);
        } else {
            echo json_encode(['status' => 'error', 'message' => $conn->error]);
        }
        exit;
    }

    // Vehículo a precargar en el historial: el de más km recorridos del usuario
    // (o el de más km global si es super). Fallback: primer vehículo asignado.
    if ($accion == 'vehiculoPrincipalGas') {
        $perm = vehiculosPermitidosGas($conn, $id_usuario, $noEmpleado);
        $idElegido = 0;

        if ($perm['todas']) {
            $res = $conn->query("SELECT id_vehiculo FROM reporte_km_vehiculo GROUP BY id_vehiculo ORDER BY SUM(km_registrado) DESC LIMIT 1");
            if ($res && ($row = $res->fetch_assoc())) { $idElegido = intval($row['id_vehiculo']); }
            if (!$idElegido) {
                $res2 = $conn->query("SELECT id_vehiculo FROM inventario WHERE estatus = 'Activo' ORDER BY id_vehiculo LIMIT 1");
                if ($res2 && ($row2 = $res2->fetch_assoc())) { $idElegido = intval($row2['id_vehiculo']); }
            }
        } else {
            $ids = $perm['ids'];
            if (!empty($ids)) {
                $idsEsc = implode(',', array_map('intval', $ids));
                $res = $conn->query("SELECT id_vehiculo FROM reporte_km_vehiculo WHERE id_vehiculo IN ($idsEsc) GROUP BY id_vehiculo ORDER BY SUM(km_registrado) DESC LIMIT 1");
                if ($res && ($row = $res->fetch_assoc())) { $idElegido = intval($row['id_vehiculo']); }
                if (!$idElegido) { $idElegido = intval($ids[0]); } // fallback: primero asignado
            }
        }

        $placaElegida = '';
        if ($idElegido) {
            $stmtP = $conn->prepare("SELECT placa FROM inventario WHERE id_vehiculo = ? LIMIT 1");
            $stmtP->bind_param("i", $idElegido);
            $stmtP->execute();
            $rp = $stmtP->get_result()->fetch_assoc();
            $placaElegida = $rp ? $rp['placa'] : '';
            $stmtP->close();
        }

        echo json_encode(['id_vehiculo' => $idElegido, 'placa' => $placaElegida]);
        exit;
    }

    // Solicitar reposición de crédito de gasolina — envía correo al encargado
    if ($accion == 'solicitarReposicionGas') {
        $id_vehiculo_req = isset($_POST['id_vehiculo']) ? intval($_POST['id_vehiculo']) : 0;
        $saldo_actual    = isset($_POST['saldo'])       ? floatval($_POST['saldo'])       : 0;
        $noEmp           = $_COOKIE['noEmpleado'] ?? '';

        $stmt = $conn->prepare("SELECT nombre FROM usuarios WHERE noEmpleado = ?");
        $stmt->bind_param("s", $noEmp);
        $stmt->execute();
        $row_u = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $nombre_sol = $row_u ? $row_u['nombre'] : 'Empleado #' . $noEmp;

        $stmt2 = $conn->prepare("SELECT placa, modelo, marca, efecticard FROM inventario WHERE id_vehiculo = ?");
        $stmt2->bind_param("i", $id_vehiculo_req);
        $stmt2->execute();
        $row_v = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();
        $vehiculo_info = $row_v
            ? $row_v['placa'] . ' - ' . $row_v['modelo'] . ' ' . $row_v['marca']
            : 'Vehículo #' . $id_vehiculo_req;
        // Número de tarjeta Efecticard: es el dato con el que cuentas de gastos
        // identifica la tarjeta a reponer. No todos los vehículos lo tienen cargado,
        // así que se avisa explícitamente en lugar de mandar una celda vacía.
        $tarjeta_txt = ($row_v && !empty($row_v['efecticard']))
            ? htmlspecialchars((string) $row_v['efecticard'])
            : '<span style="color:#999">Sin tarjeta registrada</span>';

        // Km recorridos con el crédito que se está agotando. El ciclo arranca en la carga
        // donde el monto vuelve al crédito completo (4000, ver verPlaca en
        // js/global/vehiculos.js), así que el recorrido es la diferencia entre la última
        // lectura del odómetro y la de esa carga. Sirve para juzgar si el consumo fue
        // razonable antes de reponer.
        // Se toma la ÚLTIMA lectura por fecha, no MAX(km_actual): hay dedazos en la
        // captura (por ejemplo un 1681614 entre lecturas de ~169000) y con MAX salía un
        // recorrido de más de un millón de kilómetros.
        //
        // km_ultimo sale del helper compartido, que además de carga_gasolina mira los
        // check-ins: si el vehículo se usó después de la última carga, ese kilometraje es
        // el bueno. km_inicio sí es propio de carga_gasolina, porque el ciclo se define
        // por la carga en la que el monto vuelve al crédito completo.
        $stmtKm = $conn->prepare(
            "SELECT km_actual FROM carga_gasolina
              WHERE id_vehiculo = ? AND monto >= 4000
              ORDER BY fecha_carga DESC, id DESC LIMIT 1"
        );
        $stmtKm->bind_param("i", $id_vehiculo_req);
        $stmtKm->execute();
        $rowKm = $stmtKm->get_result()->fetch_assoc();
        $stmtKm->close();

        $kmUltimo = obtenerUltimoKM($conn, $id_vehiculo_req);
        $kmInicio = isset($rowKm['km_actual']) ? intval($rowKm['km_actual']) : 0;

        // Tope de coherencia: un crédito de $4,000 rinde del orden de 1,500 km. Un
        // resultado muy por encima solo puede venir de una lectura mal capturada, y es
        // preferible no decir nada a mandar una cifra absurda en el correo.
        $LIMITE_KM_CREDITO = 10000;
        $kmRecorrido = null;
        if ($kmUltimo > 0 && $kmInicio > 0 && $kmUltimo >= $kmInicio) {
            $diff = $kmUltimo - $kmInicio;
            if ($diff <= $LIMITE_KM_CREDITO) $kmRecorrido = $diff;
        }

        $kmRecorridoTxt = $kmRecorrido !== null
            ? number_format($kmRecorrido) . ' km'
            : 'Sin datos suficientes';
        $kmUltimoTxt = $kmUltimo > 0 ? number_format($kmUltimo) . ' km' : 'S/R';

        // Se reutiliza enviarCorreoMess() (includes/enviar_notificacion.php) en vez de
        // repetir aquí la configuración SMTP. Además acepta un destinatario alterno, que
        // sirve para mandar muestras sin escribirle a cuentas de gastos; el parámetro NO
        // se lee del POST, así que no se puede desviar el correo desde el cliente.
        require_once __DIR__ . '/includes/enviar_notificacion.php';

        $destinatariosGas = isset($destinatariosGasPrueba) && $destinatariosGasPrueba
            ? $destinatariosGasPrueba
            : ['sebastian.gutierrez@mess.com.mx'];

        $cuerpoGas = '
            <html><body style="font-family:Arial,sans-serif;color:#222">
            <div style="text-align:center">
                <img width="20%" src="https://www.mess.com.mx/incidencias/img/MESS_05_Imagotipo_1.png">
                <hr style="border:2px solid #050D9E">
            </div>
            <div style="max-width:600px;margin:auto;padding:20px">
                <h2 style="color:#050D9E">Solicitud de Reposición de Crédito de Gasolina</h2>
                <p><strong>' . htmlspecialchars($nombre_sol) . '</strong> solicita reposición de crédito de gasolina a través del sistema de Control Vehicular.</p>
                <table style="border-collapse:collapse;width:100%;margin-top:16px">
                    <tr style="background:#f0f4ff">
                        <td style="padding:10px;border:1px solid #ccc"><strong>Vehículo</strong></td>
                        <td style="padding:10px;border:1px solid #ccc">' . htmlspecialchars($vehiculo_info) . '</td>
                    </tr>
                    <tr>
                        <td style="padding:10px;border:1px solid #ccc"><strong>No. de tarjeta Efecticard</strong></td>
                        <td style="padding:10px;border:1px solid #ccc">' . $tarjeta_txt . '</td>
                    </tr>
                    <tr style="background:#f0f4ff">
                        <td style="padding:10px;border:1px solid #ccc"><strong>Saldo actual</strong></td>
                        <td style="padding:10px;border:1px solid #ccc">$' . number_format($saldo_actual, 2) . '</td>
                    </tr>
                    <tr>
                        <td style="padding:10px;border:1px solid #ccc"><strong>Km recorridos con este crédito</strong></td>
                        <td style="padding:10px;border:1px solid #ccc">' . $kmRecorridoTxt . '</td>
                    </tr>
                    <tr style="background:#f0f4ff">
                        <td style="padding:10px;border:1px solid #ccc"><strong>Kilometraje actual</strong></td>
                        <td style="padding:10px;border:1px solid #ccc">' . $kmUltimoTxt . '</td>
                    </tr>
                    <tr>
                        <td style="padding:10px;border:1px solid #ccc"><strong>Fecha</strong></td>
                        <td style="padding:10px;border:1px solid #ccc">' . date('d/m/Y H:i') . '</td>
                    </tr>
                </table>
                <p style="margin-top:20px">
                    <a href="https://messbook.com.mx/ControlVehicular/historial_gasolina"
                       style="background:#050D9E;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none">
                        Ver historial de gasolina
                    </a>
                </p>
            </div>
            <p style="text-align:center;color:#888;font-size:12px">Mensaje automático — no responder.</p>
            </body></html>';

        // La solicitud se PERSISTE antes de enviar el correo. Hasta ahora solo se mandaba
        // el aviso y no quedaba rastro: no se podía saber cuántas había pendientes, quién
        // las resolvió ni cuándo. Es lo que alimenta la vista de validación.
        $idSolicitante = intval($_COOKIE['id_usuario'] ?? 0);
        $stmtSol = $conn->prepare(
            "INSERT INTO solicitudes_gas
                (id_vehiculo, id_usuario, saldo_solicitud, km_actual, km_recorrido, estatus, fecha)
             VALUES (?, ?, ?, ?, ?, 'PENDIENTE', NOW())"
        );
        $kmUltimoBd   = $kmUltimo > 0 ? $kmUltimo : null;
        $kmRecorridoBd = $kmRecorrido;   // null si no se pudo calcular con certeza
        $stmtSol->bind_param("iidii", $id_vehiculo_req, $idSolicitante, $saldo_actual, $kmUltimoBd, $kmRecorridoBd);
        $stmtSol->execute();
        $stmtSol->close();

        $resGas = enviarCorreoMess($destinatariosGas, 'Solicitud de reposición de crédito de gasolina', $cuerpoGas);
        if ($resGas['ok']) {
            echo json_encode(['status' => 'success', 'message' => 'Solicitud enviada correctamente al encargado.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al enviar: ' . $resGas['error']]);
        }
        exit;
    }

    /**
     * Listado de solicitudes de recarga para la vista de validación.
     *
     * Ver la lista y resolverla son permisos distintos: verSolicitudesGas deja consultar
     * (BI, Roger, Óscar) y autorizaRecargaGas deja aprobar o rechazar (solo Vico). Se
     * devuelve `puedeResolver` para que la vista muestre u oculte los botones, aunque la
     * validación real se repite en el servidor al resolver.
     */
    if ($accion == 'consultarSolicitudesGas') {
        $noEmpSol = $_COOKIE['noEmpleado'] ?? 0;
        if (!tieneAccesoEspecial($conn, $noEmpSol, 'verSolicitudesGas')) {
            echo json_encode(['status' => 'error', 'message' => 'No tienes acceso a esta vista.']);
            exit;
        }

        // usuarios.id_usuario no es único: se empareja con la fila de mayor id para que
        // el JOIN no multiplique las solicitudes.
        $sql = "SELECT s.id, s.id_vehiculo, s.saldo_solicitud, s.km_actual, s.km_recorrido,
                       s.estatus, s.fecha, DATE(s.fecha) AS fecha_dia,
                       s.fecha_resuelto, s.notas_resolucion,
                       inv.placa, inv.marca, inv.modelo, inv.efecticard,
                       IFNULL(NULLIF(TRIM(CONCAT(IFNULL(rrhh.nombres,''),' ',IFNULL(rrhh.apellidos,''))),''), u.nombre) AS solicitante,
                       ur.nombre AS resolvio,
                       -- Lo que se lleva abonado a esta solicitud. Con una parcialidad la
                       -- tarjeta ya recibió dinero, así que el saldo real es
                       -- saldo_solicitud + esto, y es contra ese que se calcula el resto.
                       (SELECT IFNULL(SUM(ct.monto_abonado), 0)
                          FROM ciclos_tarjeta ct WHERE ct.id_solicitud = s.id) AS abonado_acumulado
                FROM solicitudes_gas s
                LEFT JOIN inventario inv ON inv.id_vehiculo = s.id_vehiculo
                LEFT JOIN usuarios u  ON u.id  = (SELECT MAX(u2.id) FROM usuarios u2 WHERE u2.id_usuario = s.id_usuario)
                LEFT JOIN usuarios ur ON ur.id = (SELECT MAX(u3.id) FROM usuarios u3 WHERE u3.id_usuario = s.id_resuelve)
                LEFT JOIN mess_rrhh.usuarios rrhh ON rrhh.noEmpleado = u.noEmpleado
                ORDER BY FIELD(s.estatus,'PENDIENTE','APROBADA','RECHAZADA'), s.fecha DESC";

        $res = $conn->query($sql);
        $solicitudes = [];
        if ($res) { while ($row = $res->fetch_assoc()) $solicitudes[] = $row; }

        echo json_encode([
            'status'        => 'success',
            'puedeResolver' => tieneAccesoEspecial($conn, $noEmpSol, 'autorizaRecargaGas'),
            'solicitudes'   => $solicitudes
        ]);
        exit;
    }

    /**
     * Solicitudes que hizo el usuario logueado, para que pueda seguirlas.
     *
     * A diferencia de consultarSolicitudesGas, esta NO pide acceso especial: cualquiera
     * puede ver lo que él mismo pidió. Antes, quien solicitaba una reposición no tenía
     * forma de saber si se la habían aprobado, rechazado o abonado a medias — el estado
     * solo existía en la pantalla de quien autoriza.
     *
     * El filtro va contra s.id_usuario, o sea SUS solicitudes, no las de sus vehículos:
     * un vehículo prestado lo pide quien lo trae, y es esa persona la que da seguimiento.
     */
    if ($accion == 'misSolicitudesGas') {
        $idUsrSol = intval($id_usuario);
        if ($idUsrSol <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Sesión no válida.']);
            exit;
        }

        $stmt = $conn->prepare(
            "SELECT s.id, s.id_vehiculo, s.saldo_solicitud, s.km_actual, s.km_recorrido,
                    s.estatus, s.fecha, s.fecha_resuelto, s.notas_resolucion,
                    inv.placa, inv.marca, inv.modelo, inv.efecticard,
                    ur.nombre AS resolvio,
                    (SELECT IFNULL(SUM(ct.monto_abonado), 0)
                       FROM ciclos_tarjeta ct WHERE ct.id_solicitud = s.id) AS abonado_acumulado
             FROM solicitudes_gas s
             LEFT JOIN inventario inv ON inv.id_vehiculo = s.id_vehiculo
             LEFT JOIN usuarios ur ON ur.id = (SELECT MAX(u3.id) FROM usuarios u3 WHERE u3.id_usuario = s.id_resuelve)
             WHERE s.id_usuario = ?
             ORDER BY FIELD(s.estatus,'PENDIENTE','PARCIAL','APROBADA','RECHAZADA'), s.fecha DESC"
        );
        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => 'Error al preparar la consulta.']);
            exit;
        }
        $stmt->bind_param("i", $idUsrSol);
        $stmt->execute();
        $res = $stmt->get_result();
        $mias = [];
        while ($row = $res->fetch_assoc()) { $mias[] = $row; }
        $stmt->close();

        echo json_encode([
            'status'      => 'success',
            'credito'     => CREDITO_TARJETA,
            'solicitudes' => $mias
        ]);
        exit;
    }

    /** Aprobar o rechazar una solicitud. Solo con autorizaRecargaGas. */
    if ($accion == 'resolverSolicitudGas') {
        $noEmpRes = $_COOKIE['noEmpleado'] ?? 0;
        if (!tieneAccesoEspecial($conn, $noEmpRes, 'autorizaRecargaGas')) {
            echo json_encode(['status' => 'error', 'message' => 'No tienes permiso para resolver solicitudes.']);
            exit;
        }

        $idSol      = intval($_POST['id_solicitud'] ?? 0);
        $resolucion = strtoupper(trim($_POST['resolucion'] ?? ''));
        $notas      = trim($_POST['notas'] ?? '');
        // Cuánto se abonó realmente a la tarjeta. Puede diferir de lo solicitado (recarga
        // parcial), y es lo que define el saldo con el que arranca el ciclo nuevo.
        $montoRecarga = isset($_POST['monto_recarga']) && is_numeric($_POST['monto_recarga'])
            ? round((float) $_POST['monto_recarga'], 2) : 0;

        if ($idSol <= 0 || !in_array($resolucion, ['APROBADA', 'RECHAZADA'], true)) {
            echo json_encode(['status' => 'error', 'message' => 'Datos incompletos.']);
            exit;
        }

        // Las notas son obligatorias en las dos resoluciones: si se aprueba hay que poder
        // reconstruir con qué criterio, y si se rechaza el solicitante necesita el motivo.
        if ($notas === '') {
            echo json_encode(['status' => 'error', 'message' => 'Las notas son obligatorias para resolver la solicitud.']);
            exit;
        }

        if ($resolucion === 'APROBADA' && $montoRecarga <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Captura el monto que se abonó a la tarjeta.']);
            exit;
        }

        // Una recarga PARCIAL no cierra la solicitud: se queda abierta con estatus
        // 'PARCIAL' para no perder de vista que falta abonar el resto. Solo se marca
        // APROBADA cuando la tarjeta alcanza el crédito completo.
        //
        // El saldo efectivo es el que tenía al solicitar MÁS lo que ya se le abonó en
        // resoluciones anteriores; sin eso, el segundo abono volvería a compararse contra
        // el saldo original y nunca cerraría.
        $estatusFinal = $resolucion;
        $saldoEfectivo = 0.0;
        if ($resolucion === 'APROBADA') {
            $qs = $conn->prepare("SELECT saldo_solicitud FROM solicitudes_gas WHERE id = ? LIMIT 1");
            $qs->bind_param("i", $idSol);
            $qs->execute();
            $rs = $qs->get_result()->fetch_assoc();
            $qs->close();

            $saldoEfectivo = (float) ($rs['saldo_solicitud'] ?? 0) + abonadoDeSolicitud($conn, $idSol);
            // Tolerancia de centavo: DECIMAL(10,2) y sumas de floats no dan exacto.
            if ($saldoEfectivo + $montoRecarga < CREDITO_TARJETA - 0.01) {
                $estatusFinal = 'PARCIAL';
            }
        }

        // Se resuelve lo que sigue abierto: PENDIENTE o PARCIAL (esta última justamente
        // para poder abonarle el resto). Evita que dos personas la cambien dos veces o
        // que se reabra algo ya cerrado.
        $idResuelve = intval($_COOKIE['id_usuario'] ?? 0);
        $stmt = $conn->prepare(
            "UPDATE solicitudes_gas
                SET estatus = ?, id_resuelve = ?, fecha_resuelto = NOW(), notas_resolucion = ?
              WHERE id = ? AND estatus IN ('PENDIENTE', 'PARCIAL')"
        );
        $stmt->bind_param("sisi", $estatusFinal, $idResuelve, $notas, $idSol);
        $stmt->execute();
        $cambio = $stmt->affected_rows > 0;
        $stmt->close();

        if (!$cambio) {
            echo json_encode(['status' => 'error', 'message' => 'La solicitud ya fue resuelta o no existe.']);
            exit;
        }

        // Aprobar es lo que abre un ciclo de tarjeta nuevo: es el momento en que entra
        // dinero. El saldo inicial es lo que le quedaba más lo abonado, no el abono
        // solo — así una recarga parcial arranca con el saldo real de la tarjeta.
        $idCicloNuevo = 0;
        if ($resolucion === 'APROBADA') {
            $qv = $conn->prepare("SELECT id_vehiculo, saldo_solicitud FROM solicitudes_gas WHERE id = ? LIMIT 1");
            $qv->bind_param("i", $idSol);
            $qv->execute();
            $sv = $qv->get_result()->fetch_assoc();
            $qv->close();

            if ($sv) {
                // $saldoEfectivo ya trae el saldo original MÁS los abonos previos de esta
                // misma solicitud: en el segundo abono de una parcialidad, partir otra vez
                // de saldo_solicitud daría un saldo inicial más bajo que el real.
                $saldoInicial = round($saldoEfectivo + $montoRecarga, 2);
                $idCicloNuevo = abrirCicloTarjeta(
                    $conn, intval($sv['id_vehiculo']), date('Y-m-d H:i:s'),
                    $saldoInicial, $montoRecarga, 'APROBACION', $idSol, $idResuelve
                );
            }
        }

        // El aviso al solicitante no debe tumbar la resolución: si el correo falla, la
        // solicitud ya quedó resuelta igual.
        $avisado = false;
        try {
            require_once __DIR__ . '/includes/enviar_notificacion.php';
            $q = $conn->prepare(
                "SELECT s.saldo_solicitud, s.notas_resolucion, inv.placa, inv.marca, inv.modelo,
                        u.nombre AS nombre_sol, u.correo AS correo_sol
                 FROM solicitudes_gas s
                 LEFT JOIN inventario inv ON inv.id_vehiculo = s.id_vehiculo
                 LEFT JOIN usuarios u ON u.id = (SELECT MAX(u2.id) FROM usuarios u2 WHERE u2.id_usuario = s.id_usuario)
                 WHERE s.id = ? LIMIT 1"
            );
            $q->bind_param("i", $idSol);
            $q->execute();
            $d = $q->get_result()->fetch_assoc();
            $q->close();

            if ($d && !empty($d['correo_sol'])) {
                $aprobada = ($resolucion === 'APROBADA');
                $esc = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
                $vehiculo = trim($d['marca'] . ' ' . $d['modelo']) . ' — ' . $d['placa'];
                $contenido = '<h2>Hola ' . $esc($d['nombre_sol']) . ',</h2>'
                    . '<h3 style="font-weight:normal">Tu solicitud de reposición de crédito de gasolina fue <b>'
                    . ($aprobada ? 'aprobada' : 'rechazada') . '</b>.</h3>'
                    . '<table style="margin:0 auto; font-size:15px; line-height:1.6">'
                    . '<tr><td align="right"><b>Vehículo:</b></td><td align="left">' . $esc($vehiculo) . '</td></tr>'
                    // El monto abonado solo aplica cuando se aprobó. Es el dato que el
                    // solicitante necesita para saber con cuánto quedó su tarjeta, sobre
                    // todo cuando la recarga fue parcial y no por lo que pidió.
                    . ($aprobada
                        ? '<tr><td align="right"><b>Monto abonado:</b></td><td align="left">$'
                          . number_format($montoRecarga, 2) . '</td></tr>'
                          . '<tr><td align="right"><b>Saldo de la tarjeta:</b></td><td align="left">$'
                          . number_format((float) $d['saldo_solicitud'] + $montoRecarga, 2) . '</td></tr>'
                        : '')
                    . ($notas !== '' ? '<tr><td align="right"><b>Notas:</b></td><td align="left">' . $esc($notas) . '</td></tr>' : '')
                    . '</table>';

                $r = enviarCorreoMess(
                    [$d['correo_sol']],
                    ($aprobada ? 'Recarga de gasolina aprobada' : 'Recarga de gasolina rechazada') . ' — Control Vehicular',
                    plantillaCorreoMess(
                        $aprobada ? 'Recarga aprobada' : 'Recarga rechazada',
                        $aprobada ? 'rgb(29, 179, 47)' : 'rgb(200, 45, 45)',
                        $contenido
                    )
                );
                $avisado = $r['ok'];
            }
        } catch (Throwable $e) {
            error_log("Fallo al notificar resolución de recarga $idSol: " . $e->getMessage());
        }

        echo json_encode(['status' => 'success', 'notificado' => $avisado]);
        exit;
    }

    // Verificar si el vehículo tiene checklist completo antes de registrar gas
    /**
     * ¿El vehículo tiene su checklist al día para poder cargar gasolina?
     *
     * Se evalúa POR VEHÍCULO: el checklist más reciente del vehículo debe estar
     * 'completo'. Es exactamente la misma regla con la que qr_vehiculo.php pinta su
     * badge (ver obtenerDatosVehiculo en acciones_qr.php), y esa consistencia es el
     * punto: antes aquí se filtraba además por el id_usuario que estaba capturando, así
     * que en un préstamo el que maneja —que no llenó el checklist— veía "Checklist
     * pendiente" al mismo tiempo que la tarjeta del QR le decía "Completo".
     */
    if ($accion == 'verificarChecklistGas') {
        $id_vehiculo_chk = isset($_POST['id_vehiculo']) ? intval($_POST['id_vehiculo']) : 0;

        if (!$id_vehiculo_chk) {
            echo json_encode(['tiene' => false]);
            exit;
        }

        $stmt = $conn->prepare(
            "SELECT estatus FROM checklist
             WHERE id_vehiculo = ?
             ORDER BY fecha DESC, id_checklist DESC
             LIMIT 1"
        );
        $stmt->bind_param("i", $id_vehiculo_chk);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $tiene = $row && $row['estatus'] === 'completo';

        // Se devuelve también el estatus para que el front pueda decir POR QUÉ está
        // bloqueado (sin checklist vs. con un borrador a medias) en vez de un genérico.
        echo json_encode(['tiene' => $tiene, 'estatus' => $row['estatus'] ?? null]);
        exit;
    }

    /**
     * Cargas de gasolina que consumieron el crédito de una solicitud.
     *
     * Se resuelven por CICLO DE TARJETA, no por una ventana de fechas.
     *
     * Antes esto tomaba el rango entre la última solicitud aprobada y esta. Funcionaba
     * mientras cada crédito naciera de una solicitud, pero se rompía justo en el caso
     * que reportaron: una recarga parcial hecha fuera del flujo no movía la ventana, así
     * que sus cargas se sumaban al crédito anterior y el total gastado salía inflado.
     *
     * Ahora cada carga trae su id_ciclo y basta con pedir el ciclo que estaba vigente
     * cuando se hizo la solicitud.
     */
    if ($accion == 'historialCargasSolicitud') {
        $noEmpHist = $_COOKIE['noEmpleado'] ?? 0;
        if (!tieneAccesoEspecial($conn, $noEmpHist, 'verSolicitudesGas')) {
            echo json_encode(['status' => 'error', 'message' => 'No tienes acceso a esta vista.']);
            exit;
        }

        $idSolHist = isset($_POST['id_solicitud']) ? intval($_POST['id_solicitud']) : 0;
        if ($idSolHist <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Solicitud inválida.']);
            exit;
        }

        $stmtS = $conn->prepare(
            "SELECT s.id, s.id_vehiculo, s.fecha, s.saldo_solicitud, s.estatus,
                    inv.placa, inv.marca, inv.modelo, inv.efecticard,
                    -- Usuario del VEHÍCULO (su dueño/asignado), que no es necesariamente
                    -- quien pidió la recarga. Se prefiere el nombre de RRHH y se cae al
                    -- texto que guarda inventario cuando el id_usuario quedó huérfano.
                    IFNULL(NULLIF(TRIM(CONCAT(IFNULL(rrhh.nombres,''),' ',IFNULL(rrhh.apellidos,''))),''),
                           NULLIF(TRIM(inv.usuario), '')) AS usuario_vehiculo
             FROM solicitudes_gas s
             LEFT JOIN inventario inv ON inv.id_vehiculo = s.id_vehiculo
             LEFT JOIN usuarios uv ON uv.id = (SELECT MAX(u2.id) FROM usuarios u2 WHERE u2.id_usuario = inv.id_usuario)
             LEFT JOIN mess_rrhh.usuarios rrhh ON rrhh.noEmpleado = uv.noEmpleado
             WHERE s.id = ? LIMIT 1"
        );
        $stmtS->bind_param("i", $idSolHist);
        $stmtS->execute();
        $sol = $stmtS->get_result()->fetch_assoc();
        $stmtS->close();

        if (!$sol) {
            echo json_encode(['status' => 'error', 'message' => 'No se encontró la solicitud.']);
            exit;
        }

        // Ciclo vigente al momento de la solicitud: empezó antes y todavía no había sido
        // reemplazado por el siguiente.
        $stmtCi = $conn->prepare(
            "SELECT id_ciclo, fecha_inicio, fecha_fin, saldo_inicial, monto_abonado, origen, estatus
             FROM ciclos_tarjeta
             WHERE id_vehiculo = ? AND fecha_inicio <= ?
               AND (fecha_fin IS NULL OR fecha_fin > ?)
             ORDER BY fecha_inicio DESC, id_ciclo DESC LIMIT 1"
        );
        $stmtCi->bind_param("iss", $sol['id_vehiculo'], $sol['fecha'], $sol['fecha']);
        $stmtCi->execute();
        $ciclo = $stmtCi->get_result()->fetch_assoc();
        $stmtCi->close();

        $stmtC = $conn->prepare(
            "SELECT cg.id, cg.monto, cg.pagos, cg.saldo, cg.km_actual,
                    cg.fecha_carga, cg.fecha_registro, cg.ot,
                    -- Datos del destino. `PARQUE-IND` lleva guion en el nombre, así que
                    -- necesita backticks o MySQL lo lee como una resta.
                    cli.CLIENTE AS cliente,
                    cli.CLIENTE_CORTO AS cliente_corto,
                    -- El catálogo usa marcadores para 'sin parque': 77 clientes traen
                    -- '-- y otros solo guiones. Se normalizan a NULL aquí para que la
                    -- vista no tenga que conocer esos valores y no pinte basura.
                    CASE WHEN TRIM(REPLACE(REPLACE(IFNULL(cli.`PARQUE-IND`, ''), '-', ''), '''', '')) = ''
                         THEN NULL ELSE cli.`PARQUE-IND` END AS parque_industrial,
                    cli.CIUDAD AS ciudad,
                    cli.ESTADO AS estado,
                    -- Km recorridos desde la carga anterior del mismo vehículo. Mismo
                    -- cálculo que la columna 'Km Consumidos' de obtenerHistorialGas, para
                    -- que las dos vistas no den cifras distintas. La carga anterior se
                    -- busca por id y no por fecha: la captura en lote deja varias cargas
                    -- con la misma fecha_registro y el id es el único orden estable.
                    -- En la primera carga del vehículo no hay contra qué comparar y queda
                    -- NULL, que la vista pinta con un guion en vez de un 0 engañoso.
                    -- Un resultado NEGATIVO también se descarta: significa que el odómetro
                    -- de alguna de las dos capturas está mal (hay lecturas con un dígito
                    -- de más, como 1,681,614 km, que darían cifras absurdas). Es el mismo
                    -- criterio con el que solicitudes_gas.km_recorrido se deja en null.
                    (SELECT CASE WHEN cg.km_actual - prev.km_actual >= 0
                                 THEN cg.km_actual - prev.km_actual END
                     FROM carga_gasolina prev
                     WHERE prev.id_vehiculo = cg.id_vehiculo AND prev.id < cg.id
                     ORDER BY prev.id DESC LIMIT 1) AS km_recorridos,
                    IFNULL(NULLIF(TRIM(CONCAT(IFNULL(rrhh.nombres,''),' ',IFNULL(rrhh.apellidos,''))),''), u.nombre) AS usuario
             FROM carga_gasolina cg
             LEFT JOIN usuarios u ON u.id = (SELECT MAX(u2.id) FROM usuarios u2 WHERE u2.id_usuario = cg.id_usuario)
             LEFT JOIN mess_rrhh.usuarios rrhh ON rrhh.noEmpleado = u.noEmpleado
             LEFT JOIN clientes cli ON cli.IDCLTE = cg.id_cliente
             WHERE cg.id_ciclo = ?
             ORDER BY cg.fecha_carga DESC, cg.id DESC"
        );
        $idCicloCons = $ciclo ? intval($ciclo['id_ciclo']) : 0;
        $stmtC->bind_param("i", $idCicloCons);
        $stmtC->execute();
        $resC = $stmtC->get_result();
        $cargas = [];
        // Se suma 'pagos', NO 'monto'. En carga_gasolina 'monto' es el saldo que había
        // ANTES de la carga (el monto de cada fila es el saldo de la anterior, y siempre
        // se cumple monto - pagos = saldo), así que sumarlo daba una cifra sin sentido:
        // el acumulado de saldos corrientes, muy por encima de lo realmente gastado.
        $totalGastado = 0.0;
        $kmMin = null; $kmMax = null;
        while ($row = $resC->fetch_assoc()) {
            $cargas[] = $row;
            $totalGastado += floatval($row['pagos']);
            // Km recorridos del ciclo completo: de la lectura más baja a la más alta de
            // sus cargas. Es más robusto que sumar los deltas, porque los deltas que se
            // descartan por odómetro incoherente dejarían huecos en la suma.
            $km = intval($row['km_actual']);
            if ($km > 0) {
                if ($kmMin === null || $km < $kmMin) $kmMin = $km;
                if ($kmMax === null || $km > $kmMax) $kmMax = $km;
            }
        }
        $stmtC->close();

        // Solo se reporta si es coherente: un crédito de $4,000 rinde del orden de 1,500
        // km, así que una cifra desbordada delata una lectura mal capturada.
        $kmCiclo = null;
        if ($kmMin !== null && $kmMax !== null && $kmMax > $kmMin && ($kmMax - $kmMin) <= 10000) {
            $kmCiclo = $kmMax - $kmMin;
        }

        echo json_encode([
            'status'        => 'success',
            'solicitud'     => $sol,
            'ciclo'         => $ciclo ?: null,
            'desde'         => $ciclo['fecha_inicio'] ?? null,
            'hasta'         => $ciclo['fecha_fin'] ?? $sol['fecha'],
            'total_gastado' => $totalGastado,
            'km_ciclo'      => $kmCiclo,
            'cargas'        => $cargas
        ]);
        exit;
    }
?>