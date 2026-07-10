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

    //FUNCION PARA REGISTRAR CARGA
    if ($accion == 'registraGas'){
        $sqlR = "INSERT INTO carga_gasolina (`id_usuario`, `id_vehiculo`, `monto`, `pagos`, `saldo`, `km_actual`, `fecha_carga`, `fecha_registro`) 
                VALUES ('$id_usuario', '$id_vehiculo', '$monto', '$pagos', '$saldo', '$km_actual', '$fecha_carga', now())";
            
        $resultR = $conn->query($sqlR);
        if($resultR){
            echo json_encode(array("status" => "success", "message" => "Carga de gasolina registrada correctamente."));
        } else {
            echo json_encode(array("status" => "error", "message" => "Error al registrar la carga de gasolina: " . $conn->error));
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

    // Historial completo con km_consumidos calculado
    if ($accion == 'obtenerHistorialGas') {
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
                 ORDER BY cg.id DESC";

        $resultU = $conn->query($sqlU);
        $registros = [];
        if ($resultU) {
            while ($row = $resultU->fetch_assoc()) {
                $registros[] = $row;
            }
            echo json_encode($registros);
        } else {
            echo json_encode(['status' => 'error', 'message' => $conn->error]);
        }
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

        require_once __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
        require_once __DIR__ . '/PHPMailer-master/src/SMTP.php';
        require_once __DIR__ . '/PHPMailer-master/src/Exception.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->IsSMTP();
            $mail->SMTPDebug  = 0;
            $mail->SMTPAuth   = true;
            $mail->SMTPSecure = 'ssl';
            $mail->Host       = 'smtp.gmail.com';
            $mail->Port       = 465;
            $mail->IsHTML(true);
            $mail->CharSet  = 'UTF-8';
            $mail->Username = 'mess.programacion@gmail.com';
            $mail->Password = 'lnevdigasjodzbrq';
            $mail->SetFrom('mess.programacion@gmail.com', 'Control Vehicular');
            $mail->Subject  = 'Solicitud de reposición de crédito de gasolina';
            $mail->addAddress('cuentasdegastos@mess.com.mx');
            $mail->Body = '
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

            $mail->send();
            echo json_encode(['status' => 'success', 'message' => 'Solicitud enviada correctamente al encargado.']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Error al enviar: ' . $e->getMessage()]);
        }
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