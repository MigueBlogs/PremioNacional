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
        $municipio = intval($_POST['municipio']);

        if (validateForm($nombre, $correo, $telefono, $estado, $municipio)){
            
            $imageFileType = strtolower(pathinfo($target_dir . basename($_FILES['archivo']['name']), PATHINFO_EXTENSION));

            $tmp_uid = uniqid();
            $target_file = $target_dir . $tmp_uid . "." . $imageFileType;
            $url_file = "http://www.preparados.gob.mx/uploads/premionacional2020/". $tmp_uid . "." . $imageFileType;
            if (!move_uploaded_file($_FILES["archivo"]["tmp_name"], $target_file)){
                $error_msg = 'No se pudo subir tu archivo';
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
        
        if (!file_exists($_FILES['archivo']['tmp_name']) || !is_uploaded_file($_FILES['archivo']['tmp_name'])){
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
            $error_msg = 'No se pudo subir tu archivo';
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
                <div class="collapsible-body"><span>Bienvenido al portal de registro de Candidaturas para el Premio Nacional de Protección Civil, para iniciar deberás ingresar tus datos de contacto como: Nombre completo (obligatorio), correo electrónico y un Número telefónico de contacto (opcional)</span></div>
                </li>
                <li>
                <div class="collapsible-header mainIcons"><i class="material-icons">insert_drive_file</i>¿Cómo subir mis archivos?</div>
                <div class="collapsible-body"><span>Lorem ipsum dolor sit amet.</span></div>
                </li>
                <li>
                <div id="lastOne" class="collapsible-header mainIcons"><i class="material-icons">videocam</i>¿Cómo enviar mi evidencia audiovisual?</div>
                <div class="collapsible-body"><span>Lorem ipsum dolor sit amet.</span></div>
                </li>
            </ul>
            <button class="btn-large disabled guinda" type="button" id="btn-continuar" style="pointer-events:unset;"><i class="material-icons right">arrow_forward</i>
            Iniciar registro
            </button>
        </div>
        <form method="post" id="submit-form" style="display:none;" enctype="multipart/form-data">
        <!-- <form method="post" id="submit-form"> -->
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
                
            </form>

    </div>
    
    <script type="text/javascript" src="js/formulario.js"></script>
</body>
</html>
