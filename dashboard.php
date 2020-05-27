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
        <a class='dropdown-trigger btn' data-target='dropdown1'> ≡ Menú de Opciones</a>
        <!-- Dropdown Structure 1 -->
        <ul id='dropdown1' class='dropdown-content'>
            <li><a id="VGen">Vista General de Candidaturas</a></li>
            <li><a id="VAccesos"><i class="material-icons prefix">vpn_key</i>Tablero de accesos</a></li>
            <li class="divider" tabindex="-1"></li>
            <li><a href="./logout.php"><i class="material-icons">power_settings_new</i>Cerrar sesión</a></li>
        </ul>
        <br>
        <br>
        <div class="center">
            <img class="cnpc" src="http://www.atlasnacionalderiesgos.gob.mx/Imagenes/Logos/SSyPC_CNPC_h.png" alt="gob">
        </div>
    
        <!--Contenido principal-->
              
        <div id="main-container">
            <div id="VistaGeneral"  class="vista">
                <h3 class="titleMex center"> Vista General de Inscripciones </h3>
                <h6 class="right"> Ciudad de México, a <span class="fecha"></span></h6>
                <br>
                <div class="center">
                <h4 class="niceTitle">Premio Nacional de Protección Civil 2020<h4>
                <h5 class="niceTitle">Tablas de Candidaturas:<h5>
                </div>
                <h6></h6>
                <br>
                <?php $Arr_Candidaturas = getCandidaturas(); ?>
                <p> Candidaturas registradas en total: <strong class="dato"><?=count($Arr_Candidaturas)?></strong></p>
                <p> Tabla de candidaturas en la categoría de <strong>Prevención</strong>:</p>
                    <table id="vistageneral-tabla" class="responsive-table striped highlight">
                        <thead>
                            <tr id="headersTable">
                                <th># candidatura</th>
                                <th class="center">Nombre</th>
                                <th class="center">Correo</th>
                                <th class="center">Teléfono</th>
                                <th class="center">Archivo</th>
                                <th class="center">Municipio</th>
                                <th class="center">Estado</th>
                                <th class="center">Fecha reg.</th>
                                <th class="center">Tipo de candidatura</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        foreach( $Arr_Candidaturas as $candidatura){
                            if ($candidatura["categoria"] == "Prevención") { ?>
                            <tr>
                                <td class="center"><?=$candidatura["id"]?></td>
                                <td class="center"><?=$candidatura["nombre"]?></td>
                                <td class="center"><a href="mailto:<?=$candidatura["correo"]?>"><?=$candidatura["correo"]?></a></td>
                                <td class="center"><?=$candidatura["telefono"]?></td>
                                <td class="center"><a style="color:#9D2449;" href=<?=$candidatura["archivo"]->load()?>><i class="material-icons">insert_drive_file</i></a></td>
                                <td class="center"><?=$candidatura["municipio"]?></td>
                                <td class="dato center"><?=$candidatura["estado"]?></td>
                                <td class="dato center"><?=$candidatura["fecha"]?></td>
                                <td class="center"><?=$candidatura["tipo"]?></td>
                                <td><button class="btn guinda tooltipped borrar" data-position="top" data-tooltip="Borrar registro" id="btn-borrar-<?=$candidatura["id"]?>"><i class="material-icons">delete</i></button></td>
                            </tr>
                            <?php }
                        }
                        ?>
                        </tbody>
                    </table>
                <br>
                <p> Tabla de candidaturas en la categoría de <strong>Ayuda</strong>:</p>
                    <table id="vistageneral-tabla2" class="responsive-table striped highlight">
                        <thead>
                            <tr id="headersTable2">
                                <th class="center"># candidatura</th>
                                <th class="center">Nombre</th>
                                <th class="center">Correo</th>
                                <th class="center">Teléfono</th>
                                <th class="center">Archivo</th>
                                <th class="center">Municipio</th>
                                <th class="center">Estado</th>
                                <th class="center">Fecha reg.</th>
                                <th class="center">Tipo de candidatura</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        foreach( $Arr_Candidaturas as $Arr_Candidatura){
                            if ($Arr_Candidatura["categoria"] == "Ayuda") { ?>
                                <tr>
                                <td class="center"><?=$Arr_Candidatura["id"]?></td>
                                <td class="center"><?=$Arr_Candidatura["nombre"]?></td>
                                <td class="center"><a href="mailto:<?=$Arr_Candidatura["correo"]?>"><?=$Arr_Candidatura["correo"]?></a></td>
                                <td class="center"><?=$Arr_Candidatura["telefono"]?></td>
                                <td class="center"><a style="color:#9D2449;" href=<?=$Arr_Candidatura["archivo"]->load()?>><i class="material-icons">insert_drive_file</i></a></td>
                                <td class="center"><?=$Arr_Candidatura["municipio"]?></td>
                                <td class="dato center"><?=$Arr_Candidatura["estado"]?></td>
                                <td class="dato center"><?=$Arr_Candidatura["fecha"]?></td>
                                <td class="center"><?=$Arr_Candidatura["tipo"]?></td>
                                <td><button class="btn guinda tooltipped borrar" data-position="top" data-tooltip="Borrar registro" id="btn-borrar-<?=$candidatura["id"]?>"><i class="material-icons">delete</i></button></td>
                            </tr>
                            <?php }
                        }
                        ?>
                        </tbody>
                    </table>
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
                            <tr id="headersTable">
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