<?php
// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
require './vendor/autoload.php';

function enviarCorreoConfirmacion($correo, $id_inmueble, $lugar) {
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
        $mail->setFrom('no-reply-dgpc@segob.gob.mx', 'Macrosimulacro 2020');
        $mail->addAddress($correo);     // Add a recipient

        $message = file_get_contents('mail_templates/mail_confirmation.php'); 
        $message = str_replace(':registrationNumber', $id_inmueble, $message);
        $message = str_replace(':registrationAddress', $lugar, $message);

        //Content
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = 'Macrosimulacro Mayo 2020';
        $mail->Body    = $message;
        $mail->AltBody = 'Confirmacion de registro de inmueble para el macrosimulacro';

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("No se pudo enviar correo de confirmación a: ".$correo." (ID: ".$id_inmueble."). Error en la siguiente línea.");
        error_log($mail->ErrorInfo);
        return false;
    }
}
function enviarCorreoConfirmacionFederal($correo, $id_inmueble, $lugar) {
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
        $mail->setFrom('no-reply-dgpc@segob.gob.mx', 'Macrosimulacro 2020');
        $mail->addAddress($correo);     // Add a recipient

        $message = file_get_contents('mail_templates/mail_confirmation_federal.php'); 
        $message = str_replace(':registrationNumber', $id_inmueble, $message);
        $message = str_replace(':registrationAddress', $lugar, $message);

        //Content
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = 'Macrosimulacro Mayo 2020';
        $mail->Body    = $message;
        $mail->AltBody = 'Confirmacion de registro de inmueble para el macrosimulacro';

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("No se pudo enviar correo de confirmación a: ".$correo." (ID: ".$id_inmueble."). Error en la siguiente línea.");
        error_log($mail->ErrorInfo);
        return false;
    }
}
?>