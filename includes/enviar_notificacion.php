<?php

/**
 * Envío SMTP compartido. Extraído de enviarNotificacionSolicitud() para que la
 * notificación de resolución no duplique la configuración del servidor.
 *
 * NO hace echo ni manda headers a propósito: se llama a mitad de peticiones que
 * devuelven su propio JSON (p. ej. acciones_mantenimiento.php) y cualquier salida
 * aquí corrompería esa respuesta.
 *
 * @return array{ok: bool, error: string}
 */
function enviarCorreoMess(array $destinatarios, $asunto, $cuerpoHtml) {
    require_once(__DIR__ . "/../PHPMailer-master/src/PHPMailer.php");
    require_once(__DIR__ . "/../PHPMailer-master/src/SMTP.php");
    require_once(__DIR__ . "/../PHPMailer-master/src/Exception.php");

    $validos = [];
    foreach ($destinatarios as $correo) {
        $correo = trim((string) $correo);
        if (filter_var($correo, FILTER_VALIDATE_EMAIL)) $validos[] = $correo;
    }
    if (!$validos) return ['ok' => false, 'error' => 'Sin destinatarios válidos.'];

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->IsSMTP();
        $mail->SMTPDebug = 0;
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = 'ssl';
        $mail->Host = "smtp.gmail.com";
        $mail->Port = 465;
        $mail->IsHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Username = "mess.programacion@gmail.com";
        $mail->Password = "lnevdigasjodzbrq";
        $mail->SetFrom("mess.programacion@gmail.com", "Notificacion");
        $mail->Subject = $asunto;
        $mail->Body    = $cuerpoHtml;
        foreach ($validos as $correo) $mail->addAddress($correo);
        $mail->send();
        return ['ok' => true, 'error' => ''];
    } catch (Exception $e) {
        error_log("Mailer Error: " . $e->getMessage());
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Plantilla HTML común (cabecera con imagotipo + banda de color + contenido).
 */
function plantillaCorreoMess($tituloBanda, $colorBanda, $contenidoHtml) {
    return '
    <html>
    <head>
        <meta charset="UTF-8">
        <div style="text-align:center">
            <img width="25%" src="https://www.mess.com.mx/incidencias/img/MESS_05_Imagotipo_1.png">
            <br>
            <hr style="border: 2px solid rgb(24, 60, 165);">
        </div>
    </head>
    <body>
        <div style="background-color:' . $colorBanda . '; color:#ffffff; padding:20px; text-align:center;
                    font-size:22px; font-weight:bold; border-radius:8px 8px 0 0;">
            ' . $tituloBanda . '
        </div>
        <div style="text-align:center">' . $contenidoHtml . '</div>
        <br><br><br><br>
        <div style="text-align:center">
            <p>Este es un mensaje autom&aacute;tico, por favor no responda a este correo.</p>
        </div>
    </body>
    </html>';
}

function enviarNotificacionSolicitud($tipo) {
    include __DIR__ . '/../conn.php';
    mysqli_set_charset($conn, "utf8mb4");

    ini_set('display_errors', 0);
    error_reporting(E_ALL);
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json; charset=UTF-8');

    $solicita = $_COOKIE['noEmpleado'] ?? null;
    $jefe = null;
    $solicitaNombre = null;

    $sql_jefe = "SELECT (SELECT correo FROM usuarios WHERE noEmpleado = U.jefe) as correoJefe, U.nombre
                FROM usuarios U
                WHERE noEmpleado = ?";

    if ($stmt = $conn->prepare($sql_jefe)) {
        $employeeIdToQuery = ($solicita && is_numeric($solicita)) ? (int)$solicita : $solicita;
        $stmt->bind_param("i", $employeeIdToQuery);
        $stmt->execute();
        $resul_jefe = $stmt->get_result();
        if ($row2 = $resul_jefe->fetch_assoc()) {
            $jefe = $row2["correoJefe"];
            $solicitaNombre = $row2["nombre"];
        }
        $stmt->close();
    } else {
        error_log("Error al preparar consulta de jefe: " . $conn->error);
        echo json_encode(["status" => "error", "message" => "Error interno del servidor."]);
        exit();
    }

    $destinatarios = [];
    if ($tipo === 'mantenimiento') {
        $destinatarios[] = 'rafael@mess.com.mx';
    } else {
        $destinatarios[] = 'pedro.martinez@mess.com.mx';
        $destinatarios[] = 'rafael@mess.com.mx';
    }

    $solicitaN = $solicitaNombre;
    $tipoLabel = ($tipo === 'mantenimiento') ? 'mantenimiento' : 'prestamo';

    $contenido = '
        <h1>
            ' . htmlspecialchars($solicitaN) . ' acaba hacer una solicitud de ' . $tipoLabel . ' a través del sistema de Control Vehicular
        </h1>
        <br><br>
        <h2>
            Para validar la solicitud de ' . $tipoLabel . ' por favor entra al sistema de control vehicular.<br>
            <a href="https://messbook.com.mx/ControlVehicular"> Ver Solicitud</a>
        </h2>';

    $res = enviarCorreoMess(
        $destinatarios,
        "Notificación del sistema de control vehicular.",
        plantillaCorreoMess('Aviso de Nueva Solicitud', 'rgb(29, 179, 47)', $contenido)
    );

    if ($res['ok']) {
        echo json_encode(["status" => "success", "message" => "Mensaje enviado correctamente."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Fallo al enviar el correo: " . $res['error']]);
    }
}

/**
 * Aviso de nueva solicitud de PRÉSTAMO, con los datos del préstamo.
 *
 * Sustituye a enviarNotificacionSolicitud('prestamo') para este flujo: aquel manda un
 * correo genérico ("fulano acaba hacer una solicitud") sin decir de qué vehículo ni para
 * cuándo, así que quien autoriza tenía que entrar al sistema para saber qué le pidieron.
 *
 * Se llama desde acciones_prestamos.php justo después del INSERT, que es donde el id ya
 * existe; no hace echo (el que llama emite su propio JSON) y devuelve bool.
 *
 * @param mysqli     $conn
 * @param int        $idPrestamo
 * @param array|null $destinatarios Solo para pruebas: si se omite usa la lista real.
 */
function enviarNotificacionSolicitudPrestamo($conn, $idPrestamo, $destinatarios = null) {
    $idPrestamo = (int) $idPrestamo;
    if ($idPrestamo <= 0) return false;

    // usuarios.id_usuario NO es único: emparejar con la fila de mayor id.
    $sql = "SELECT p.fecha_inc_prestamo, p.fecha_fin_prestamo, p.tipo_uso,
                   p.detalle_tipo_uso, p.Destino,
                   inv.placa, inv.marca, inv.modelo,
                   u.nombre AS nombre_solicitante
            FROM prestamos p
            LEFT JOIN inventario inv ON inv.id_vehiculo = p.id_vehiculo
            LEFT JOIN usuarios u ON u.id = (
                SELECT MAX(u2.id) FROM usuarios u2 WHERE u2.id_usuario = p.id_usuario
            )
            WHERE p.id_prestamo = ?
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) { error_log("Notif. solicitud préstamo: prepare falló - " . $conn->error); return false; }
    $stmt->bind_param("i", $idPrestamo);
    $stmt->execute();
    $d = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$d) { error_log("Notif. solicitud préstamo: $idPrestamo no encontrado"); return false; }

    $esc = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
    $fmt = function ($f) {
        if (empty($f) || $f === '0000-00-00 00:00:00') return '';
        $ts = strtotime($f);
        return $ts ? date('d/m/Y H:i', $ts) : '';
    };

    $vehiculo = trim(($d['marca'] ?? '') . ' ' . ($d['modelo'] ?? '')) . ' — ' . ($d['placa'] ?? 'S/P');

    // tipo_uso trae la clase (OV/OT/Proyecto) y detalle_tipo_uso el número; juntos son
    // lo que identifica el trabajo, así que se muestran en una sola línea.
    $uso = trim((string) $d['tipo_uso']);
    if (!empty($d['detalle_tipo_uso'])) $uso = trim($uso . ' — ' . $d['detalle_tipo_uso']);

    $filas = '<tr><td align="right"><b>Vehículo:</b></td><td align="left">' . $esc($vehiculo) . '</td></tr>';
    $desde = $fmt($d['fecha_inc_prestamo']);
    $hasta = $fmt($d['fecha_fin_prestamo']);
    if ($desde) $filas .= '<tr><td align="right"><b>Desde:</b></td><td align="left">' . $esc($desde) . '</td></tr>';
    if ($hasta) $filas .= '<tr><td align="right"><b>Hasta:</b></td><td align="left">' . $esc($hasta) . '</td></tr>';
    if (!empty($d['Destino'])) $filas .= '<tr><td align="right"><b>Destino:</b></td><td align="left">' . $esc($d['Destino']) . '</td></tr>';
    if ($uso !== '')           $filas .= '<tr><td align="right"><b>Tipo de uso:</b></td><td align="left">' . $esc($uso) . '</td></tr>';

    $contenido = '
        <h2>' . $esc($d['nombre_solicitante']) . ' solicitó un préstamo de vehículo</h2>
        <table style="margin:0 auto; font-size:15px; line-height:1.6">' . $filas . '</table>
        <br>
        <h3 style="font-weight:normal">
            Para validarla entra al sistema de control vehicular.<br>
            <a href="https://messbook.com.mx/ControlVehicular/autorizar_prestamo">Ver solicitud</a>
        </h3>';

    $res = enviarCorreoMess(
        $destinatarios ?: ['pedro.martinez@mess.com.mx', 'rafael@mess.com.mx'],
        'Nueva solicitud de préstamo — Control Vehicular',
        plantillaCorreoMess('Aviso de Nueva Solicitud', 'rgb(29, 179, 47)', $contenido)
    );

    if (!$res['ok']) error_log("Notif. solicitud préstamo $idPrestamo: " . $res['error']);
    return $res['ok'];
}

/**
 * Avisa al SOLICITANTE que su mantenimiento fue autorizado o denegado.
 *
 * Antes no existía ningún aviso al resolver: autorizarMantenimiento solo hacía el
 * UPDATE, así que el solicitante tenía que entrar a revisar por su cuenta.
 *
 * Recibe $conn ya abierto (lo llama acciones_mantenimiento.php) para no abrir una
 * segunda conexión, y devuelve bool sin imprimir nada: el que llama ya emite su
 * propio JSON.
 *
 * @param mysqli $conn
 * @param int    $idMantenimiento
 * @param string $resolucion 'AUTORIZADO' | 'DENEGADO'
 */
function enviarNotificacionResolucionMantenimiento($conn, $idMantenimiento, $resolucion) {
    $idMantenimiento = (int) $idMantenimiento;
    if ($idMantenimiento <= 0) return false;

    $esAutorizado = (strtoupper($resolucion) === 'AUTORIZADO');

    // usuarios.id_usuario NO es único: hay que quedarse con la fila de mayor id o el
    // JOIN devuelve varias y el correo puede salir vacío o duplicado.
    $sql = "SELECT m.tipo_mantenimiento, m.descripcion, m.fecha_programada, m.folio,
                   m.costo, m.notas, m.kilometraje,
                   inv.placa, inv.marca, inv.modelo,
                   u.nombre AS nombre_solicitante, u.correo AS correo_solicitante
            FROM mantenimientos m
            LEFT JOIN inventario inv ON inv.id_vehiculo = m.id_vehiculo
            LEFT JOIN usuarios u ON u.id = (
                SELECT MAX(u2.id) FROM usuarios u2 WHERE u2.id_usuario = m.solicitante
            )
            WHERE m.id_mantenimiento = ?
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) { error_log("Notif. resolución: prepare falló - " . $conn->error); return false; }
    $stmt->bind_param("i", $idMantenimiento);
    $stmt->execute();
    $datos = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$datos) { error_log("Notif. resolución: mantenimiento $idMantenimiento no encontrado"); return false; }
    if (empty($datos['correo_solicitante'])) {
        error_log("Notif. resolución: el solicitante del mantenimiento $idMantenimiento no tiene correo");
        return false;
    }

    $esc = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
    $vehiculo = trim($datos['marca'] . ' ' . $datos['modelo']) . ' — ' . $datos['placa'];

    $filas = '<tr><td align="right"><b>Vehículo:</b></td><td align="left">' . $esc($vehiculo) . '</td></tr>'
           . '<tr><td align="right"><b>Tipo:</b></td><td align="left">' . $esc($datos['tipo_mantenimiento']) . '</td></tr>';
    if ($esAutorizado) {
        if (!empty($datos['fecha_programada'])) $filas .= '<tr><td align="right"><b>Fecha programada:</b></td><td align="left">' . $esc($datos['fecha_programada']) . '</td></tr>';
        if (!empty($datos['folio']))            $filas .= '<tr><td align="right"><b>Folio OC:</b></td><td align="left">' . $esc($datos['folio']) . '</td></tr>';
        // El costo NO se incluye a propósito: es información interna que no le aporta
        // nada al solicitante (decisión de la junta del 20/08).
    }
    if (!empty($datos['notas'])) $filas .= '<tr><td align="right"><b>Notas:</b></td><td align="left">' . $esc($datos['notas']) . '</td></tr>';

    // "RECHAZADO" es solo la etiqueta que ve el usuario; en la BD el valor sigue siendo
    // 'DENEGADO', que es lo que comparan autorizar_prestamo.php, qr_vehiculo.php y
    // acciones_prestamos.php. Mismo criterio que verActividades.php, que ya muestra
    // 'DENEGADO' como "Cancelado".
    $titulo = $esAutorizado ? 'Mantenimiento autorizado' : 'Mantenimiento rechazado';
    $color  = $esAutorizado ? 'rgb(29, 179, 47)' : 'rgb(200, 45, 45)';
    $frase  = $esAutorizado
        ? 'Tu solicitud de mantenimiento fue <b>autorizada</b>.'
        : 'Tu solicitud de mantenimiento fue <b>rechazada</b>.';

    $contenido = '
        <h2>Hola ' . $esc($datos['nombre_solicitante']) . ',</h2>
        <h3 style="font-weight:normal">' . $frase . '</h3>
        <table style="margin:0 auto; font-size:15px; line-height:1.6">' . $filas . '</table>
        <br>
        <h3 style="font-weight:normal">
            Puedes consultarla en el sistema de control vehicular.<br>
            <a href="https://messbook.com.mx/ControlVehicular/seguimiento_mantenimiento">Ver mantenimiento</a>
        </h3>';

    $res = enviarCorreoMess(
        [$datos['correo_solicitante']],
        $titulo . ' — Control Vehicular',
        plantillaCorreoMess($titulo, $color, $contenido)
    );

    if (!$res['ok']) error_log("Notif. resolución mantenimiento $idMantenimiento: " . $res['error']);
    return $res['ok'];
}
