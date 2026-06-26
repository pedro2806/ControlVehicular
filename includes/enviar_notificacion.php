<?php
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

    require_once(__DIR__ . "/../PHPMailer-master/src/PHPMailer.php");
    require_once(__DIR__ . "/../PHPMailer-master/src/SMTP.php");
    require_once(__DIR__ . "/../PHPMailer-master/src/Exception.php");

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
        $mail->Username = "mess.metrologia@gmail.com";
        $mail->Password = "hglidvwsxcbbefhe";
        $mail->SetFrom("mess.metrologia@gmail.com", "Notificacion");
        $mail->Subject = "Notificación del sistema de control vehicular.";

        $mail->Body = '
        <html>
        <head>
            <div style="text-align:center">
                <img width="25%" src="https://www.mess.com.mx/incidencias/img/MESS_05_Imagotipo_1.png">
                <br>
                <hr style="border: 2px solidrgb(24, 60, 165);">
            </div>
            <meta charset="UTF-8">
            <style>
                .header {
                    background-color:rgb(29, 179, 47);
                    color: #ffffff;
                    padding: 20px;
                    text-align: center;
                    font-size: 22px;
                    font-weight: bold;
                    border-radius: 8px 8px 0 0;
                }
            </style>
        </head>
        <body>
            <div class="header">
                Aviso de Nueva Solicitud
            </div>
            <div style="text-align:center">
                <h1>
                    ' . htmlspecialchars($solicitaN) . ' acaba hacer una solicitud de ' . $tipoLabel . ' a través del sistema de Control Vehicular
                </h1>
                <br><br>
                <h2>
                    Para validar la solicitud de ' . $tipoLabel . ' por favor entra al sistema de control vehicular.<br>
                    <a href="https://messbook.com.mx/ControlVehicular"> Ver Solicitud</a>
                </h2>
            </div> <br><br><br><br>
            <div style="text-align:center">
                <p>Este es un mensaje autom&aacute;tico, por favor no responda a este correo.</p>
            </div>
        </body>
        </html>';

        foreach ($destinatarios as $correo) {
            $correo = trim($correo);
            if (filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $mail->addAddress($correo);
            }
        }

        $mail->send();
        echo json_encode(["status" => "success", "message" => "Mensaje enviado correctamente."]);

    } catch (PHPMailer\PHPMailer\Exception $e) {
        error_log("Mailer Error: " . $e->getMessage());
        echo json_encode(["status" => "error", "message" => "Fallo al enviar el correo: " . $e->getMessage()]);
    } catch (Exception $e) {
        error_log("Error general: " . $e->getMessage());
        echo json_encode(["status" => "error", "message" => "Ocurrió un error inesperado: " . $e->getMessage()]);
    }
}
