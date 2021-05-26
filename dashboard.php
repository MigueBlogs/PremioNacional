<?php
    session_start();
    require_once("dash_fns.php");
    #Validando sesión...
    //echo(json_encode($_SESSION));
    if(!isset($_SESSION["username"])) {
		$_SESSION['username'] = $ar["username"];
		$host  = $_SERVER['HTTP_HOST'];
		$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
		$extra = 'login.php';
        header("Location: http://$host$uri/$extra");
        die(); // detiene la ejecución de código subsecuente
    }
    $allowTypes = array('zip','rar');
    $max_file_size = 10485760; // 10MB
    $target_dir = "/var/www/html/uploads/premionacional2021/";
    //$target_dir = "js/"; //only for develop
    

    if (isset($_FILES["archivo"])){sendFile();}

    function sendFile() {
        global $target_dir, $error_msg, $success_msg, $keep;
        $id_update = trim($_POST['id_update']);

        if(validateFile()){
            $imageFileType = strtolower(pathinfo($target_dir . basename($_FILES['archivo']['name']), PATHINFO_EXTENSION));
            $tmp_uid = uniqid();
            $target_file = $target_dir . $tmp_uid . "." . $imageFileType;
            $url_file = "http://www.preparados.cenapred.unam.mx/uploads/premionacional2021/". $tmp_uid . "." . $imageFileType;
            //$url_file = "js/". $tmp_uid . "." . $imageFileType; //only for develop
            if (!move_uploaded_file($_FILES["archivo"]["tmp_name"], $target_file)){
                $error_msg = 'No se pudo subir tu archivo al servidor';
                $keep = true;
                return;
            }
            
            $datos = [
                ":id"=>$id_update,
                ":archivo"=>$url_file
            ];
            
            if (editarArchivo($datos)) {
                $success_msg = 'Registro actualizado correctamente';
                //unset($_POST);
            }
            else {
                unlink($target_file);
                $error_msg = 'No se pudo actualizar el registro de tu candidatura.';
            }
        }
        if ($error_msg) $keep = true;    
    }

    function validateFile() {
        global $error_msg, $allowTypes, $max_file_size, $target_dir;
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
    $Arr_Candidaturas = getCandidaturas();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="shortcut icon" href="http://www.atlasnacionalderiesgos.gob.mx/Imagenes/Logos/cenapred_icon.ico"/>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1.0, maximum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Información</title>
    <!-- JQUERY -->
    <script  src="https://code.jquery.com/jquery-3.3.1.min.js"  integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="  crossorigin="anonymous"></script>
    <!-- Materialize -->
        <!-- Compiled and minified CSS -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
        <!-- Compiled and minified JavaScript -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <!-- Script D3 for Graphics v3 -->
    <script src="//d3js.org/d3.v3.min.js"></script>
    <script src="http://labratrevenge.com/d3-tip/javascripts/d3.tip.v0.6.3.js"></script>
    <script src="https://d3js.org/topojson.v1.min.js"></script>
    <!-- Iconos -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <!--CSS -->
    <link type="text/css" rel="stylesheet" href="./CSS/graphics.css">
    <link type="text/css" rel="stylesheet" href="./CSS/main.css">
    
</head>
<body>
    <div class="container">
        <!-- User Data -->
        <h6 class="right">
        <i class="small material-icons" style="vertical-align: middle;">perm_identity</i>Bienvenido: <?php echo($_SESSION['name']); echo(' '.$_SESSION['lastname'])?>
        </h6>
        <!-- Dropdown Button 1 -->
        <a class='dropdown-trigger btn guinda white-text' data-target='dropdown1'><i class="material-icons left">menu</i>Menú de Opciones</a>
        <!-- Dropdown Structure 1 -->
        <ul id='dropdown1' class='dropdown-content'>
            <li><a class="dorado-text" id="VGen">Vista General de Candidaturas</a></li>
            <li><a class="dorado-text" id="VAccesos"><i class="material-icons prefix">vpn_key</i>Tablero de accesos</a></li>
            <li class="divider" tabindex="-1"></li>
            <li><a class="guinda white-text" href="./logout.php"><i class="material-icons">power_settings_new</i>Cerrar sesión</a></li>
        </ul>
        <br>
        <br>
        <div class="center">
            <img class="cnpc" src="http://www.atlasnacionalderiesgos.gob.mx/Imagenes/Logos/SSyPC_CNPC_h.png" alt="gob">
        </div>
    
        <!--Contenido principal-->
              
        <div id="main-container">
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
            <div id="VistaGeneral"  class="vista">
                <h3 class="titleMex center"> Vista General de Inscripciones </h3>
                <h6 class="right"> Ciudad de México, a <span class="fecha"></span></h6>
                <br>
                <div class="center">
                <h4 class="niceTitle">Premio Nacional de Protección Civil 2021<h4>
                <h5 class="niceTitle">Tablas de Candidaturas:<h5>
                </div>
                <h6></h6>
                <br>
                
                <p> Candidaturas registradas en total: <strong class="dato"><?=count($Arr_Candidaturas)?></strong></p>
                <p> Tabla de candidaturas en la categoría de <strong>Prevención</strong>:</p>
                <h5 id="prev" class="niceTitle">Aún no existen registros en la categoría de prevención</h5>
                    <table id="vistageneral-tabla" class="responsive-table striped highlight">
                        <thead>
                            <tr class="headersTable">
                                <th class="center">#</th>
                                <th class="center">Nombre</th>
                                <th class="center">Correo</th>
                                <th class="center">Teléfono</th>
                                <th class="center">Archivo</th>
                                <th class="center">Municipio</th>
                                <th class="center">Estado</th>
                                <th class="center">Fecha reg.</th>
                                <th class="center">Tipo de candidatura</th>
                                <th class="center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        foreach( $Arr_Candidaturas as $candidatura){
                            if ($candidatura["categoria"] == "Prevención") { ?>
                            <tr class="headersTable">
                                <td class="center">D<?=$candidatura["id"]?></td>
                                <td class="center"><?=$candidatura["nombre"]?></td>
                                <td class="center"><a href="mailto:<?=$candidatura["correo"]?>"><?=$candidatura["correo"]?></a></td>
                                <td class="center"><?=$candidatura["telefono"]?></td>
                                <td class="center" style="padding: 10px 0;"><a target="_blank" style="color:#9D2449;" href=<?=$candidatura["archivo"]->load()?>><i class="material-icons">insert_drive_file</i></a></td>
                                <td class="center"><?=$candidatura["municipio"]?></td>
                                <td class="center"><?=$candidatura["estado"]?></td>
                                <td class="center"><?=$candidatura["fecha"]?></td>
                                <td class="center"><?=$candidatura["tipo"]?></td>
                                <td><button class="btn guinda tooltipped borrar" data-position="top" data-tooltip="Borrar registro" id="btn-borrar-<?=$candidatura["id"]?>"><i class="material-icons">delete</i></button>
                                <button class="btn edit tooltipped editar" data-position="top" data-tooltip="Editar archivos" id="btn-editar-<?=$candidatura["id"]?>"><i class="material-icons">edit</i></button></td>
                            </tr>
                            <?php }
                        }
                        ?>
                        </tbody>
                    </table>
                    <div id="des1" class="hide-on-med-and-up">Desliza hacia la derecha para ver las candidaturas</div>
                <br>
                <p> Tabla de candidaturas en la categoría de <strong>Ayuda</strong>:</p>
                <h5 id="help" class="niceTitle">Aún no existen registros en la categoría de ayuda</h5>
                    <table id="vistageneral-tabla2" class="responsive-table striped highlight">
                        <thead>
                            <tr class="headersTable">
                                <th class="center">#</th>
                                <th class="center">Nombre</th>
                                <th class="center">Correo</th>
                                <th class="center">Teléfono</th>
                                <th class="center">Archivo</th>
                                <th class="center">Municipio</th>
                                <th class="center">Estado</th>
                                <th class="center">Fecha reg.</th>
                                <th class="center">Tipo de candidatura</th>
                                <th class="center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        foreach( $Arr_Candidaturas as $Arr_Candidatura){
                            if ($Arr_Candidatura["categoria"] == "Ayuda") { ?>
                                <tr class="headersTable">
                                <td class="center">D<?=$Arr_Candidatura["id"]?></td>
                                <td class="center"><?=$Arr_Candidatura["nombre"]?></td>
                                <td class="center"><a href="mailto:<?=$Arr_Candidatura["correo"]?>"><?=$Arr_Candidatura["correo"]?></a></td>
                                <td class="center"><?=$Arr_Candidatura["telefono"]?></td>
                                <td class="center" style="padding: 10px 0;"><a style="color:#9D2449;" href=<?=$Arr_Candidatura["archivo"]->load()?>><i class="material-icons">insert_drive_file</i></a></td>
                                <td class="center"><?=$Arr_Candidatura["municipio"]?></td>
                                <td class="center"><?=$Arr_Candidatura["estado"]?></td>
                                <td class="center"><?=$Arr_Candidatura["fecha"]?></td>
                                <td class="center"><?=$Arr_Candidatura["tipo"]?></td>
                                <td><button class="btn guinda tooltipped borrar" data-position="top" data-tooltip="Borrar registro" id="btn-borrar-<?=$candidatura["id"]?>"><i class="material-icons">delete</i></button>
                                <button class="btn edit tooltipped editar" data-position="top" data-tooltip="Editar archivos" id="btn-editar-<?=$candidatura["id"]?>"><i class="material-icons">edit</i></button></td>
                            </tr>
                            <?php }
                        }
                        ?>
                        </tbody>
                    </table>
                    
                    <div id="des2" class="hide-on-med-and-up">Desliza hacia la derecha para ver las candidaturas</div>
                    <!-- MODALS -->
                    <div id="modal-borrar" class="modal">
                        <div class="modal-content">
                            <h4>Confirmar</h4>
                            <p>¿Estás seguro de eliminar el registro <strong id="borrar-registro-id"></strong> de
                            <strong id="borrar-registro-nombre"></strong> (<strong id="borrar-registro-correo"></strong>)?
                            </p>
                            <p class="red-text valign-wrapper"><i class="material-icons">warning</i>Esta acción no se puede revertir ni tampoco será posible recuperar los datos previamente guardados.</p>
                        </div>
                        <div class="modal-footer">
                            <button id="borrar-registro-aceptar" class="btn modal-close guinda">Borrar</button>
                            <button data-target="modal-borrar" class="btn modal-close verde-oscuro">Cancelar</button>
                        </div>
                    </div>
                    <div id="modal-editar" class="modal">
                        <form method="post" id="submit-form" enctype="multipart/form-data">
                            <div class="modal-content">
                                <h4>Editar</h4>
                                <p>Haz seleccionado el registro <strong id="editar-registro-id"></strong> de
                                <strong id="editar-registro-nombre"></strong> (<strong id="editar-registro-correo"></strong>) para editar su documentación.
                                </p>
                                <div class="row">
                                    <h6 class="center">Subir NUEVO documento de la candidatura</h6>
                                </div>
                                <input name="id_update" id="id_update" type="text" class="validate" style="display:none;">
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
                                <p class="red-text valign-wrapper"><i class="material-icons">warning</i>Recuerda que al subir el nuevo ZIP o RAR se reemplazará el archivo anterior. No será posible recuperar archivos anteriores.</p>
                            </div>
                        </form>
                        <div class="modal-footer">
                            <button type="submit" form="submit-form" id="btn-update-file" class="btn modal-close disabled guinda">Subir documentos</button>
                            <button data-target="modal-editar" class="btn modal-close verde-oscuro">Cancelar</button>
                        </div>
                    </div>
                    <div id="wait-modal" class="modal">
                        <div class="modal-content center">
                            <h4>Subiendo nuevos archivos...</h4>
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
                            <div id="error-modal" class="modal">
                                <div class="modal-content">
                                    <h4>Error</h4>
                                    <p>Verifica tus datos</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" data-target="modal" class="btn modal-close">Entendido</button>
                                </div>
                            </div>
                            <p>Por favor no cierres ni actualices la página hasta que se haya completado este proceso.</p>
                        </div>
                    </div>
                <p> Estados participantes: </p>
                    <div id="barrasConstancia" style="height: 400px;" class="slideAnimation">
                        <div id="barsvg" class="adjustSize"> </div>
                        <div id="barSvgTop" class="adjustSize"> </div>
                    </div>
            </div>
            <div id="VistaAccesos" class="vista">
                <h3 class="titleMex"> Vista de último acceso a la sesión </h3>
                    <div class="container">
                        <table id="vistaAccesos-tabla">
                            <tr class="headersTable">
                                <th>Usuario</th>
                                <th style="text-align: center !important;">Acceso</th>
                            </tr>
                            <?php
                            $Arr_Usuarios = getUsuarios();
                            
                            foreach( $Arr_Usuarios as $Arr_Usuario){
                                if($Arr_Usuario["acceso"]==null) $Arr_Usuario["acceso"]="-";
                                echo '<tr>
                                        <td style="text-align: left !important;">
                                            <span id="apellidos">'.$Arr_Usuario["apellidoUsuario"].'</span>
                                        </td>
                                        <td style="text-align: center !important;">
                                            <span id="lastAccess">'.$Arr_Usuario["acceso"].'</span>
                                        </td>
                                      </tr>';
                            };
                            ?>
                        </table>
                    </div>
            </div>
        </div>
    </div>
    <script src="./js/dashboard.js"></script>
    <script>
        var porcentajeEstados = <?php echo json_encode(getEstadosCandidaturas()); ?>
    </script>
    <script src="./js/grafica.js"></script>
    
</body>
</html>