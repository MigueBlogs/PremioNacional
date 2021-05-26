<?php
// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
require './vendor/autoload.php';

$smtp_host = '10.2.233.141'; // 10.2.56.25 (SEGOB) 10.2.233.141 (cenapred)
$smtp_user = 'no-reply-dgpc@cenapred.unam.mx';
$smtp_pwd = "2020-dgpc";

function enviarCorreoConfirmacion($correo,$id,$nombre,$tipo,$categoria) {
    global $smtp_host, $smtp_user, $smtp_pwd;
    $mail = new PHPMailer(true);                            // Passing `true` enables exceptions
    try {
        //Server settings
        $mail->CharSet = 'UTF-8';
        //$mail->SMTPDebug = 3;                             // Enable verbose debug output
        $mail->isSMTP();                                    // Set mailer to use SMTP
        $mail->Host = $smtp_host;                         // Specify main and backup SMTP servers
        $mail->SMTPAuth = false;                            // Enable SMTP authentication
        $mail->Port = 25;                                   // TCP port to connect to
        $mail->Username = $smtp_user;
        $mail->Password = $smtp_pwd;
        //Recipients
        $mail->setFrom($smtp_user, 'Premio Nacional 2021');
        $mail->addAddress($correo);     // Add a recipient

        $message = file_get_contents('mail_templates/mail_confirmation.php'); 
        $message = str_replace(':registrationName', $nombre, $message);
        $message = str_replace(':registrationType', $tipo, $message);
        $message = str_replace(':registrationCat', $categoria, $message);
        $message = str_replace(':registrationID', $id, $message);

        //Content
        $mail->isHTML(true);                                  // Set email format to HTML
        $mail->Subject = 'Premio Nacional PC 2021';
        $mail->Body    = $message;
        $mail->AltBody = 'Confirmación de registro al Premio Nacional de Protección Civil 2021';

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("No se pudo enviar correo de confirmación a: ".$correo." . Error en la siguiente linea.");
        error_log($mail->ErrorInfo);
        return false;
    }
}

?>