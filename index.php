<?php
    $today = time();
    $date_start = strtotime('2020-05-29 05:00:00.0');  // UTC for 29 May, 00:00 GMT-5 (Mexico City)
    $date_end = strtotime('2020-12-31 05:00:00.0');  // UTC for 31 Dic, 00:00 GMT-5 (Mexico City)
    $available = $today - $date_start >= 0 ? true : false;
    $expired = $today - $date_end >= 0 ? true: false;
    // if ($available == false || $expired) {
    //     header("Location: http://www.preparados.gob.mx/");
    //     die();
    // }
    // Verifica si el navegador es Internet Explorer y lo bloquea por razones de compatibilidad
    if (preg_match("/MSIE /",getenv("HTTP_USER_AGENT")) || preg_match("/Trident\//",getenv("HTTP_USER_AGENT"))) { ?>
        <div id="div-ie-error" style="background-color: lightcoral; text-align: center;">
            <img style="max-width: 100px;" src="img/ie9.png" alt="Internet Explorer 9">
            <p style="width: 100%;">Internet Explorer no está soportado en esta página, por favor utiliza otro navegador web como 
            <a target="_blank" href="https://www.google.com/intl/es-419/chrome/">Google Chrome</a> o 
            <a target="_blank" href="https://www.mozilla.org/es-MX/firefox/new/">Firefox</a>. 
            También puedes entrar desde tu dispositivo móvil.</p>
        </div>
    <?php 
        die();
    }


    $keep = false;
    $allowTypes = array('zip','rar');
    $max_file_size = 10485760; // 10MB
    $target_dir = "/var/www/html/uploads/premionacional2020/";
    require_once("premio_fns.php");
    if (
        isset($_POST["nombre"]) && 
        isset($_POST["correo"]) && 
        isset($_FILES["archivo"]) && 
        isset($_POST["estado"]) && 
        isset($_POST["municipio"])) 
    {
        sendForm();    
    }

    function SendForm() {
        global $target_dir, $error_msg, $success_msg, $keep;

        $nombre = substr(trim($_POST['nombre']), 0, 512);
        $correo =  mb_strtolower(substr(trim($_POST['correo']), 0, 128));
        $telefono = isset($_POST["telefono"]) ? substr(trim($_POST['telefono']), 0, 10) : null;
        $estado = intval($_POST['estado']);
        $municipio = $_POST['municipio'];  // debe ser char para respetar 0 al inicio

        if (validateForm($nombre, $correo, $telefono, $estado, $municipio)){
            
            $imageFileType = strtolower(pathinfo($target_dir . basename($_FILES['archivo']['name']), PATHINFO_EXTENSION));

            $tmp_uid = uniqid();
            $target_file = $target_dir . $tmp_uid . "." . $imageFileType;
            $url_file = "http://www.preparados.gob.mx/uploads/premionacional2020/". $tmp_uid . "." . $imageFileType;
            if (!move_uploaded_file($_FILES["archivo"]["tmp_name"], $target_file)){
                $error_msg = 'No se pudo subir tu archivo al servidor';
                $keep = true;
                return;
            }
            
            $datos = [
                ":nombre"=>$nombre,
                ":correo"=>$correo,
                ":telefono"=>$telefono,
                ":archivo"=>$url_file,
                ":estado"=>$estado,
                ":municipio"=>$municipio
            ];
            if (registrar($datos)) {
                $tmp = getUltimoRegistro($correo);
                if (enviarCorreoConfirmacion($correo, $nombre)){
                    $success_msg = 'Registro realizado correctamente. Se ha enviado exitosamente un correo confirmando tu registro';
                }
                else {
                    $success_msg = 'Registro realizado correctamente';
                    $error_msg = 'No se pudo enviar el correo de confirmación para la dirección que ingresaste.';
                }
            }
            else {
                unlink($target_file);
                $error_msg = 'No se pudo realizar el registro de tu solicitud';
            }

        }
        if ($error_msg) $keep = true;
    }

    function validateForm($nombre, $correo, $telefono, $estado, $municipio) {
        global $error_msg, $allowTypes, $max_file_size, $target_dir;

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $error_msg = 'El correo electrónico es inválido. Verifíca que esté bien escrito';
            return false;
        }
        if ($estado < 1 || $estado > 32){
            $error_msg = 'El estado seleccionado es inválido.';
            return false;
        }
        
        if ($_FILES['archivo']['error'] != UPLOAD_ERR_OK){
            error_log('Error UPLOAD '.$_FILES['archivo']['error']);
            $error_msg = 'No se pudo subir tu archivo';
            return false;
        }
        $fileType = strtolower(pathinfo($target_dir . basename($_FILES['archivo']['name']), PATHINFO_EXTENSION));
        if(!in_array($fileType, $allowTypes)) {
            // not valid extension
            $error_msg = "Extensión de archivo inválida. Debe ser de tipo zip o rar";
            return false;
        }
        // Check file size
        if ($_FILES["archivo"]["size"] > $max_file_size) {
            // file too large
            $error_msg = "Tamaño de archivo muy grande";
            return false;
        }
        $fh = @fopen($_FILES["archivo"]["tmp_name"], "r");

        if (!$fh) {
            fclose($fh);
            $error_msg = 'No se pudo subir tu archivo.';
            return false;
        }

        $blob = fgets($fh, 5);  // Reads first 5 bytes

        fclose($fh);

        if (strpos($blob, 'Rar') !== false) {
            // Is RAR
        } 
        else if (strpos($blob, 'PK') !== false) {
            // Is ZIP
        } else {
            $error_msg = 'El archivo subido no es válido';
            return false;
        }
        return true;
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="shortcut icon" href="http://www.atlasnacionalderiesgos.gob.mx/Imagenes/Logos/cenapred_icon.ico"/>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1.0, maximum-scale=1.0">
    <title>Premio Nacional 2020</title>
    
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <!-- Materialize -->
        <!-- Compiled and minified CSS -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
        <!-- Compiled and minified JavaScript -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <!-- Iconos -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <link rel="stylesheet" href="./CSS/main.css">

    <noscript>
        <div id="div-js-error" class="valign-wrapper center" style="background-color: lightcoral;">
            <i class="material-icons alerted" style="margin-left: 1em;">error_outline</i>
            <p style="width: 100%;">JavaScript está deshabilitado, esta página necestia tener activado JavaScript para que funcione correctamente.</p>
            <button id="btn-error-close" class="btn-small" type="button" style="margin-right: 1em;"><i class="material-icons">close</i></button>
        </div>
    </noscript>
    <style>
        p.MsoNormal, li.MsoNormal, div.MsoNormal
        {mso-style-unhide:no;
        mso-style-parent:"";
        margin-top:0cm;
        margin-right:0cm;
        margin-bottom:10.0pt;
        margin-left:0cm;
        line-height:115%;
        mso-pagination:widow-orphan;
        font-size:11.0pt;
        font-family:"Calibri",sans-serif;
        mso-fareast-font-family:Calibri;
        mso-bidi-font-family:Calibri;}

        tr {
            border-bottom: unset;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="center">
            <img class="cnpc" src="http://www.atlasnacionalderiesgos.gob.mx/Imagenes/Logos/SSyPC_CNPC_h.png" alt="Gobierno de México">
        </div>
        <h1 class="flow-text center">Premio Nacional de Protección Civil 2020</h1>
        <hr>
        <div class="fixed-action-btn" style="position: fixed; bottom: 5em;">
            <a target="_blank" href="http://www.preparados.gob.mx/blog" class="btn-floating btn blue"><i class="material-icons">help</i></a>
        </div>

        <?php if (isset($success_msg) && $success_msg) { ?>
            <div id="div-success" class="valign-wrapper center green lighten-2">
                <i class="material-icons alerted" style="margin-left: 1em;">error_outline</i>
                <p style="width: 100%;"><?=$success_msg?></p>
                <button id="btn-success-close" class="btn" type="button" style="margin: 0.4rem;"><i class="material-icons">close</i></button>
            </div>
        <?php } ?>

        <?php if (isset($error_msg) && $error_msg) { ?>
            <div id="div-error" class="valign-wrapper center red lighten-1">
                <i class="material-icons alerted" style="margin-left: 1em;">error_outline</i>
                <p style="width: 100%;"><?=$error_msg?></p>
                <button id="btn-error-close" class="btn" type="button" style="margin: 0.4rem;"><i class="material-icons">close</i></button>
            </div>
        <?php } ?>
        <div id="instrucciones">
            <ul class="collapsible popout">
                <li class="active">
                    <div class="collapsible-header mainIcons"><i class="material-icons">lightbulb_outline</i>Lee las siguientes instrucciones para realizar el registro de tu candidatura</div>
                    <div class="collapsible-body"><span>Bienvenido al portal de registro de Candidaturas para el Premio Nacional de Protección Civil, para iniciar deberás leer completamente las instrucciones siguientes y a continuación hacer clic sobre el botón de “INICIAR REGISTRO”, a continuación deberás ingresar tus datos de contacto como: Nombre completo (<b>obligatorio</b>), correo electrónico (<b>obligatorio</b>) y un Número telefónico de contacto (opcional), dicho contacto deberá ser responsable del proyecto en caso de persona física. Si el registro es por parte de un Grupo Voluntario se deberá de colocar el nombre de un solo representante, esto con el fin de mantener contacto y tener referencia de la persona que ha postulado la candidatura.</span></div>
                </li>
                <li>
                    <div class="collapsible-header mainIcons"><i class="material-icons">subject</i>Bases del concurso</div>
                    <div class="collapsible-body">
                        <span>
                            <p class=MsoNormal align=center style='margin-right:-4.65pt;text-align:center;
                            text-indent:.2pt'><b style='mso-bidi-font-weight:normal'><span
                            style='font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'>BASES<o:p></o:p></span></b></p>
                            <p class=MsoNormal align=center style='margin-right:-4.65pt;text-align:center;
                            text-indent:.2pt'><b style='mso-bidi-font-weight:normal'><span
                            style='font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'><o:p>&nbsp;</o:p></span></b></p>

                            <p class=MsoNormal style='text-align:justify;text-indent:.2pt'><b
                            style='mso-bidi-font-weight:normal'><span style='font-size:10.0pt;line-height:
                            115%;font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'>Primera.</span></b><span style='font-size:10.0pt;line-height:115%;
                            font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'> El Premio Nacional de Protección Civil 2020, será conferido y
                            entregado a aquellas personas físicas o grupos voluntarios, mexicanos o
                            mexicanas que representen un ejemplo para la comunidad, por su esfuerzo en
                            acciones o medidas de autoprotección y autopreparación para enfrentar los
                            fenómenos naturales o de origen humano que pongan a la población en situación
                            de riesgo o de peligro, así como cuando se destaquen por su labor ejemplar en
                            la ayuda a la población ante la eventualidad de un desastre.</span><span
                            style='font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'><o:p></o:p></span></p>
                            <p class=MsoNormal style='text-align:justify;text-indent:.2pt'><b
                            style='mso-bidi-font-weight:normal'><span style='font-size:10.0pt;line-height:
                            115%;font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'>Segunda.</span></b><span style='font-size:10.0pt;line-height:115%;
                            font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'> En los términos del artículo 103 de la Ley de Premios, Estímulos y
                            Recompensas Civiles, los dos campos de este premio, en los que se puede
                            participar individual o colectivamente, son los siguientes:<o:p></o:p></span></p>

                            <p class=MsoNormal style='margin-left:.2pt;text-align:justify'><span
                            style='font-size:10.0pt;line-height:115%;font-family:Montserrat;mso-fareast-font-family:
                            Montserrat;mso-bidi-font-family:Montserrat'>I.- La Prevención, por las medidas
                            que se consideren de relevancia en materia de gestión integral de riesgos de
                            desastres, y<o:p></o:p></span></p>

                            <p class=MsoNormal style='text-align:justify;tab-stops:288.0pt'><span
                            style='font-size:10.0pt;line-height:115%;font-family:Montserrat;mso-fareast-font-family:
                            Montserrat;mso-bidi-font-family:Montserrat'>II.- La Ayuda, por las acciones que
                            se hayan llevado a cabo en las tareas de auxilio a la población en caso de
                            desastre.<o:p></o:p></span></p>

                            <p class=MsoNormal style='text-align:justify;text-indent:.2pt'><b
                            style='mso-bidi-font-weight:normal'><span style='font-size:10.0pt;line-height:
                            115%;font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'>Tercera.</span></b><span style='font-size:10.0pt;line-height:115%;
                            font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'> Podrán ser beneficiarias del Premio Nacional de Protección Civil
                            2020, personas físicas o grupos voluntarios, mexicanos o mexicanas, que se
                            hayan significado en los campos mencionados en la base segunda de esta
                            Convocatoria. <o:p></o:p></span></p>

                            <p class=MsoNormal style='text-align:justify;text-indent:.2pt'><b
                            style='mso-bidi-font-weight:normal'><span style='font-size:10.0pt;line-height:
                            115%;font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'>Cuarta. </span></b><span style='font-size:10.0pt;line-height:115%;
                            font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'>Conforme a los artículos 4° y 92 de la Ley de Premios, Estímulos y
                            Recompensas Civiles, no podrán participar las personas que tengan la calidad de
                            servidores públicos seleccionados de entre aquéllos que prestan sus servicios
                            en las dependencias y entidades cuyas relaciones laborales se rigen por el
                            apartado &quot;B&quot; del artículo 123 de la Constitución Política de los
                            Estados Unidos Mexicanos.<o:p></o:p></span></p>

                            <p class=MsoNormal style='text-align:justify;text-indent:.2pt'><b
                            style='mso-bidi-font-weight:normal'><span style='font-size:10.0pt;line-height:
                            115%;font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'>Quinta.</span></b><span style='font-size:10.0pt;line-height:115%;
                            font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'> Las producciones, estudios, programas o acciones para salvaguardar
                            la vida, integridad y salud de la población, así como sus bienes; la
                            infraestructura, la planta productiva y el medio ambiente que fortalezca el
                            Sistema Nacional de Protección Civil y que acrediten el merecimiento del Premio
                            Nacional de Protección Civil 2020, deberán ser expresión de acciones realizadas
                            durante el período comprendido del 01 de enero de 2019 hasta la fecha en que se
                            publique la presente Convocatoria y que hayan significado un impacto a la
                            Protección Civil Nacional, así mismo, las personas o grupos voluntarios hayan
                            sido premiados en pasadas ediciones del Premio Nacional de Protección Civil, no
                            podrán ser postulados nuevamente.<o:p></o:p></span></p>

                            <p class=MsoNormal style='text-align:justify'><b style='mso-bidi-font-weight:
                            normal'><span style='font-size:10.0pt;line-height:115%;font-family:Montserrat;
                            mso-fareast-font-family:Montserrat;mso-bidi-font-family:Montserrat'>Sexta.</span></b><span
                            style='font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'> </span><span style='font-size:10.0pt;line-height:115%;font-family:
                            Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:Montserrat'>Conforme
                            lo establecido en los artículos 7, fracción IV, 8, 11, y 104 de la Ley de
                            Premios, Estímulos y Recompensas Civiles, el premio para cada uno de los dos
                            campos consistirá en un diploma firmado por el Presidente de los Estados Unidos
                            Mexicanos, por las y los miembros del Consejo de Premiación del Premio Nacional
                            de Protección Civil 2020 y por las y los integrantes del Jurado.<o:p></o:p></span></p>

                            <p class=MsoNormal style='text-align:justify;text-indent:.2pt'><span
                            style='font-size:10.0pt;line-height:115%;font-family:Montserrat;mso-fareast-font-family:
                            Montserrat;mso-bidi-font-family:Montserrat'>El Premio Nacional de Protección
                            Civil 2020 podrá otorgarse en un mismo campo a dos o más personas físicas o
                            grupos voluntarios, mexicanos o mexicanas, en el caso de las personas físicas
                            en grupo, solo se entregará un diploma.<o:p></o:p></span></p>

                            <p class=MsoNormal style='text-align:justify;text-indent:.2pt'><b
                            style='mso-bidi-font-weight:normal'><span style='font-size:10.0pt;line-height:
                            115%;font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'>Séptima</span></b><b style='mso-bidi-font-weight:normal'><span
                            style='font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'>.</span></b><span style='font-family:Montserrat;mso-fareast-font-family:
                            Montserrat;mso-bidi-font-family:Montserrat'> </span><span style='font-size:
                            10.0pt;line-height:115%;font-family:Montserrat;mso-fareast-font-family:Montserrat;
                            mso-bidi-font-family:Montserrat'>Para la entrega del Premio, en caso de que la
                            candidatura ganadora incluya a un grupo de personas físicas, que cuente con más
                            de cuatro integrantes, se deberá nombrar un o una representante común para
                            recibir el premio. Para el caso de los grupos voluntarios, el premio se otorgará
                            por medio de su representante legal.<o:p></o:p></span></p>

                            <p class=MsoNormal style='margin-bottom:0cm;margin-bottom:.0001pt;text-align:
                            justify;text-indent:.2pt'><b style='mso-bidi-font-weight:normal'><span
                            style='font-size:10.0pt;line-height:115%;font-family:Montserrat;mso-fareast-font-family:
                            Montserrat;mso-bidi-font-family:Montserrat'>Octava.</span></b><span
                            style='font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'> </span><span style='font-size:10.0pt;line-height:115%;font-family:
                            Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:Montserrat'>La
                            presentación de candidaturas, se efectuará ante el Consejo de Premiación del
                            Premio Nacional de Protección Civil 2020, por conducto de la Coordinación
                            Nacional de Protección Civil de la Secretaría de Seguridad y Protección
                            Ciudadana, en su carácter de Secretaría Técnica del referido Órgano Colegiado,
                            de forma física en el domicilio ubicado en Avenida José Vasconcelos número 221,
                            Piso 7, Colonia San Miguel Chapultepec, Alcaldía Miguel Hidalgo, Ciudad de
                            México, Código Postal 11850, en un horario de 09:00 a 18:00 horas, de lunes a
                            viernes, o bien en línea en la página: </span><span class=MsoHyperlink>preparados.gob.mx/PremioNacional2020</span>,
                            <span style='font-size:10.0pt;line-height:115%;font-family:Montserrat;
                            mso-fareast-font-family:Montserrat;mso-bidi-font-family:Montserrat'>a partir de
                            la publicación de la presente convocatoria<span style='mso-spacerun:yes'> 
                            </span>y hasta las 18:00 horas del día 03 de julio de 2020.<o:p></o:p></span></p>
                            <p class=MsoNormal style='margin-bottom:0cm;margin-bottom:.0001pt;text-align:
                            justify;text-indent:.2pt'><span style='font-size:10.0pt;line-height:115%;
                            font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'><o:p>&nbsp;</o:p></span></p>

                            <p class=MsoNormal style='text-align:justify'><span style='font-size:10.0pt;
                            line-height:115%;font-family:Montserrat;mso-fareast-font-family:Montserrat;
                            mso-bidi-font-family:Montserrat'>La presentación de las candidaturas, para ser
                            puesta a consideración del Consejo de Premiación, deberá estar acompañada de
                            los documentos que lo avalen. Consulta la sección de <b>documentación necesaria</b> para más detalles.<o:p></o:p></span></p>
                            
                            <p class=MsoNormal style='text-align:justify;text-indent:.2pt'><b
                            style='mso-bidi-font-weight:normal'><span style='font-size:10.0pt;line-height:
                            115%;font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'>Novena.</span></b><span style='font-size:10.0pt;line-height:115%;
                            font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'> La Coordinación Nacional de Protección Civil de la Secretaría de
                            Seguridad y Protección Ciudadana, en su carácter de Secretaría Técnica del
                            Consejo de Premiación del Premio Nacional de Protección Civil 2020, verificará
                            que las candidaturas satisfagan los términos de la presente convocatoria y sus
                            bases para poder notificar al o la participante vía correo electrónico su
                            aceptación o rechazo, dentro de los quince días hábiles posteriores a la fecha
                            establecida como límite para la recepción de candidaturas.<o:p></o:p></span></p>

                            <p class=MsoNormal style='text-align:justify;text-indent:.2pt'><b
                            style='mso-bidi-font-weight:normal'><span style='font-size:10.0pt;line-height:
                            115%;font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'>Décima.</span></b><span style='font-size:10.0pt;line-height:115%;
                            font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'> La información que sea presentada con motivo de las candidaturas
                            propuestas para el otorgamiento del Premio Nacional de Protección Civil 2020,
                            estará sujeta a lo dispuesto por la Ley General de Protección de Datos
                            Personales en Posesión de Sujetos Obligados; así mismo si desea conocer el
                            aviso de privacidad, se encuentra disponible en la página de la Secretaría de
                            Seguridad y Protección Ciudadana.<o:p></o:p></span></p>

                            <p class=MsoNormal style='text-align:justify;text-indent:.2pt'><b
                            style='mso-bidi-font-weight:normal'><span style='font-size:10.0pt;line-height:
                            115%;font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'>Décima Primera.</span></b><span style='font-size:10.0pt;line-height:
                            115%;font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'> El Consejo de Premiación del Premio Nacional de Protección Civil
                            2020, integrará, a propuesta de las personas que tengan la calidad de miembros,
                            un Jurado compuesto por el número de integrantes que determinen en su primera
                            sesión, el cual no deberá ser menor a diez integrantes, quienes se sujetarán a
                            lo dispuesto por los artículos 16 a 18 y 20 a 23 de la Ley de Premios,
                            Estímulos y Recompensas Civiles. <o:p></o:p></span></p>

                            <p class=MsoNormal style='text-align:justify;text-indent:.2pt'><b
                            style='mso-bidi-font-weight:normal'><span style='font-size:10.0pt;line-height:
                            115%;font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'>Décima Segunda.</span></b><span style='font-size:10.0pt;line-height:
                            115%;font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'> El Jurado dictaminará sobre los expedientes de las candidaturas,
                            que le turne el Consejo de Premiación, dictámenes que serán entregados a la
                            Secretaría Técnica del Consejo de Premiación del Premio Nacional de Protección
                            Civil 2020, a más tardar el 14<b style='mso-bidi-font-weight:normal'> </b>de
                            agosto 2020<b style='mso-bidi-font-weight:normal'>, </b>los cuales deberán
                            contar con la mayoría de votos de los integrantes del jurado respectivo,<span
                            style='mso-spacerun:yes'>  </span>a fin de someterlos a consideración del
                            Presidente de los Estados Unidos Mexicanos. <o:p></o:p></span></p>

                            <p class=MsoNormal style='text-align:justify;text-indent:.2pt'><b
                            style='mso-bidi-font-weight:normal'><span style='font-size:10.0pt;line-height:
                            115%;font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'>Décima Tercera.</span></b><span style='font-size:10.0pt;line-height:
                            115%;font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'> El Consejo de Premiación podrá declarar, la vacancia del premio,
                            en cualquiera de sus dos campos, si no sobreviene el reconocimiento que se
                            estatuye, con base en el dictamen del Jurado debidamente fundado y motivado. <o:p></o:p></span></p>

                            <p class=MsoNormal style='text-align:justify;text-indent:.2pt'><b
                            style='mso-bidi-font-weight:normal'><span style='font-size:10.0pt;line-height:
                            115%;font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'>Décima Cuarta.</span></b><span style='font-size:10.0pt;line-height:
                            115%;font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'> Los casos no previstos en la presente convocatoria, serán
                            resueltos en definitiva por el Consejo de Premiación del Premio Nacional de
                            Protección Civil 2020, de acuerdo a las disposiciones de la Ley de Premios,
                            Estímulos y Recompensas Civiles.<o:p></o:p></span></p>

                            <p class=MsoNormal style='margin-right:-2.05pt;text-align:justify;text-indent:
                            .2pt'><b style='mso-bidi-font-weight:normal'><span style='font-size:10.0pt;
                            line-height:115%;font-family:Montserrat;mso-fareast-font-family:Montserrat;
                            mso-bidi-font-family:Montserrat'>Décima Quinta.</span></b><span
                            style='font-size:10.0pt;line-height:115%;font-family:Montserrat;mso-fareast-font-family:
                            Montserrat;mso-bidi-font-family:Montserrat'> Los y las participantes que no
                            resultaron ganadores del Premio Nacional de Protección Civil 2020, podrán
                            solicitar mediante escrito, ante la Secretaría Técnica del Consejo de
                            Premiación, la devolución de la documentación con la cual fueron postulados y/o
                            postuladas como candidatos y/o candidatas a dicho Premio, en un periodo no
                            mayor a dos meses a partir de la publicación del acuerdo presidencial en el que
                            se señalan a los ganadores. En caso de no requerir la devolución en el plazo
                            señalado, se entiende por autorizada su destrucción.<o:p></o:p></span></p>

                            <p class=MsoNormal style='margin-right:-2.05pt;text-align:justify;text-indent:
                            .2pt'><b style='mso-bidi-font-weight:normal'><span style='font-size:10.0pt;
                            line-height:115%;font-family:Montserrat;mso-fareast-font-family:Montserrat;
                            mso-bidi-font-family:Montserrat'>Décima Sexta.</span></b><span
                            style='font-size:10.0pt;line-height:115%;font-family:Montserrat;mso-fareast-font-family:
                            Montserrat;mso-bidi-font-family:Montserrat'> Solo serán consideradas las
                            candidaturas que se entreguen directamente en la Secretaría Técnica del Consejo
                            de Premiación del Premio Nacional de Protección Civil 2020, o a través de la
                            página del premio señalada en la Base Octava de la presente convocatoria, y que
                            cumplan con los términos de la presente convocatoria y sus bases. <o:p></o:p></span></p>

                            <p class=MsoNormal style='margin-right:-2.05pt;text-align:justify;text-indent:
                            .2pt'><span style='font-size:10.0pt;line-height:115%;font-family:Montserrat;
                            mso-fareast-font-family:Montserrat;mso-bidi-font-family:Montserrat'>No se
                            recibirán postulaciones por correo electrónico, de ser el caso serán
                            rechazadas.<o:p></o:p></span></p>

                            <p class=MsoNormal style='margin-right:-4.65pt;text-align:justify;text-indent:
                            .2pt'><b style='mso-bidi-font-weight:normal'><span style='font-size:10.0pt;
                            line-height:115%;font-family:Montserrat;mso-fareast-font-family:Montserrat;
                            mso-bidi-font-family:Montserrat'>Décima Séptima</span></b><span
                            style='font-size:10.0pt;line-height:115%;font-family:Montserrat;mso-fareast-font-family:
                            Montserrat;mso-bidi-font-family:Montserrat'>. De acuerdo a lo establecido en el
                            artículo 105 de la Ley de Premios, Estímulos y Recompensas Civiles el Premio
                            será entregado el 19 de septiembre del año 2020.<span
                            style='mso-spacerun:yes'>  </span><o:p></o:p></span></p>

                            <p class=MsoNormal style='text-align:justify;text-indent:.2pt'><span
                            style='font-size:10.0pt;line-height:115%;font-family:Montserrat;mso-fareast-font-family:
                            Montserrat;mso-bidi-font-family:Montserrat'>El Consejo de Premiación:<o:p></o:p></span></p>

                            <table class=a border=0 cellspacing=0 cellpadding=0 width=0 style='border-collapse:
                            collapse;mso-table-layout-alt:fixed;mso-yfti-tbllook:1024;mso-padding-alt:
                            0cm .5pt 0cm .5pt'>
                                <tr style='mso-yfti-irow:0;mso-yfti-firstrow:yes'>
                                <td width=518 valign=top style='width:388.15pt;padding:0cm .5pt 0cm .5pt'>
                                <p class=MsoNormal style='margin-right:-5.4pt;text-align:justify'><b
                                style='mso-bidi-font-weight:normal'><span style='font-size:10.0pt;line-height:
                                115%;font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                                Montserrat'>Titular de la Secretaría de Seguridad y Protección Ciudadana
                                (Presidente)<o:p></o:p></span></b></p>
                                </td>
                                </tr>
                                <tr style='mso-yfti-irow:1'>
                                <td width=518 valign=top style='width:388.15pt;padding:0cm .5pt 0cm .5pt'>
                                <p class=MsoNormal style='text-align:justify'><b style='mso-bidi-font-weight:
                                normal'><span style='font-size:10.0pt;line-height:115%;font-family:Montserrat;
                                mso-fareast-font-family:Montserrat;mso-bidi-font-family:Montserrat'>Titular
                                de la Secretaría de la Defensa Nacional<o:p></o:p></span></b></p>
                                </td>
                                </tr>
                                <tr style='mso-yfti-irow:2'>
                                <td width=518 valign=top style='width:388.15pt;padding:0cm .5pt 0cm .5pt'>
                                <p class=MsoNormal style='margin-right:-4.65pt;text-align:justify'><b
                                style='mso-bidi-font-weight:normal'><span style='font-size:10.0pt;line-height:
                                115%;font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                                Montserrat'>Titular de la Secretaría de Marina<o:p></o:p></span></b></p>
                                </td>
                                </tr>
                                <tr style='mso-yfti-irow:3'>
                                <td width=518 valign=top style='width:388.15pt;padding:0cm .5pt 0cm .5pt'>
                                <p class=MsoNormal style='text-align:justify'><b style='mso-bidi-font-weight:
                                normal'><span style='font-size:10.0pt;line-height:115%;font-family:Montserrat;
                                mso-fareast-font-family:Montserrat;mso-bidi-font-family:Montserrat'>Presidenta
                                de la Mesa Directiva de la H. Cámara de Diputados<o:p></o:p></span></b></p>
                                </td>
                                </tr>
                                <tr style='mso-yfti-irow:4'>
                                <td width=518 valign=top style='width:388.15pt;padding:0cm .5pt 0cm .5pt'>
                                <p class=MsoNormal style='text-align:justify'><b style='mso-bidi-font-weight:
                                normal'><span style='font-size:10.0pt;line-height:115%;font-family:Montserrat;
                                mso-fareast-font-family:Montserrat;mso-bidi-font-family:Montserrat'>Presidenta
                                de la Mesa Directiva de la H. Cámara de Senadores<o:p></o:p></span></b></p>
                                </td>
                                </tr>
                                <tr style='mso-yfti-irow:5'>
                                <td width=518 valign=top style='width:388.15pt;padding:0cm .5pt 0cm .5pt'>
                                <p class=MsoNormal style='margin-right:1.65pt;text-align:justify'><b
                                style='mso-bidi-font-weight:normal'><span style='font-size:10.0pt;line-height:
                                115%;font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                                Montserrat'>Titular de la Coordinación Nacional de Protección Civil
                                (Secretario Técnico)<o:p></o:p></span></b></p>
                                </td>
                                </tr>
                                <tr style='mso-yfti-irow:6'>
                                <td width=518 valign=top style='width:388.15pt;padding:0cm .5pt 0cm .5pt'>
                                <p class=MsoNormal style='margin-right:1.65pt;text-align:justify'><b
                                style='mso-bidi-font-weight:normal'><span style='font-size:10.0pt;line-height:
                                115%;font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                                Montserrat'>Titular de la Dirección General del Centro Nacional de Prevención
                                de Desastres<o:p></o:p></span></b></p>
                                </td>
                                </tr>
                                <tr style='mso-yfti-irow:7'>
                                <td width=518 valign=top style='width:388.15pt;padding:0cm .5pt 0cm .5pt'>
                                <p class=MsoNormal style='margin-right:1.65pt;text-align:justify'><b
                                style='mso-bidi-font-weight:normal'><span style='font-size:10.0pt;line-height:
                                115%;font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                                Montserrat'>Rector de la Universidad Nacional Autónoma de México<o:p></o:p></span></b></p>
                                </td>
                                </tr>
                                <tr style='mso-yfti-irow:8;height:15.6pt'>
                                <td width=518 valign=top style='width:388.15pt;padding:0cm .5pt 0cm .5pt;
                                height:15.6pt'>
                                <p class=MsoNormal style='margin-right:1.65pt;text-align:justify'><b
                                style='mso-bidi-font-weight:normal'><span style='font-size:10.0pt;line-height:
                                115%;font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                                Montserrat'>Titular de la Dirección General del Instituto Politécnico
                                Nacional<o:p></o:p></span></b></p>
                                </td>
                                </tr>
                                <tr style='mso-yfti-irow:9;mso-yfti-lastrow:yes'>
                                <td width=518 valign=top style='width:388.15pt;padding:0cm .5pt 0cm .5pt'>
                                <p class=MsoNormal style='text-align:justify'><b style='mso-bidi-font-weight:
                                normal'><span style='font-size:10.0pt;line-height:115%;font-family:Montserrat;
                                mso-fareast-font-family:Montserrat;mso-bidi-font-family:Montserrat'>Presidente
                                Nacional de la Cruz Roja Mexicana<o:p></o:p></span></b></p>
                                <p class=MsoNormal style='text-align:justify'><b style='mso-bidi-font-weight:
                                normal'><span style='font-size:10.0pt;line-height:115%;font-family:Montserrat;
                                mso-fareast-font-family:Montserrat;mso-bidi-font-family:Montserrat'><o:p>&nbsp;</o:p></span></b></p>
                                <p class=MsoNormal style='text-align:justify'><b style='mso-bidi-font-weight:
                                normal'><span style='font-size:10.0pt;line-height:115%;font-family:Montserrat;
                                mso-fareast-font-family:Montserrat;mso-bidi-font-family:Montserrat'><o:p>&nbsp;</o:p></span></b></p>
                                </td>
                                </tr>
                            </table>

                            <p class=MsoNormal align=right style='text-align:right'><b style='mso-bidi-font-weight:
                            normal'><span style='font-size:10.0pt;line-height:115%;font-family:Montserrat;
                            mso-fareast-font-family:Montserrat;mso-bidi-font-family:Montserrat'>Ciudad de
                            México, a 29 de mayo de 2020.</span></b><b style='mso-bidi-font-weight:normal'><span
                            style='font-size:12.0pt;line-height:115%;font-family:Montserrat;mso-fareast-font-family:
                            Montserrat;mso-bidi-font-family:Montserrat'><o:p></o:p></span></b></p>
                        </span>
                    </div>
                </li>
                <li>
                    <div class="collapsible-header mainIcons"><i class="material-icons">attach_file</i>Documentación necesaria</div>
                    <div class="collapsible-body">
                        <span>
                            <p class=MsoNormal style='margin-left:36.0pt;text-align:justify;text-indent:
                            -36.0pt;mso-list:l1 level1 lfo1'><![if !supportLists]><span style='font-size:
                            10.0pt;line-height:115%;font-family:Montserrat;mso-fareast-font-family:Montserrat;
                            mso-bidi-font-family:Montserrat'><span style='mso-list:Ignore'>1.<span
                            style='font:7.0pt "Times New Roman"'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            </span></span></span><![endif]><span style='font-size:10.0pt;line-height:115%;
                            font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'>Carta de propuesta, la cual deberá ser emitida por un tercero,
                            debidamente firmada en original dirigida al Consejo de Premiación, la cual
                            deberá contener el nombre completo del candidato o la candidata, señalar el
                            campo en que participa y señalar domicilio para recibir notificaciones y datos
                            de contacto, tanto del o la proponente como del candidato o la candidata, en la
                            que argumente y exponga los motivos por los cuales se considera que la
                            candidatura es<span style='mso-spacerun:yes'>  </span>merecedora del premio,
                            presentado con las siguientes características: Letra Arial 12, doble espacio,
                            tamaño carta, sin sangría y margen justificado, márgenes laterales de 3 centímetros,
                            y superior e inferior de 2.5 centímetros. Además el documento deberá contener
                            los siguientes apartados: 1) Antecedentes; 2) Descripción de acciones y medidas
                            a premiar; 3) Justificación y relevancia; 4) Alineación de las acciones con el
                            Sistema Nacional de Protección Civil.<o:p></o:p></span></p>

                            <p class=MsoNormal style='margin-left:36.0pt;text-align:justify;text-indent:
                            -36.0pt;mso-list:l1 level1 lfo1'><![if !supportLists]><span style='font-size:
                            10.0pt;line-height:115%;font-family:Montserrat;mso-fareast-font-family:Montserrat;
                            mso-bidi-font-family:Montserrat'><span style='mso-list:Ignore'>2.<span
                            style='font:7.0pt "Times New Roman"'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            </span></span></span><![endif]><span style='font-size:10.0pt;line-height:115%;
                            font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'>Carta de auto propuesta, firmada en original dirigida al Consejo de
                            Premiación, la cual deberá contener el nombre completo, señalar el campo en que
                            participa y señalar domicilio para recibir notificaciones y datos de contacto,
                            en la que argumente y exponga los motivos por los cuales se considera que la
                            candidatura es<span style='mso-spacerun:yes'>  </span>merecedora del premio,
                            presentado con las siguientes características: : Letra Arial 12, doble espacio,
                            tamaño carta, sin sangría y margen justificado, márgenes laterales de 3
                            centímetros, y superior e inferior de 2.5 centímetros. Además el documento
                            deberá contener los siguientes apartados: 1) Antecedentes; 2) Descripción de
                            acciones y medidas a premiar; 3) Justificación y relevancia; 4) Alineación de
                            las acciones con el Sistema Nacional de Protección Civil.<o:p></o:p></span></p>

                            <p class=MsoNormal style='margin-left:36.0pt;text-align:justify;text-indent:
                            -36.0pt;mso-list:l1 level1 lfo1'><![if !supportLists]><span style='font-size:
                            10.0pt;line-height:115%;font-family:Montserrat;mso-fareast-font-family:Montserrat;
                            mso-bidi-font-family:Montserrat'><span style='mso-list:Ignore'>3.<span
                            style='font:7.0pt "Times New Roman"'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            </span></span></span><![endif]><span style='font-size:10.0pt;line-height:115%;
                            font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'>Escrito de protesta de aceptación por parte del candidato o la
                            candidata para participar y, en su caso, recibir el Premio Nacional de
                            Protección Civil 2020, firmada en original. <o:p></o:p></span></p>

                            <p class=MsoNormal style='margin-left:36.0pt;text-align:justify'><span
                            style='font-size:10.0pt;line-height:115%;font-family:Montserrat;mso-fareast-font-family:
                            Montserrat;mso-bidi-font-family:Montserrat'>En caso de un grupo de personas
                            físicas, anexar relación de sus integrantes y documento en el que se haya
                            designado a su representante.<span style='mso-spacerun:yes'>  </span><o:p></o:p></span></p>

                            <p class=MsoNormal style='margin-left:36.0pt;text-align:justify;text-indent:
                            -36.0pt;mso-list:l1 level1 lfo1'><![if !supportLists]><span style='font-size:
                            10.0pt;line-height:115%;font-family:Montserrat;mso-fareast-font-family:Montserrat;
                            mso-bidi-font-family:Montserrat'><span style='mso-list:Ignore'>4.<span
                            style='font:7.0pt "Times New Roman"'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            </span></span></span><![endif]><span style='font-size:10.0pt;line-height:115%;
                            font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'>Currículum Vitae actualizado del o de los candidato(s) propuesto(s)
                            y/o de la o de las candidata(s) propuesta(s), incluyendo nombre, dirección
                            completa, teléfono(s) y correo electrónico.<o:p></o:p></span></p>

                            <p class=MsoNormal style='margin-top:0cm;margin-right:0cm;margin-bottom:0cm;
                            margin-left:36.0pt;margin-bottom:.0001pt;text-align:justify;text-indent:-36.0pt;
                            mso-list:l1 level1 lfo1'><![if !supportLists]><span style='font-size:10.0pt;
                            line-height:115%;font-family:Montserrat;mso-fareast-font-family:Montserrat;
                            mso-bidi-font-family:Montserrat'><span style='mso-list:Ignore'>5.<span
                            style='font:7.0pt "Times New Roman"'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            </span></span></span><![endif]><span style='font-size:10.0pt;line-height:115%;
                            font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'>Copia del documento oficial con el que demuestre su nacionalidad
                            mexicana:<o:p></o:p></span></p>

                            <p class=MsoNormal style='margin-bottom:0cm;margin-bottom:.0001pt;text-align:
                            justify;text-indent:.2pt'><span style='font-size:10.0pt;line-height:115%;
                            font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'><o:p>&nbsp;</o:p></span></p>

                            <p class=MsoNormal style='margin-top:0cm;margin-right:0cm;margin-bottom:0cm;
                            margin-left:36.0pt;margin-bottom:.0001pt;text-align:justify;text-indent:-18.0pt;
                            mso-list:l0 level1 lfo2;border:none;mso-padding-alt:31.0pt 31.0pt 31.0pt 31.0pt;
                            mso-border-shadow:yes'><![if !supportLists]><span style='font-size:10.0pt;
                            line-height:115%;font-family:Montserrat;mso-fareast-font-family:Montserrat;
                            mso-bidi-font-family:Montserrat;color:black'><span style='mso-list:Ignore'>a)<span
                            style='font:7.0pt "Times New Roman"'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><![endif]><span
                            style='font-size:10.0pt;line-height:115%;font-family:Montserrat;mso-fareast-font-family:
                            Montserrat;mso-bidi-font-family:Montserrat;color:black'>Para personas físicas
                            mexicanas, individualmente o en grupo, copia del acta de nacimiento de cada
                            participante. <o:p></o:p></span></p>

                            <p class=MsoNormal style='margin-top:0cm;margin-right:0cm;margin-bottom:0cm;
                            margin-left:54.2pt;margin-bottom:.0001pt;text-align:justify'><span
                            style='font-size:10.0pt;line-height:115%;font-family:Montserrat;mso-fareast-font-family:
                            Montserrat;mso-bidi-font-family:Montserrat'><o:p>&nbsp;</o:p></span></p>

                            <p class=MsoNormal style='margin-top:0cm;margin-right:0cm;margin-bottom:0cm;
                            margin-left:36.0pt;margin-bottom:.0001pt;text-align:justify;text-indent:-18.0pt;
                            mso-list:l0 level1 lfo2;border:none;mso-padding-alt:31.0pt 31.0pt 31.0pt 31.0pt;
                            mso-border-shadow:yes'><![if !supportLists]><span style='font-size:10.0pt;
                            line-height:115%;font-family:Montserrat;mso-fareast-font-family:Montserrat;
                            mso-bidi-font-family:Montserrat;color:black'><span style='mso-list:Ignore'>b)<span
                            style='font:7.0pt "Times New Roman"'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </span></span></span><![endif]><span
                            style='font-size:10.0pt;line-height:115%;font-family:Montserrat;mso-fareast-font-family:
                            Montserrat;mso-bidi-font-family:Montserrat;color:black'>Para personas morales,
                            copia del acta constitutiva, debidamente protocolizada ante Notario Público y
                            en caso de modificación, deberá presentar la protocolización del acta de
                            Asamblea General, a fin de acreditar su legal existencia. <o:p></o:p></span></p>

                            <p class=MsoNormal style='margin-top:0cm;margin-right:0cm;margin-bottom:0cm;
                            margin-left:54.2pt;margin-bottom:.0001pt;text-align:justify'><span
                            style='font-size:10.0pt;line-height:115%;font-family:Montserrat;mso-fareast-font-family:
                            Montserrat;mso-bidi-font-family:Montserrat'><o:p>&nbsp;</o:p></span></p>

                            <p class=MsoNormal style='margin-top:0cm;margin-right:0cm;margin-bottom:0cm;
                            margin-left:36.0pt;margin-bottom:.0001pt;text-align:justify;text-indent:-36.0pt;
                            mso-list:l1 level1 lfo1'><![if !supportLists]><span style='font-size:10.0pt;
                            line-height:115%;font-family:Montserrat;mso-fareast-font-family:Montserrat;
                            mso-bidi-font-family:Montserrat'><span style='mso-list:Ignore'>6.<span
                            style='font:7.0pt "Times New Roman"'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            </span></span></span><![endif]><span style='font-size:10.0pt;line-height:115%;
                            font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'>Copia o duplicado de materiales bibliográficos, audiovisuales,
                            gráficos u otros que demuestren los motivos por los cuales se considera que el
                            candidato o la candidata puede merecer el Premio Nacional de Protección Civil
                            2020. Para más detalles consulta la sección siguiente denominada "¿Cómo subir mis archivos?".<o:p></o:p></span></p>

                            <p class=MsoNormal style='margin-top:0cm;margin-right:-4.65pt;margin-bottom:
                            0cm;margin-left:36.2pt;margin-bottom:.0001pt;text-align:justify'><span
                            style='font-size:10.0pt;line-height:115%;font-family:Montserrat;mso-fareast-font-family:
                            Montserrat;mso-bidi-font-family:Montserrat'><o:p>&nbsp;</o:p></span></p>

                            <p class=MsoNormal style='margin-top:0cm;margin-right:0cm;margin-bottom:0cm;
                            margin-left:36.0pt;margin-bottom:.0001pt;text-align:justify;text-indent:-36.0pt;
                            mso-list:l1 level1 lfo1'><![if !supportLists]><span style='font-size:10.0pt;
                            line-height:115%;font-family:Montserrat;mso-fareast-font-family:Montserrat;
                            mso-bidi-font-family:Montserrat'><span style='mso-list:Ignore'>7.<span
                            style='font:7.0pt "Times New Roman"'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            </span></span></span><![endif]><span style='font-size:10.0pt;line-height:115%;
                            font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'>Los trabajos relacionados con herramientas tecnológicas e
                            innovación deberán presentar resultados que sustenten su viabilidad y un
                            análisis costo beneficio.<o:p></o:p></span></p>

                            <p class=MsoNormal style='margin-bottom:0cm;margin-bottom:.0001pt'><span
                            style='font-size:10.0pt;line-height:115%;font-family:Montserrat;mso-fareast-font-family:
                            Montserrat;mso-bidi-font-family:Montserrat'><o:p>&nbsp;</o:p></span></p>

                            <p class=MsoNormal style='margin-left:36.0pt;text-align:justify;text-indent:
                            -36.0pt;mso-list:l1 level1 lfo1'><![if !supportLists]><span style='font-size:
                            10.0pt;line-height:115%;font-family:Montserrat;mso-fareast-font-family:Montserrat;
                            mso-bidi-font-family:Montserrat'><span style='mso-list:Ignore'>8.<span
                            style='font:7.0pt "Times New Roman"'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            </span></span></span><![endif]><span style='font-size:10.0pt;line-height:115%;
                            font-family:Montserrat;mso-fareast-font-family:Montserrat;mso-bidi-font-family:
                            Montserrat'>Escrito donde acepta conocer el Aviso de Privacidad del Premio
                            Nacional de Protección Civil 2020 y estar conforme con el tratamiento que se le
                            dará a sus datos.<o:p></o:p></span></p>

                            <p class=MsoNormal style='margin-bottom:0cm;margin-bottom:.0001pt;text-align:
                            justify'><span style='font-size:10.0pt;line-height:115%;font-family:Montserrat;
                            mso-fareast-font-family:Montserrat;mso-bidi-font-family:Montserrat'>En caso de
                            faltar cualquier documento anteriormente enlistado, se hará del conocimiento
                            del candidato, a fin de que complemente la documentación faltante y sea
                            entregada antes del término señalado el párrafo primero de esta Base.<o:p></o:p></span></p>
                        </span>
                    </div>
                </li>
                <li>
                    <div class="collapsible-header mainIcons"><i class="material-icons">insert_drive_file</i>¿Cómo subir mis archivos?</div>
                    <div class="collapsible-body">
                        <span>
                        Los archivos mencionados en la convocatoria deben ser subidos a esta plataforma como un archivo .zip o .rar, 
                        este debe de pesar menos de 10 MegaBytes y debe estar conformado por las siguientes carpetas con su respectiva nomenclatura en cada nombre del documento que se muestra en rojo:
                        </span>
                        <ul style="padding-left:inherit;">
                            <li style="list-style-type: circle;">Cartas.</li>
                                <ul style="padding-left:inherit;">
                                    <li style="list-style-type: square;">Carta de propuesta. <span class="nomenclatura">carta_propuesta_TUSINICIALES.pdf</span></li>
                                    <li style="list-style-type: square;">Carta de auto propuesta. <span class="nomenclatura">carta_autopropuesta_TUSINICIALES.pdf</span></li>
                                    <li style="list-style-type: square;">Carta de designación de representante (en caso de un grupo de personas físicas). <span class="nomenclatura">representante_oficial.pdf</span></li>
                                </ul>
                            <li style="list-style-type: circle;">Escritos.</li>
                                <ul style="padding-left:inherit;">
                                    <li style="list-style-type: square;">Escrito de protesta de aceptación.</li>
                                    <li style="list-style-type: square;">Escrito de Aviso de Privacidad.</li>
                                </ul>
                            <li style="list-style-type: circle;">Documentos oficiales.</li>
                                <ul style="padding-left:inherit;">
                                    <li style="list-style-type: square;">Currículum Vitae.</li>
                                    <li style="list-style-type: square;">Lista de participantes (en caso de grupo de persons físicas).</li>
                                    <li style="list-style-type: square;">Acta de nacimiento de cada participante (en caso de grupo de persons físicas).</li>
                                    <li style="list-style-type: square;">Copia del acta constitutiva (en caso de personas morales).</li>
                                </ul>
                            <li style="list-style-type: circle;">Materiales extras.</li>
                                <ul style="padding-left:inherit;">
                                    <li style="list-style-type: square;">Materiales bibliográficos, audiovisuales, gráficos u otros (en caso de que quepan dentro de los límites permitidos del ZIP o RAR).</li>
                                    <li style="list-style-type: square;">Anaísis costo beneficio (solo para trabajos relacionados con herramientas tecnológicas).</li>
                                    <li style="list-style-type: square;">Archivo de texto (Word o PDF) con el listado de direcciónes electrónicas URL hacia los documentos multimedia.</li>
                                    <i style="">Para mas detalles de este apartado consulta la sección de "¿Cómo enviar mi evidencia audiovisual?".</i>
                                </ul>
                        </ul>
                </div>
                </li>
                <li>
                    <div class="collapsible-header mainIcons"><i class="material-icons">check</i>¿Cómo sabré que mi candidatura fue recibida?</div>
                    <div class="collapsible-body"><span>Al completar el siguiente formulario y darle clic al botón de "SUBIR DATOS" te mandará a una pantalla de confirmación de que tu información y tus archivos han sido enviados. Espera en tu bandeja de entrada un email de confirmación para verificar que hemos recibido la candidatura. Recuerda revisar tu bandeja de SPAM así como de CORREO NO DESEADO según sea el caso. Si no has recibido respuesta de confirmación intenta nuevamente subir tu candidatura en un lapso de 3 horas. En forma reiterada de pedimos verifiques tu mail de contacto para evitar conflictos de envío de confirmación, ante esto te recomendamos usar correos de dominio Gmail, Hotmail, Yahoo!, etc.</span></div>
                </li>
                <li>
                    <div id="lastOne" class="collapsible-header mainIcons"><i class="material-icons">videocam</i>¿Cómo enviar mi evidencia audiovisual?</div>
                    <div class="collapsible-body"><span>Toda la evidencia audiovisual deberá ser subida a plataformas abiertas como: Youtube, GoogleDrive, WeTransfer, etc. Y compartir en un archivo de texto (Word o PDF) la liga de esta evidencia. En caso de que tu evidencia pese menos de 10 MB en el archivo ZIP podrá ser aceptada dentro de su carpeta correspondiente.</span></div>
                </li>
            </ul>
            <div class="row center">
            <button class="btn-large disabled guinda" type="button" id="btn-continuar" style="pointer-events:unset;"><i class="material-icons right">arrow_forward</i>
            Iniciar registro
            </button>
            </div>
        </div>
        <form method="post" id="submit-form" style="display:none;" enctype="multipart/form-data">
                <h5 class="center titleMex">Registro de candidaturas para el Premio Nacional de Protección Civil 2020</h5>
                <div id="primera-parte" class="row">
                    <div class="row">
                        <h6 class="center">Ingresa los siguientes campos</h6>
                    </div>
                    <div class="row">
                        <div class="input-field">
                            <i class="material-icons prefix">account_circle</i>
                            <input required placeholder="Nombre Completo" name="nombre" id="nombre" type="text" class="validate" maxlength="512" <?php if ($keep || isset($_POST['nombre'])) echo 'value="'.$_POST['nombre'].'"'; ?>>
                            <label for="nombre">Ingresa tu nombre completo</label>
                            <span class="helper-text" data-error="Completa este campo" data-success="Correcto"></span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="input-field">
                            <i class="material-icons prefix">phone</i>
                            <input placeholder="Ingresa número telefónico" name="telefono" id="telefono" type="text" class="validate" data-length="10" maxlength="10" <?php if ($keep || isset($_POST['telefono'])) echo 'value="'.$_POST['telefono'].'"'; ?>>
                            <label for="telefono">Ingresa un número de contacto. (10 dígitos)</label>
                            <span class="helper-text" data-error="Completa este campo" data-success="Correcto"></span>
                    </div>
                    <div class="row">
                        <strong>Ingresa un correo para poder dar un seguimiento y tener un medio de comunicación donde se enviará información relevante. Asegúrate de escribirlo corréctamente.</strong><br>
                        <div class="input-field">
                            <i class="material-icons prefix">email</i>
                            <input required type="email" name="correo" id="correo"  class="validate" <?php if ($keep || isset($_POST['correo'])) echo 'value="'.$_POST['correo'].'"'; ?>>
                            <label for="correo">Correo</label>
                            <span class="helper-text" data-error="Correo inválido" data-success="Correcto"></span>
                        </div>
                    </div>
                    <div class="row">
                        <h6 class="center">Subir documento de la candidatura</h6>
                    </div>
                    <div class="row">
                        <div class = "file-field input-field">
                            <div class = "btn guinda">
                                <span>Seleccionar archivo</span>
                                <input id="archivo" type = "file" name="archivo" accept=".zip,.rar"/>
                            </div>
                            
                            <div class = "file-path-wrapper">
                                <input class = "file-path validate" type="text"
                                    placeholder = "Subir documento (.zip o .rar)" />
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <h6 class="center">Datos de ubicación de candidatura</h6>
                    </div>
                    <div class="row" id="location-helper">
                        <p>Selecciona tu estado y municipio</p>
                    </div>
                    <div class="row" id="estado-div">
                        <div class="input-field">
                            <select name="estado" id="estado-select" class="validate">
                                <option value="" disabled selected>Elije el estado</option>
                                <?php $estados = getEstados();
                                    foreach($estados as $i => $estado) { ?>
                                    <?php if ($keep && $estado['id'] == $_POST['estado'] || (isset($_GET['estado']) && $estado['estado'] == $_GET['estado'])) { ?>
                                        <option value="<?=$estado['id']?>" selected><?=$estado['estado']?></option>
                                    <?php } else { ?>
                                        <option value="<?=$estado['id']?>"><?=$estado['estado']?></option>
                                    <?php } ?>
                                <?php } ?>
                            </select>
                            <label for="estado-select">Estado</label>
                            <span class="helper-text" data-error="Completa este campo" data-success="Correcto"></span>
                        </div>
                    </div>
                    <div class="progress" id="municipio-loading" style="display: none;">
                        <div class="indeterminate"></div>
                    </div>
                    <div class="row" id="municipio-div" style="display: none;">
                        <div class="input-field">
                            <select name="municipio" id="municipio-select" class="validate">
                                <option value="" disabled>Elije el municipio</option>
                            </select>
                            <label for="estado-select">Municipio</label>
                            <span class="helper-text" data-error="Completa este campo" data-success="Correcto"></span>
                        </div>
                    </div>
                    <div class="row center">
                        <button id="btn-submit" class="btn-large modal-trigger disabled guinda" data-target="modal"><i class="material-icons right">send</i>
                            Subir datos
                        </button>
                    </div>
                </div>

                <div id="modal" class="modal">
                    <div class="modal-content">
                        <h4>Confirmación</h4>
                        <p>Si estás seguro de que la información ingresada es correcta, presiona el botón de Aceptar</p>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn modal-close guinda">Aceptar</button>
                        <button type="button" data-target="modal" class="btn modal-close verde-oscuro">Cancelar</button>
                    </div>
                </div>
                <div id="error-modal" class="modal">
                    <div class="modal-content">
                        <h4>Error</h4>
                        <p>Verifica tus datos</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-target="modal" class="btn modal-close">Entendido</button>
                    </div>
                </div>
                <div id="wait-modal" class="modal">
                    <div class="modal-content center">
                        <h4>Subiendo registro...</h4>
                        <p>Esto podría tardar unos segundos, dependiendo del tamaño de tu archivo y la velocidad de tu conexión.</p>
                        <div class="preloader-wrapper big active">
                            <div class="spinner-layer spinner-blue-only">
                            <div class="circle-clipper left">
                                <div class="circle"></div>
                            </div><div class="gap-patch">
                                <div class="circle"></div>
                            </div><div class="circle-clipper right">
                                <div class="circle"></div>
                            </div>
                            </div>
                        </div>
                        <p>Por favor no cierres ni actualices la página hasta que se haya completado este proceso.</p>
                    </div>
                </div>
                
            </form>

    </div>
    
    <script type="text/javascript" src="js/formulario.js"></script>
</body>
</html>
