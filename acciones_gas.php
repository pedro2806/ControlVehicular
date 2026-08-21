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

        $stmt = $conn->prepare(
            "INSERT INTO carga_gasolina
                (id_usuario, id_vehiculo, monto, pagos, saldo, km_actual, fecha_carga, fecha_registro)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        if (!$stmt) {
            echo json_encode(["status" => "error", "message" => "Error al preparar el registro: " . $conn->error]);
            exit;
        }
        $stmt->bind_param("iidddis", $idUsr, $idVeh, $montoNum, $pagosNum, $saldoNum, $km, $fechaLimpia);
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
                    )) AS km_consumidos
                 FROM carga_gasolina cg
                 INNER JOIN inventario inv ON inv.id_vehiculo = cg.id_vehiculo
                 LEFT JOIN usuarios u ON u.id_usuario = cg.id_usuario
                 LEFT JOIN mess_rrhh.usuarios rrhh ON rrhh.noEmpleado = u.noEmpleado
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

        $stmt2 = $conn->prepare("SELECT placa, modelo, marca FROM inventario WHERE id_vehiculo = ?");
        $stmt2->bind_param("i", $id_vehiculo_req);
        $stmt2->execute();
        $row_v = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();
        $vehiculo_info = $row_v
            ? $row_v['placa'] . ' - ' . $row_v['modelo'] . ' ' . $row_v['marca']
            : 'Vehículo #' . $id_vehiculo_req;

        // Km recorridos con el crédito que se está agotando. El ciclo arranca en la carga
        // donde el monto vuelve al crédito completo (4000, ver verPlaca en
        // js/global/vehiculos.js), así que el recorrido es la diferencia entre la última
        // lectura del odómetro y la de esa carga. Sirve para juzgar si el consumo fue
        // razonable antes de reponer.
        // Se toma la ÚLTIMA lectura por fecha, no MAX(km_actual): hay dedazos en la
        // captura (por ejemplo un 1681614 entre lecturas de ~169000) y con MAX salía un
        // recorrido de más de un millón de kilómetros.
        $stmtKm = $conn->prepare(
            "SELECT
                (SELECT km_actual FROM carga_gasolina
                   WHERE id_vehiculo = ? ORDER BY fecha_carga DESC, id DESC LIMIT 1) AS km_ultimo,
                (SELECT km_actual FROM carga_gasolina
                   WHERE id_vehiculo = ? AND monto >= 4000
                   ORDER BY fecha_carga DESC, id DESC LIMIT 1) AS km_inicio"
        );
        $stmtKm->bind_param("ii", $id_vehiculo_req, $id_vehiculo_req);
        $stmtKm->execute();
        $rowKm = $stmtKm->get_result()->fetch_assoc();
        $stmtKm->close();

        $kmUltimo = isset($rowKm['km_ultimo']) ? intval($rowKm['km_ultimo']) : 0;
        $kmInicio = isset($rowKm['km_inicio']) ? intval($rowKm['km_inicio']) : 0;

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
            : ['cuentasdegastos@mess.com.mx'];

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
                        <td style="padding:10px;border:1px solid #ccc"><strong>Saldo actual</strong></td>
                        <td style="padding:10px;border:1px solid #ccc">$' . number_format($saldo_actual, 2) . '</td>
                    </tr>
                    <tr style="background:#f0f4ff">
                        <td style="padding:10px;border:1px solid #ccc"><strong>Km recorridos con este crédito</strong></td>
                        <td style="padding:10px;border:1px solid #ccc">' . $kmRecorridoTxt . '</td>
                    </tr>
                    <tr>
                        <td style="padding:10px;border:1px solid #ccc"><strong>Kilometraje actual</strong></td>
                        <td style="padding:10px;border:1px solid #ccc">' . $kmUltimoTxt . '</td>
                    </tr>
                    <tr style="background:#f0f4ff">
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
                       inv.placa, inv.marca, inv.modelo,
                       IFNULL(NULLIF(TRIM(CONCAT(IFNULL(rrhh.nombres,''),' ',IFNULL(rrhh.apellidos,''))),''), u.nombre) AS solicitante,
                       ur.nombre AS resolvio
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

        if ($idSol <= 0 || !in_array($resolucion, ['APROBADA', 'RECHAZADA'], true)) {
            echo json_encode(['status' => 'error', 'message' => 'Datos incompletos.']);
            exit;
        }

        // Solo se resuelve lo que sigue pendiente: evita que dos personas la cambien dos
        // veces, o que se reabra algo ya resuelto.
        $idResuelve = intval($_COOKIE['id_usuario'] ?? 0);
        $stmt = $conn->prepare(
            "UPDATE solicitudes_gas
                SET estatus = ?, id_resuelve = ?, fecha_resuelto = NOW(), notas_resolucion = ?
              WHERE id = ? AND estatus = 'PENDIENTE'"
        );
        $stmt->bind_param("sisi", $resolucion, $idResuelve, $notas, $idSol);
        $stmt->execute();
        $cambio = $stmt->affected_rows > 0;
        $stmt->close();

        if (!$cambio) {
            echo json_encode(['status' => 'error', 'message' => 'La solicitud ya fue resuelta o no existe.']);
            exit;
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
    if ($accion == 'verificarChecklistGas') {
        $id_vehiculo_chk = isset($_POST['id_vehiculo']) ? intval($_POST['id_vehiculo']) : 0;
        $id_usuario_chk  = $_COOKIE['id_usuario'] ?? $_COOKIE['id_usuarioL'] ?? null;

        if (!$id_vehiculo_chk || !$id_usuario_chk) {
            echo json_encode(['tiene' => false]);
            exit;
        }

        $stmt = $conn->prepare(
            "SELECT 1 FROM checklist
             WHERE id_vehiculo = ? AND id_usuario = ? AND estatus = 'completo'
             LIMIT 1"
        );
        $stmt->bind_param("ii", $id_vehiculo_chk, $id_usuario_chk);
        $stmt->execute();
        $tiene = (bool) $stmt->get_result()->fetch_assoc();
        $stmt->close();

        echo json_encode(['tiene' => $tiene]);
        exit;
    }
?>