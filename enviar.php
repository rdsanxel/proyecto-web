<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Habilitar reporte de errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Protección contra envíos demasiado frecuentes
session_start();
if (isset($_SESSION['last_submit']) && (time() - $_SESSION['last_submit'] < 30)) {
    http_response_code(429);
    echo "Por favor, espera al menos 30 segundos antes de enviar otra solicitud.";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Protección contra bots (honeypot)
    if (!empty($_POST['website'])) {
        http_response_code(400);
        echo "Acceso denegado.";
        exit;
    }
    
    // Validar y sanitizar los datos del formulario
    $nombre = filter_var(trim($_POST['nombre']), FILTER_SANITIZE_STRING);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $telefono = filter_var(trim($_POST['telefono']), FILTER_SANITIZE_STRING);
    $fecha_nacimiento = filter_var(trim($_POST['fecha_nacimiento']), FILTER_SANITIZE_STRING);
    $domicilio = filter_var(trim($_POST['domicilio']), FILTER_SANITIZE_STRING);
    $ingresos_mensuales = filter_var(trim($_POST['ingresos_mensuales']), FILTER_SANITIZE_NUMBER_INT);
    $monto = filter_var(trim($_POST['monto']), FILTER_SANITIZE_NUMBER_INT);
    $plazo = filter_var(trim($_POST['plazo']), FILTER_SANITIZE_STRING);
    $motivo = filter_var(trim($_POST['motivo']), FILTER_SANITIZE_STRING);
    
    // Validar campos obligatorios
    if (empty($nombre) || empty($email) || empty($telefono) || empty($fecha_nacimiento) || 
        empty($domicilio) || empty($ingresos_mensuales) || empty($monto) || empty($plazo)) {
        http_response_code(400);
        echo "Por favor, complete todos los campos obligatorios.";
        exit;
    }
    
    // Validar formato de email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo "Por favor, ingrese un correo electrónico válido.";
        exit;
    }
    
    // Validar fecha de nacimiento (mayor de 18 años)
    $fecha_nac = DateTime::createFromFormat('Y-m-d', $fecha_nacimiento);
    $hoy = new DateTime();
    $edad = $hoy->diff($fecha_nac)->y;
    
    if ($edad < 18) {
        http_response_code(400);
        echo "Debes ser mayor de 18 años para solicitar un préstamo.";
        exit;
    }

    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP de Gmail
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'rdsnxel@gmail.com';  // Tu correo de Gmail
        $mail->Password = 'aquí_va_tu_contraseña_de_aplicación';  // Contraseña de aplicación de Gmail
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Configurar codificación de caracteres
        $mail->CharSet = 'UTF-8';

        // Remitente y destinatario
        $mail->setFrom('rdsnxel@gmail.com', 'Quiterio Crédito');
        $mail->addAddress('rdsnxel@gmail.com');  // Aquí recibirás las solicitudes
        $mail->addReplyTo($email, $nombre);  // Para responder directamente al solicitante
        
        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = 'Nueva solicitud de préstamo - ' . $nombre;
        $mail->Body = "
            <h2 style='color: #002f6c;'>Nueva solicitud de préstamo recibida</h2>
            <p><strong>Nombre:</strong> $nombre</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Teléfono:</strong> $telefono</p>
            <p><strong>Fecha de nacimiento:</strong> $fecha_nacimiento</p>
            <p><strong>Domicilio:</strong> $domicilio</p>
            <p><strong>Ingresos mensuales:</strong> $$ingresos_mensuales MXN</p>
            <p><strong>Monto solicitado:</strong> $$monto MXN</p>
            <p><strong>Plazo de pago:</strong> $plazo meses</p>
            <p><strong>Motivo del préstamo:</strong> " . (!empty($motivo) ? $motivo : 'No especificado') . "</p>
            <br>
            <p><em>Este mensaje fue enviado desde el formulario de contacto de Quiterio Crédito</em></p>
        ";
        
        // Versión alternativa en texto plano
        $mail->AltBody = "Nueva solicitud de préstamo:\n\nNombre: $nombre\nEmail: $email\nTeléfono: $telefono\nFecha de nacimiento: $fecha_nacimiento\nDomicilio: $domicilio\nIngresos mensuales: $$ingresos_mensuales MXN\nMonto solicitado: $$monto MXN\nPlazo: $plazo meses\nMotivo: " . (!empty($motivo) ? $motivo : 'No especificado');

        // Enviar el correo
        $mail->send();
        
        // Registrar el tiempo de envío para prevenir spam
        $_SESSION['last_submit'] = time();
        
        // Respuesta de éxito
        http_response_code(200);
        echo "¡Solicitud enviada con éxito! Nos pondremos en contacto contigo en menos de 24 horas.";
        
    } catch (Exception $e) {
        // Manejo de errores
        http_response_code(500);
        echo "Lo sentimos, ha ocurrido un error al procesar tu solicitud. Por favor, intenta nuevamente más tarde.";
        // Para depuración, puedes descomentar la siguiente línea:
        // echo " Error: {$mail->ErrorInfo}";
    }
} else {
    // Si se accede al script directamente sin enviar el formulario
    http_response_code(403);
    echo "Acceso denegado.";
}
?>