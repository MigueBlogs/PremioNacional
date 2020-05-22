<?php
// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
require './vendor/autoload.php';

function enviarCorreoConfirmacion($correo, $nombre) {
    $mail = new PHPMailer(true);                            // Passing `true` enables exceptions
    try {
        //Server settings
        $mail->CharSet = 'UTF-8';
        //$mail->SMTPDebug = 3;                             // Enable verbose debug output
        $mail->isSMTP();                                    // Set mailer to use SMTP
        $mail->Host = '10.2.56.25';                         // Specify main and backup SMTP servers
        $mail->SMTPAuth = false;                            // Enable SMTP authentication
        $mail->Port = 25;                                   // TCP port to connect to

        //Recipients
        $mail->setFrom('no-reply-dgpc@segob.gob.mx', 'Premio Nacional 2020');
        $mail->addAddress($correo);     // Add a recipient

        $message = file_get_contents('mail_templates/mail_confirmation.php'); 
        $message = str_replace(':registrationName', $nombre, $message);

        //Content
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = 'Premio Nacional PC 2020';
        $mail->Body    = $message;
        $mail->AltBody = 'Confirmacion de registro al Premio Nacional de Protección Civil 2020';

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("No se pudo enviar correo de confirmación a: ".$correo." . Error en la siguiente linea.");
        error_log($mail->ErrorInfo);
        return false;
    }
}

?>