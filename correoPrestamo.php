<?php
// Asegúrate de que el manejo de errores esté configurado para producción
ini_set('display_errors', 0);
error_reporting(E_ALL);

include 'conn.php'; // Asegúrate que conn.php maneje errores de conexión

// Si usas MySQLi y la conexión está abierta, configura el charset
if ($conn) {
    mysqli_set_charset($conn, "utf8mb4");
} else {
    // Si la conexión falló en conn.php, maneja el error y termina el script
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(["status" => "error", "message" => "Error de conexión a la base de datos."]);
    exit();
}

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8'); // Cambiado a JSON

$solicita = $_COOKIE['noEmpleado'] ?? null; // Usa el operador null coalesce para evitar undefined index

$jefe = null;
$solicitaNombre = null;

// Preparar la consulta para evitar inyecciones SQL, incluso si $solicita no se usa aún
// Asegúrate de que 'noEmpleado' en la tabla 'usuarios' sea un entero si usas 'i'
$sql_jefe = "SELECT (SELECT correo FROM usuarios WHERE noEmpleado = U.jefe) as correoJefe, U.nombre
            FROM usuarios U
            WHERE noEmpleado = ?"; // Usar placeholder para la sentencia preparada

if ($stmt = $conn->prepare($sql_jefe)) {
    // Vincular el parámetro. Usamos 55 para la prueba, pero deberías usar $solicita en producción
    // $employeeIdToQuery = 55; // Para depuración, como tu original.
    $employeeIdToQuery = $solicita; // Usa esta línea en producción después de validar $solicita
    
    // Asegúrate de que $solicita sea un entero si lo vas a usar
    if ($solicita && is_numeric($solicita)) {
        $employeeIdToQuery = (int)$solicita;
    } else {
        // Manejar el caso donde noEmpleado de la cookie no es válido o no existe
        error_log("Cookie 'noEmpleado' inválida o ausente. Usando valor por defecto.");
    }

    $stmt->bind_param("i", $employeeIdToQuery);
    $stmt->execute();
    $resul_jefe = $stmt->get_result();
    
    if ($row2 = $resul_jefe->fetch_assoc()) {
        $jefe = $row2["correoJefe"]; // Usa el correo de la DB
        $solicitaNombre = $row2["nombre"];
    }
    $stmt->close();
} else {
    // Error al preparar la sentencia
    error_log("Error al preparar la consulta SQL para obtener datos del jefe: " . $conn->error);
    echo json_encode(["status" => "error", "message" => "Error interno del servidor."]);
    exit();
}

// Para depuración, sobrescribe el jefe si es necesario
//$jefe = 'pedro.martinez@mess.com.mx'; // Mantener esto para pruebas


// Codificación de caracteres: Asegúrate de que $solicitaNombre ya esté en UTF-8
// Si no lo está, esta conversión sería necesaria, pero intenta evitarla si todo el flujo es UTF-8
// $solicitaN = mb_convert_encoding($solicitaNombre, 'UTF-8', 'ISO-8859-1'); 
// Asumiendo que $solicitaNombre ya es UTF-8
$solicitaN = $solicitaNombre;


$deAsunto = "Notificación del sistema de control vehicular.";

require_once("PHPMailer-master/src/PHPMailer.php");
require_once("PHPMailer-master/src/SMTP.php");
require_once("PHPMailer-master/src/Exception.php"); // Incluir Exception para un mejor manejo de errores

$mail = new PHPMailer\PHPMailer\PHPMailer(true); // Habilitar excepciones

try {
    $mail->IsSMTP();
    $mail->SMTPDebug = 0; // Poner en 0 para producción
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = 'ssl'; // O 'tls' con Port 587
    $mail->Host = "smtp.gmail.com";
    $mail->Port = 465; // Puerto 465 para SSL
    $mail->IsHTML(true);
    $mail->CharSet = 'UTF-8'; // Especificar CharSet para el correo

    $mail->Username = "mess.metrologia@gmail.com";
    // ¡IMPORTANTE! Reemplaza esto por una variable de entorno o un archivo de configuración seguro
    $mail->Password = "hglidvwsxcbbefhe"; 

    $mail->SetFrom("mess.metrologia@gmail.com", "Notificacion");
    $mail->Subject = $deAsunto;

    // Construir el cuerpo del correo. Asumiendo que $solicitaN ya es UTF-8
    $mail->Body = '
    <html>
    <head>
        <center> 
            <img width="25%" id="m_-3753487164271908945_x0000_i1025" src="https://www.mess.com.mx/incidencias/img/MESS_05_Imagotipo_1.png" class="CToWUd a6T" data-bit="iit" tabindex="0">
            <br>
            <hr style="border: 2px solidrgb(24, 60, 165);">
        </center>
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
        <center>
            <h1>
                ' . htmlspecialchars($solicitaN) . ' acaba hacer una solicitud de prestamo a través del sistema de Control Vehicular
            </h1>
            <br><br>
            <h2>
                Para validar la solicitud de prestamo por favor entra al sistema de control vehicular.<br> 
                <a href="https://messbook.com.mx/ControlVehicular"> Ver Solicitud</a>
            </h2>
        </center> <br><br><br><br>
        <center>
            <p>Este es un mensaje autom&aacute;tico, por favor no responda a este correo.</p>
        </center>
    </body>
    </html>';

    // Envío de correo
    if (!empty($jefe)) {
        $correos = $jefe;         
        $correos .= ',rafael@mess.com.mx'; // Correo adicional fijo

        $Arraycorreos = explode(",", $correos); // $jefe ya puede contener multiples correos si la DB los devuelve así.

        foreach ($Arraycorreos as $correo) {
            $correo = trim($correo);
            if (filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $mail->addAddress($correo);
            } else {
                error_log("Correo inválido encontrado: '$correo'");
            }
        }
    } else {
        // Manejar caso donde no hay jefe definido o el correo es nulo
        error_log("No se pudo obtener el correo del jefe o está vacío.");
        echo json_encode(["status" => "error", "message" => "No se pudo determinar el destinatario."]);
        exit();
    }

    $mail->send();
    echo json_encode(["status" => "success", "message" => "Mensaje enviado correctamente."]);

} catch (PHPMailer\PHPMailer\Exception $e) {
    // Captura las excepciones de PHPMailer
    error_log("Mailer Error: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "Fallo al enviar el correo: " . $e->getMessage()]);
} catch (Exception $e) {
    // Captura otras excepciones generales
    error_log("Error general: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "Ocurrió un error inesperado: " . $e->getMessage()]);
}
?>