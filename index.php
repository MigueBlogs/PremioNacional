<?php
    $today = time();
    $date_start = strtotime('2020-05-29 13:00:00.0');  // UTC for 29 May, 08:00 GMT-5 (Mexico City)
    $date_end = strtotime('2020-08-14 23:00:00.0');  // UTC for 03 Jul, 18:00 GMT-5 (Mexico City)
    $available = $today - $date_start >= 0 ? true : false;
    $expired = $today - $date_end >= 0 ? true: false;
    if ($available == false || $expired) {
        header("Location: http://www.preparados.cenapred.unam.mx/PremioNacional2020/gracias.php");
        die();
    }
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
        isset($_POST["municipio"]) &&
        isset($_POST["tipo"]) &&
        isset($_POST["categoria"]))
    {
        SendForm();    
    }

    function SendForm() {
        global $target_dir, $error_msg, $success_msg, $keep;

        $nombre = substr(trim($_POST['nombre']), 0, 512);
        $correo =  mb_strtolower(substr(trim($_POST['correo']), 0, 128));
        $telefono = isset($_POST["telefono"]) ? substr(trim($_POST['telefono']), 0, 10) : null;
        if ($telefono) {
            $telefono = preg_replace("/[^0-9]/", "", $telefono);
        }
        $estado = intval($_POST['estado']);
        $municipio = $_POST['municipio'];  // debe ser char para respetar 0 al inicio
        $tipo = trim($_POST['tipo']);
        $categoria = trim($_POST['categoria']);

        if (validateForm($nombre, $correo, $telefono, $estado, $municipio, $tipo, $categoria)){
            
            $imageFileType = strtolower(pathinfo($target_dir . basename($_FILES['archivo']['name']), PATHINFO_EXTENSION));

            $tmp_uid = uniqid();
            $target_file = $target_dir . $tmp_uid . "." . $imageFileType;
            $url_file = "http://www.preparados.cenapred.unam.mx/uploads/premionacional2020/". $tmp_uid . "." . $imageFileType;
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
                ":municipio"=>$municipio,
                ":tipo"=>$tipo,
                ":categoria"=>$categoria
            ];
            if (registrar($datos)) {
                $tmp = getUltimoRegistro($correo); //se utiliza para obtener ultimo registro en la BD y envia correo de confirmación
                if (enviarCorreoConfirmacion($correo,$tmp['ID'],$nombre,$tipo,$categoria)){
                    $success_msg = 'Registro realizado correctamente. Se ha enviado exitosamente un correo confirmando tu registro';
                    //unset($_POST);
                }
                else {
                    $success_msg = 'Registro realizado correctamente';
                    $error_msg = 'No se pudo enviar el correo de confirmación para la dirección que ingresaste. Es posible que se haya ingresado erróneamente.';
                }
            }
            else {
                unlink($target_file);
                $error_msg = 'No se pudo realizar el registro de tu candidatura.';
            }

        }
        if ($error_msg) $keep = true;
    }

    function validateForm($nombre, $correo, $telefono, $estado, $municipio, $tipo, $categoria) {
        global $error_msg, $allowTypes, $max_file_size, $target_dir;

        $possible_tipo = array("Persona física", "Personas físicas", "Grupo voluntario");
        $possible_categoria = array("Prevención", "Ayuda");

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $error_msg = 'El correo electrónico es inválido. Verifíca que esté bien escrito';
            return false;
        }
        if ($estado < 1 || $estado > 32){
            $error_msg = 'El estado seleccionado es inválido.';
            return false;
        }

        if (!in_array($tipo, $possible_tipo)){
            $error_msg = "Selecciona un tipo de registro válido";
            return false;
        }

        if (!in_array($categoria, $possible_categoria)){
            $error_msg = "Selecciona una categoría válida";
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
            $error_msg = 'El archivo subido no es un archivo ZIP o RAR válido';
            return false;
        }
        return true;
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-145898219-1"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'UA-145898219-1');
    </script>
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <link rel="shortcut icon" href="http://www.atlasnacionalderiesgos.gob.mx/Imagenes/Logos/cenapred_icon.ico"/>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1.0, maximum-scale=1.0">
    <title>Premio Nacional 2020</title>
    <?php
    // function console_log( $data ){
    //     echo '<script>';
    //     echo 'console.log('. json_encode( $data ) .');';
    //     echo '</script>';
    // };
    ?>
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
            <p style="width: 100%;">JavaScript está deshabilitado, esta página necesita tener activado JavaScript para que funcione correctamente.</p>
            <button id="btn-error-close" class="btn-small" type="button" style="margin-right: 1em;"><i class="material-icons">close</i></button>
        </div>
    </noscript>
    <style>
        p.MsoNormal, li.MsoNormal, div.MsoNormal
        {
        margin-top:0cm;
        margin-right:0cm;
        margin-bottom:10.0pt;
        margin-left:0cm;
        line-height:115%;
        
        font-size:11.0pt;
        /* font-family:"Calibri",sans-serif; */
        }

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
        <!-- <div class="fixed-action-btn" style="position: fixed; bottom: 5em;">
            <a target="_blank" href="http://www.preparados.cenapred.unam.mx/blog" class="btn-floating btn blue"><i class="material-icons">help</i></a>
        </div> -->

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
                    <div class="collapsible-body"><span>Bienvenido al portal de registro de Candidaturas para el Premio Nacional de Protección Civil,
                     para iniciar deberás leer completamente las instrucciones siguientes y a continuación hacer clic sobre el botón de 
                     “INICIAR REGISTRO”, después deberás seleccionar el tipo de registro (<b>obligatorio</b>) el cuál debe ser 
                     <b>una</b> de tres opciones distintas: Persona física, un grupo de personas físicas o Grupo voluntario. Enseguida 
                     debes seleccionar la categoría de tu postulación, esta puede ser “Prevención” o “Ayuda”, en caso de que sean ambas opciones
                      deberás de registrar tus datos dos veces, es decir: un registro con el rubro seleccionado de “Prevención” y otro registro nuevo 
                      con el rubro seleccionado en “Ayuda”, todo esto con los datos correspondientes. Posteriormente podrás ingresar tus datos
                       de contacto como: Nombre completo (<b>obligatorio</b>), correo electrónico (<b>obligatorio</b>) y un número telefónico de contacto
                        (opcional), dicho contacto deberá ser titular responsable del proyecto en caso de persona física. Si el registro es por parte de un Grupo 
                        Voluntario se deberá de colocar el nombre de un solo representante, esto con el fin de mantener contacto y tener referencia de la 
                        persona que ha postulado la candidatura.</span></div>
                </li>
                <li>
                    <div class="collapsible-header mainIcons"><i class="material-icons">subject</i>Bases para participar</div>
                    <div class="collapsible-body">
                        <span>
                            <p class=MsoNormal align=center style='margin-right:-4.65pt;text-align:center;
                            text-indent:.2pt'><b ><span
                            >BASES</span></b></p>
                            <p class=MsoNormal align=center style='margin-right:-4.65pt;text-align:center;
                            text-indent:.2pt'><b ><span
                            ><o:p>&nbsp;</o:p></span></b></p>

                            <p class=MsoNormal style='text-align:justify;text-indent:.2pt'><b
                            ><span style='text-transform: uppercase;font-size:10.0pt;line-height:
                            115%;'>Primera.</span></b><span style='font-size:10.0pt;line-height:115%;
                            '> El Premio Nacional de Protección Civil 2020, será conferido y
                            entregado a aquellas personas físicas o grupos voluntarios, mexicanos o
                            mexicanas que representen un ejemplo para la comunidad, por su esfuerzo en
                            acciones o medidas de autoprotección y autopreparación para enfrentar los
                            fenómenos naturales o de origen humano que pongan a la población en situación
                            de riesgo o de peligro, así como cuando se destaquen por su labor ejemplar en
                            la ayuda a la población ante la eventualidad de un desastre.</span><span
                            ></span></p>
                            <p class=MsoNormal style='text-align:justify;text-indent:.2pt'><b
                            ><span style='text-transform: uppercase;font-size:10.0pt;line-height:
                            115%;'>Segunda.</span></b><span style='font-size:10.0pt;line-height:115%;
                            '> En los términos del artículo 103 de la Ley de Premios, Estímulos y
                            Recompensas Civiles, los dos campos de este premio, en los que se puede
                            participar individual o colectivamente, son los siguientes:</span></p>

                            <p class=MsoNormal style='margin-left:.2pt;text-align:justify'><span
                            style='font-size:10.0pt;line-height:115%;'>I.- La Prevención, por las medidas
                            que se consideren de relevancia en materia de gestión integral de riesgos de
                            desastres, y</span></p>

                            <p class=MsoNormal style='text-align:justify;'><span
                            style='font-size:10.0pt;line-height:115%;'>II.- La Ayuda, por las acciones que
                            se hayan llevado a cabo en las tareas de auxilio a la población en caso de
                            desastre.</span></p>

                            <p class=MsoNormal style='text-align:justify;text-indent:.2pt'><b
                            ><span style='text-transform: uppercase;font-size:10.0pt;line-height:
                            115%;'>Tercera.</span></b><span style='font-size:10.0pt;line-height:115%;
                            '> Podrán ser beneficiarias del Premio Nacional de Protección Civil
                            2020, personas físicas o grupos voluntarios, mexicanos o mexicanas, que se
                            hayan significado en los campos mencionados en la base segunda de esta
                            Convocatoria.</span></p>

                            <p class=MsoNormal style='text-align:justify;text-indent:.2pt'><b
                            ><span style='text-transform: uppercase;font-size:10.0pt;line-height:
                            115%;'>Cuarta. </span></b><span style='font-size:10.0pt;line-height:115%;
                            '>Conforme a los artículos 4° y 92 de la Ley de Premios, Estímulos y
                            Recompensas Civiles, no podrán participar las personas que tengan la calidad de
                            servidores públicos seleccionados de entre aquéllos que prestan sus servicios
                            en las dependencias y entidades cuyas relaciones laborales se rigen por el
                            apartado &quot;B&quot; del artículo 123 de la Constitución Política de los
                            Estados Unidos Mexicanos.</span></p>

                            <p class=MsoNormal style='text-align:justify;text-indent:.2pt'><b
                            ><span style='text-transform: uppercase;font-size:10.0pt;line-height:
                            115%;'>Quinta.</span></b><span style='font-size:10.0pt;line-height:115%;
                            '> Las producciones, estudios, programas o acciones para salvaguardar
                            la vida, integridad y salud de la población, así como sus bienes; la
                            infraestructura, la planta productiva y el medio ambiente que fortalezca el
                            Sistema Nacional de Protección Civil y que acrediten el merecimiento del Premio
                            Nacional de Protección Civil 2020, deberán ser expresión de acciones realizadas
                            durante el período comprendido del 01 de enero de 2019 hasta la fecha en que se
                            publique la presente Convocatoria y que hayan significado un impacto a la
                            Protección Civil Nacional, así mismo, las personas o grupos voluntarios hayan
                            sido premiados en pasadas ediciones del Premio Nacional de Protección Civil, no
                            podrán ser postulados nuevamente.</span></p>

                            <p class=MsoNormal style='text-align:justify'><b ><span style='text-transform: uppercase;font-size:10.0pt;line-height:115%;'>Sexta.</span></b><span> </span>
                            <span style='font-size:10.0pt;line-height:115%;'>Conforme
                            lo establecido en los artículos 7, fracción IV, 8, 11, y 104 de la Ley de
                            Premios, Estímulos y Recompensas Civiles, el premio para cada uno de los dos
                            campos consistirá en un diploma firmado por el Presidente de los Estados Unidos
                            Mexicanos, por las y los miembros del Consejo de Premiación del Premio Nacional
                            de Protección Civil 2020 y por las y los integrantes del Jurado.</span></p>

                            <p class=MsoNormal style='text-align:justify;text-indent:.2pt'><span
                            style='font-size:10.0pt;line-height:115%;'>El Premio Nacional de Protección
                            Civil 2020 podrá otorgarse en un mismo campo a dos o más personas físicas o
                            grupos voluntarios, mexicanos o mexicanas, en el caso de las personas físicas
                            en grupo, solo se entregará un diploma.</span></p>

                            <p class=MsoNormal style='text-align:justify;text-indent:.2pt'><b
                            ><span style='text-transform: uppercase;font-size:10.0pt;line-height:
                            115%;'>Séptima</span></b><b ><span
                            >.</span></b><span > </span><span style='font-size:
                            10.0pt;line-height:115%;
                            '>Para la entrega del Premio, en caso de que la
                            candidatura ganadora incluya a un grupo de personas físicas, que cuente con más
                            de cuatro integrantes, se deberá nombrar un o una representante común para
                            recibir el premio. Para el caso de los grupos voluntarios, el premio se otorgará
                            por medio de su representante legal.</span></p>

                            <p class=MsoNormal style='margin-bottom:0cm;margin-bottom:.0001pt;text-align:
                            justify;text-indent:.2pt'><b ><span
                            style='text-transform: uppercase;font-size:10.0pt;line-height:115%;'>Octava.</span></b><span
                            > </span><span style='font-size:10.0pt;line-height:115%;font-family:
                            Montserrat;'>La
                            presentación de candidaturas, se efectuará ante el Consejo de Premiación del
                            Premio Nacional de Protección Civil 2020, por conducto de la Coordinación
                            Nacional de Protección Civil de la Secretaría de Seguridad y Protección
                            Ciudadana, en su carácter de Secretaría Técnica del referido Órgano Colegiado,
                            de forma física en el domicilio ubicado en Avenida José Vasconcelos número 221,
                            Piso 7, Colonia San Miguel Chapultepec, Alcaldía Miguel Hidalgo, Ciudad de
                            México, Código Postal 11850, en un horario de 09:00 a 18:00 horas, de lunes a
                            viernes, o bien en línea en la página: </span><a href="http://www.preparados.cenapred.unam.mx/PremioNacional2020">www.preparados.cenapred.unam.mx/PremioNacional2020</a>,
                            <span style='font-size:10.0pt;line-height:115%;
                            '>a partir de
                            la publicación de la presente convocatoria<span > 
                            <!-- </span>y hasta las 18:00 horas del día 03 de julio de 2020.</span></p> -->
                            </span>y hasta las 18:00 horas del día 14 de agosto de 2020.</span></p> 
                            <p class=MsoNormal style='margin-bottom:0cm;margin-bottom:.0001pt;text-align:
                            justify;text-indent:.2pt'><span style='font-size:10.0pt;line-height:115%;
                            '><o:p>&nbsp;</o:p></span></p>

                            <p class=MsoNormal style='text-align:justify'><span style='color:#c09c65;font-size:10.0pt;
                            line-height:115%;
                            '>La presentación de las candidaturas, para ser
                            puesta a consideración del Consejo de Premiación, deberá estar acompañada de
                            los documentos que lo avalen. Consulta la sección de <b>documentación necesaria</b> para más detalles.</span></p>
                            
                            <p class=MsoNormal style='text-align:justify;text-indent:.2pt'><b
                            ><span style='text-transform: uppercase;font-size:10.0pt;line-height:
                            115%;'>Novena.</span></b><span style='font-size:10.0pt;line-height:115%;
                            '> La Coordinación Nacional de Protección Civil de la Secretaría de
                            Seguridad y Protección Ciudadana, en su carácter de Secretaría Técnica del
                            Consejo de Premiación del Premio Nacional de Protección Civil 2020, verificará
                            que las candidaturas satisfagan los términos de la presente convocatoria y sus
                            bases para poder notificar al o la participante vía correo electrónico su
                            aceptación o rechazo, dentro de los quince días hábiles posteriores a la fecha
                            establecida como límite para la recepción de candidaturas.</span></p>

                            <p class=MsoNormal style='text-align:justify;text-indent:.2pt'><b
                            ><span style='text-transform: uppercase;font-size:10.0pt;line-height:
                            115%;'>Décima.</span></b><span style='font-size:10.0pt;line-height:115%;
                            '> La información que sea presentada con motivo de las candidaturas
                            propuestas para el otorgamiento del Premio Nacional de Protección Civil 2020,
                            estará sujeta a lo dispuesto por la Ley General de Protección de Datos
                            Personales en Posesión de Sujetos Obligados; así mismo si desea conocer el
                            aviso de privacidad, se encuentra disponible en la página de la Secretaría de
                            Seguridad y Protección Ciudadana.</span></p>

                            <p class=MsoNormal style='text-align:justify;text-indent:.2pt'><b
                            ><span style='text-transform: uppercase;font-size:10.0pt;line-height:
                            115%;'>Décima Primera.</span></b><span style='font-size:10.0pt;line-height:
                            115%;'> El Consejo de Premiación del Premio Nacional de Protección Civil
                            2020, integrará, a propuesta de las personas que tengan la calidad de miembros,
                            un Jurado compuesto por el número de integrantes que determinen en su primera
                            sesión, el cual no deberá ser menor a diez integrantes, quienes se sujetarán a
                            lo dispuesto por los artículos 16 a 18 y 20 a 23 de la Ley de Premios,
                            Estímulos y Recompensas Civiles. </span></p>

                            <p class=MsoNormal style='text-align:justify;text-indent:.2pt'><b
                            ><span style='text-transform: uppercase;font-size:10.0pt;line-height:
                            115%;'>Décima Segunda.</span></b><span style='font-size:10.0pt;line-height:
                            115%;'> El Jurado dictaminará sobre los expedientes de las candidaturas,
                            que le turne el Consejo de Premiación, dictámenes que serán entregados a la
                            Secretaría Técnica del Consejo de Premiación del Premio Nacional de Protección
                            Civil 2020, a más tardar el 14<b > </b>de
                            agosto 2020<b >, </b>los cuales deberán
                            contar con la mayoría de votos de los integrantes del jurado respectivo,<span
                            >  </span>a fin de someterlos a consideración del
                            Presidente de los Estados Unidos Mexicanos. </span></p>

                            <p class=MsoNormal style='text-align:justify;text-indent:.2pt'><b
                            ><span style='text-transform: uppercase;font-size:10.0pt;line-height:
                            115%;'>Décima Tercera.</span></b><span style='font-size:10.0pt;line-height:
                            115%;'> El Consejo de Premiación podrá declarar, la vacancia del premio,
                            en cualquiera de sus dos campos, si no sobreviene el reconocimiento que se
                            estatuye, con base en el dictamen del Jurado debidamente fundado y motivado. </span></p>

                            <p class=MsoNormal style='text-align:justify;text-indent:.2pt'><b
                            ><span style='text-transform: uppercase;font-size:10.0pt;line-height:
                            115%;'>Décima Cuarta.</span></b><span style='font-size:10.0pt;line-height:
                            115%;'> Los casos no previstos en la presente convocatoria, serán
                            resueltos en definitiva por el Consejo de Premiación del Premio Nacional de
                            Protección Civil 2020, de acuerdo a las disposiciones de la Ley de Premios,
                            Estímulos y Recompensas Civiles.</span></p>

                            <p class=MsoNormal style='margin-right:-2.05pt;text-align:justify;text-indent:
                            .2pt'><b ><span style='text-transform: uppercase;font-size:10.0pt;
                            line-height:115%;
                            '>Décima Quinta.</span></b><span
                            style='font-size:10.0pt;line-height:115%;'> Los y las participantes que no
                            resultaron ganadores del Premio Nacional de Protección Civil 2020, podrán
                            solicitar mediante escrito, ante la Secretaría Técnica del Consejo de
                            Premiación, la devolución de la documentación con la cual fueron postulados y/o
                            postuladas como candidatos y/o candidatas a dicho Premio, en un periodo no
                            mayor a dos meses a partir de la publicación del acuerdo presidencial en el que
                            se señalan a los ganadores. En caso de no requerir la devolución en el plazo
                            señalado, se entiende por autorizada su destrucción.</span></p>

                            <p class=MsoNormal style='margin-right:-2.05pt;text-align:justify;text-indent:
                            .2pt'><b ><span style='text-transform: uppercase;font-size:10.0pt;
                            line-height:115%;
                            '>Décima Sexta.</span></b><span
                            style='font-size:10.0pt;line-height:115%;'> Solo serán consideradas las
                            candidaturas que se entreguen directamente en la Secretaría Técnica del Consejo
                            de Premiación del Premio Nacional de Protección Civil 2020, o a través de la
                            página del premio señalada en la Base Octava de la presente convocatoria, y que
                            cumplan con los términos de la presente convocatoria y sus bases. </span></p>

                            <p class=MsoNormal style='margin-right:-2.05pt;text-align:justify;text-indent:
                            .2pt'><span style='font-size:10.0pt;line-height:115%;
                            '>No se
                            recibirán postulaciones por correo electrónico, de ser el caso serán
                            rechazadas.</span></p>

                            <p class=MsoNormal style='margin-right:-4.65pt;text-align:justify;text-indent:
                            .2pt'><b ><span style='text-transform: uppercase;font-size:10.0pt;
                            line-height:115%;
                            '>Décima Séptima</span></b><span
                            style='font-size:10.0pt;line-height:115%;'>. De acuerdo a lo establecido en el
                            artículo 105 de la Ley de Premios, Estímulos y Recompensas Civiles el Premio
                            será entregado el 19 de septiembre del año 2020.<span
                            >  </span></span></p>

                            <p class=MsoNormal style='text-align:justify;text-indent:.2pt'><span
                            style='font-size:10.0pt;line-height:115%;'>El Consejo de Premiación:</span></p>

                            <table class=a border=0 cellspacing=0 cellpadding=0 width=0 style='border-collapse:
                            collapse;'>
                                <tr>
                                <td width=518 valign=top style='width:388.15pt;padding:0cm .5pt 0cm .5pt'>
                                <p class=MsoNormal style='margin-right:-5.4pt;text-align:justify'><b
                                ><span style='font-size:10.0pt;line-height:
                                115%;'>Titular de la Secretaría de Seguridad y Protección Ciudadana
                                (Presidente)</span></b></p>
                                </td>
                                </tr>
                                <tr >
                                <td width=518 valign=top style='width:388.15pt;padding:0cm .5pt 0cm .5pt'>
                                <p class=MsoNormal style='text-align:justify'><b '><span style='font-size:10.0pt;line-height:115%;
                                '>Titular
                                de la Secretaría de la Defensa Nacional</span></b></p>
                                </td>
                                </tr>
                                <tr >
                                <td width=518 valign=top style='width:388.15pt;padding:0cm .5pt 0cm .5pt'>
                                <p class=MsoNormal style='margin-right:-4.65pt;text-align:justify'><b
                                ><span style='font-size:10.0pt;line-height:
                                115%;'>Titular de la Secretaría de Marina</span></b></p>
                                </td>
                                </tr>
                                <tr >
                                <td width=518 valign=top style='width:388.15pt;padding:0cm .5pt 0cm .5pt'>
                                <p class=MsoNormal style='text-align:justify'><b '><span style='font-size:10.0pt;line-height:115%;
                                '>Presidenta
                                de la Mesa Directiva de la H. Cámara de Diputados</span></b></p>
                                </td>
                                </tr>
                                <tr >
                                <td width=518 valign=top style='width:388.15pt;padding:0cm .5pt 0cm .5pt'>
                                <p class=MsoNormal style='text-align:justify'><b '><span style='font-size:10.0pt;line-height:115%;
                                '>Presidenta
                                de la Mesa Directiva de la H. Cámara de Senadores</span></b></p>
                                </td>
                                </tr>
                                <tr >
                                <td width=518 valign=top style='width:388.15pt;padding:0cm .5pt 0cm .5pt'>
                                <p class=MsoNormal style='margin-right:1.65pt;text-align:justify'><b
                                ><span style='font-size:10.0pt;line-height:
                                115%;'>Titular de la Coordinación Nacional de Protección Civil
                                (Secretario Técnico)</span></b></p>
                                </td>
                                </tr>
                                <tr >
                                <td width=518 valign=top style='width:388.15pt;padding:0cm .5pt 0cm .5pt'>
                                <p class=MsoNormal style='margin-right:1.65pt;text-align:justify'><b
                                ><span style='font-size:10.0pt;line-height:
                                115%;'>Titular de la Dirección General del Centro Nacional de Prevención
                                de Desastres</span></b></p>
                                </td>
                                </tr>
                                <tr >
                                <td width=518 valign=top style='width:388.15pt;padding:0cm .5pt 0cm .5pt'>
                                <p class=MsoNormal style='margin-right:1.65pt;text-align:justify'><b
                                ><span style='font-size:10.0pt;line-height:
                                115%;'>Rector de la Universidad Nacional Autónoma de México</span></b></p>
                                </td>
                                </tr>
                                <tr style='height:15.6pt'>
                                <td width=518 valign=top style='width:388.15pt;padding:0cm .5pt 0cm .5pt;
                                height:15.6pt'>
                                <p class=MsoNormal style='margin-right:1.65pt;text-align:justify'><b
                                ><span style='font-size:10.0pt;line-height:
                                115%;'>Titular de la Dirección General del Instituto Politécnico
                                Nacional</span></b></p>
                                </td>
                                </tr>
                                <tr >
                                <td width=518 valign=top style='width:388.15pt;padding:0cm .5pt 0cm .5pt'>
                                <p class=MsoNormal style='text-align:justify'><b><span style='font-size:10.0pt;line-height:115%;
                                '>Presidente
                                Nacional de la Cruz Roja Mexicana</span></b></p>
                                <p class=MsoNormal style='text-align:justify'><b><span style='font-size:10.0pt;line-height:115%;
                                '><o:p>&nbsp;</o:p></span></b></p>
                                <p class=MsoNormal style='text-align:justify'><b><span style='font-size:10.0pt;line-height:115%;
                                '><o:p>&nbsp;</o:p></span></b></p>
                                </td>
                                </tr>
                            </table>

                            <p class=MsoNormal align=right style='text-align:right'><b><span style='font-size:10.0pt;line-height:115%;
                            '>Ciudad de
                            México, a 29 de mayo de 2020.</span></b><b ><span
                            style='font-size:12.0pt;line-height:115%;'></span></b></p>
                        </span>
                    </div>
                </li>
                <li>
                    <div class="collapsible-header mainIcons"><i class="material-icons">attach_file</i>Documentación necesaria</div>
                    <div class="collapsible-body">
                        <span>
                            <ol>
                                <li><span style='font-size:10.0pt;line-height:115%;'>Carta de propuesta, la cual deberá ser emitida por un tercero,
                                    debidamente firmada en original dirigida al Consejo de Premiación, la cual
                                    deberá contener el nombre completo del candidato o la candidata, señalar el
                                    campo en que participa y señalar domicilio para recibir notificaciones y datos
                                    de contacto, tanto del o la proponente como del candidato o la candidata, en la
                                    que argumente y exponga los motivos por los cuales se considera que la
                                    candidatura es merecedora del premio,
                                    presentado con las siguientes características: Letra Arial 12, doble espacio,
                                    tamaño carta, sin sangría y margen justificado, márgenes laterales de 3 centímetros,
                                    y superior e inferior de 2.5 centímetros. Además el documento deberá contener
                                    los siguientes apartados: 1) Antecedentes; 2) Descripción de acciones y medidas
                                    a premiar; 3) Justificación y relevancia; 4) Alineación de las acciones con el
                                    Sistema Nacional de Protección Civil.</span></li>
                                <li><span style='font-size:10.0pt;line-height:115%;'>Carta de auto propuesta, firmada en original dirigida al Consejo de
                                    Premiación, la cual deberá contener el nombre completo, señalar el campo en que
                                    participa y señalar domicilio para recibir notificaciones y datos de contacto,
                                    en la que argumente y exponga los motivos por los cuales se considera que la
                                    candidatura es<span >  </span>merecedora del premio,
                                    presentado con las siguientes características: : Letra Arial 12, doble espacio,
                                    tamaño carta, sin sangría y margen justificado, márgenes laterales de 3
                                    centímetros, y superior e inferior de 2.5 centímetros. Además el documento
                                    deberá contener los siguientes apartados: 1) Antecedentes; 2) Descripción de
                                    acciones y medidas a premiar; 3) Justificación y relevancia; 4) Alineación de
                                    las acciones con el Sistema Nacional de Protección Civil.</span></li>
                                <li><span style='font-size:10.0pt;line-height:115%;'>Escrito de protesta de aceptación por parte del candidato o la
                                    candidata para participar y, en su caso, recibir el Premio Nacional de
                                    Protección Civil 2020, firmada en original.
                                    <br>
                                    En caso de un grupo de personas físicas, anexar relación de sus integrantes y documento en el que se haya
                                    designado a su representante. </span></li>
                                <li><span style='font-size:10.0pt;line-height:115%;'>Currículum Vitae actualizado del o de los candidato(s) propuesto(s)
                                    y/o de la o de las candidata(s) propuesta(s), incluyendo nombre, dirección
                                    completa, teléfono(s) y correo electrónico.</span></li>
                                <li><span style='font-size:10.0pt;line-height:115%;'>Copia del documento oficial con el que demuestre su nacionalidad
                                    mexicana:</span>
                                    <ul style="padding-left: 40px;">
                                        <li style="list-style: upper-latin;"><span style='font-size:10.0pt;line-height:115%;;color:black'>Para personas físicas
                                            mexicanas, individualmente o en grupo, copia del acta de nacimiento de cada
                                            participante. </span></li>
                                        <li style="list-style: upper-latin;"><span
                                            style='font-size:10.0pt;line-height:115%;;color:black'>Para personas morales,
                                            copia del acta constitutiva, debidamente protocolizada ante Notario Público y
                                            en caso de modificación, deberá presentar la protocolización del acta de
                                            Asamblea General, a fin de acreditar su legal existencia. </span></li>
                                    </ul>
                                </li>
                                <li><span style='font-size:10.0pt;line-height:115%;'>Copia o duplicado de materiales bibliográficos, audiovisuales,
                                    gráficos u otros que demuestren los motivos por los cuales se considera que el
                                    candidato o la candidata puede merecer el Premio Nacional de Protección Civil
                                    2020. </span></li>
                                <li><span style='font-size:10.0pt;line-height:115%;'>Los trabajos relacionados con herramientas tecnológicas e
                                    innovación deberán presentar resultados que sustenten su viabilidad y un
                                    análisis costo beneficio.</span></li>
                                <li><span style='font-size:10.0pt;line-height:115%;'>Escrito donde acepta conocer el Aviso de Privacidad del Premio
                                    Nacional de Protección Civil 2020 y estar conforme con el tratamiento que se le
                                    dará a sus datos.</span></li>
                            </ol>
                            <br>
                            <p class=MsoNormal style='margin-bottom:0cm;margin-bottom:.0001pt;text-align:justify'><span style='font-size:10.0pt;line-height:115%;'>En caso de
                                    faltar cualquier documento anteriormente enlistado, se hará del conocimiento
                                    del candidato, a fin de que complemente la documentación faltante y sea
                                    entregada antes del término señalado el párrafo primero de esta Base.</span></p>
                        </span>
                    </div>
                </li>
                <li>
                    <div class="collapsible-header mainIcons"><i class="material-icons">insert_drive_file</i>¿Cómo subir mis archivos?</div>
                    <div class="collapsible-body">
                        <span>
                        Los archivos mencionados en la convocatoria deben ser subidos a esta plataforma como un archivo .zip o .rar, 
                        este debe de pesar menos de 10 Megabytes y debe estar conformado por las siguientes carpetas con su respectiva 
                        nomenclatura en cada nombre del documento que se muestra en rojo:
                        </span>
                        <ul style="padding-left:inherit;">
                            <li style="list-style-type: circle;">Cartas.</li>
                                <ul style="padding-left:inherit;">
                                    <li style="list-style-type: square;">Carta de propuesta. <span class="nomenclatura">carta_propuesta_INICIALES.pdf</span></li>
                                    <li style="list-style-type: square;">Carta de auto propuesta. <span class="nomenclatura">carta_autopropuesta_INICIALES.pdf</span></li>
                                    <li style="list-style-type: square;">Carta de designación de representante (en caso de un grupo de personas físicas). <span class="nomenclatura">representante_oficial_INICIALES.pdf</span></li>
                                </ul>
                            <li style="list-style-type: circle;">Escritos.</li>
                                <ul style="padding-left:inherit;">
                                    <li style="list-style-type: square;">Escrito de protesta de aceptación. <span class="nomenclatura">escrito_propuesta_INICIALES.pdf</span></li>
                                    <li style="list-style-type: square;">Escrito de Aviso de Privacidad. <span class="nomenclatura">escrito_aviso_INICIALES.pdf</span></li>
                                </ul>
                            <li style="list-style-type: circle;">Documentos oficiales.</li>
                                <ul style="padding-left:inherit;">
                                    <li style="list-style-type: square;">Currículum Vitae. <span class="nomenclatura">CV_INICIALES.pdf</span></li>
                                    <li style="list-style-type: square;">Lista de participantes (en caso de grupo de personas físicas). <span class="nomenclatura">lista_participantes_INICIALES.pdf</span></li>
                                    <li style="list-style-type: square;">Acta de nacimiento de cada participante (en caso de grupo de personas físicas). <span class="nomenclatura">acta_nacimiento_INICIALES.pdf</span></li>
                                    <li style="list-style-type: square;">Copia del acta constitutiva (en caso de personas morales). <span class="nomenclatura">acta_constitutiva_INICIALES.pdf</span></li>
                                </ul>
                            <li style="list-style-type: circle;">Materiales extras.</li>
                                <ul style="padding-left:inherit;">
                                    <li style="list-style-type: square;">Materiales bibliográficos, audiovisuales, gráficos u otros* (en caso de que quepan dentro de los límites permitidos del ZIP o RAR). <span class="nomenclatura">ejemplo: video_de_muestra_INICIALES.wmv</span></li>
                                    <li style="list-style-type: square;">Análisis costo beneficio (solo para trabajos relacionados con herramientas tecnológicas). <span class="nomenclatura">costo_beneficio_INICIALES.pdf</span></li>
                                    <li style="list-style-type: square;">Archivo de texto (Word o PDF) con el listado de direcciónes electrónicas URL hacia los documentos multimedia. <span class="nomenclatura">enlaces_multimedia_INICIALES.pdf</span></li>
                                    <br>
                                    <span class="nomenclatura">Nota: Las INICIALES corresponden al nombre completo comenzando por APELLIDO PATERNO, MATERNO Y NOMBRE(S) de quién se postula, ya sea persona física, el o la representante de un grupo de personas físicas o el representante del Grupo voluntario. En cuanto a las iniciales en los documentos personales como CV y Áctas de nacimiento deben ir las iniciales de la persona a la que pertenece el documento.</span>
                                    <br><i>*En caso de que los archivos sean demasiado grándes y ya comprimidos ocupen más de 10 MB en el RAR o ZIP se deben enviar unicamente las ligas hacia estos documentos, para más detalles consulta la sección de "¿Cómo enviar mi evidencia audiovisual?".</i> 
                                </ul>
                        </ul>
                    </div>
                </li>
                <li>
                    <div class="collapsible-header mainIcons"><i class="material-icons">check</i>¿Cómo sabré que mi candidatura fue recibida?</div>
                    <div class="collapsible-body"><span>Al completar el siguiente formulario y darle clic al botón de "SUBIR DATOS" te mandará a una pantalla de confirmación de que tu información y tus archivos
                     han sido enviados. Espera en tu bandeja de entrada un email de confirmación para verificar que hemos recibido la candidatura. Recuerda revisar tu 
                     bandeja de SPAM así como de CORREO NO DESEADO según sea el caso. Si no has recibido respuesta de confirmación intenta nuevamente subir tu 
                     candidatura en un lapso de 3 horas. En forma reiterada te pedimos verifiques tu mail de contacto para evitar conflictos de envío de confirmación,
                      ante esto te recomendamos usar correos de dominio Gmail, Hotmail, Yahoo!, etc. Los archivos serán subidos al sistema en el momento de tu registro,
                       sin embargo deben ser revisados minuciosamente por el personal de la Coordinación Nacional de Protección Civil en un plazo no mayor a 15 días
                        hábiles, en caso de existir algún error en tu documentación o si falta algún archivo en tu registro recibirás un correo electrónico con las 
                        respectivas instrucciones para enviarlo nuevamente vía mail.</span></div>
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
                    <div class="row" id="div-tipos">
                        
                        <p> Selecciona tu tipo de registro: </p>
                        <p>
                            <label>
                                <input name="tipo" type="radio" value="Persona física">
                                <span>Una persona física.</span>
                            </label>
                        </p>
                        <p>
                            <label>
                                <input name="tipo" type="radio" value="Personas físicas">
                                <span>Un grupo de personas físicas.</span>
                            </label>
                        </p>
                        <p>
                            <label>
                                <input name="tipo" type="radio" value="Grupo voluntario">
                                <span>Grupo voluntario</span>
                            </label>
                        </p>
                    </div>
                    <div class="row" id="div-categorias">
                        <p>Selecciona una categoría por la cual estás participando:</p>
                        <p>
                        <label>
                            <input name="categoria" type="radio" value="Prevención">
                            <span>Prevención</span>
                        </label>
                        </p>
                        <p>
                        <label>
                            <input name="categoria" type="radio" value="Ayuda">
                            <span>Ayuda</span>
                        </label>
                        </p>
                    </div>
                    <div class="row">
                        <div class="input-field">
                            <i class="material-icons prefix">account_circle</i>
                            <input required placeholder="Nombre Completo" name="nombre" id="nombre" type="text" class="validate" maxlength="512" <?php if ($keep || isset($_POST['nombre'])) echo 'value="'.$_POST['nombre'].'"'; ?>>
                            <label for="nombre">Ingresa el nombre completo del representante</label>
                            <span class="helper-text" data-error="Completa este campo" data-success="Correcto"></span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="input-field">
                            <i class="material-icons prefix">phone</i>
                            <input placeholder="Ingresa número telefónico" name="telefono" id="telefono" type="text" data-length="10" maxlength="10" <?php if ($keep || isset($_POST['telefono'])) echo 'value="'.$_POST['telefono'].'"'; ?>>
                            <label for="telefono">Ingresa un número de contacto. (10 dígitos)</label>
                            <span class="helper-text" data-error="Debe ser un número de 10 dígitos" data-success="Correcto"></span>
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
                                <option value="" disabled selected>Elige el estado</option>
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
                                <option value="" disabled>Elige el municipio</option>
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
