<?php
    $today = time();
    $date_start = strtotime('2020-03-05 06:00:00.0');  // UTC for 5 March, 00:00 GMT-6 (Mexico City)
    $date_end = strtotime('2020-07-21 06:00:00.0');  // UTC for 20 May, 00:00 GMT-6 (Mexico City)
    $available = $today - $date_start >= 0 ? true : false;
    $expired = $today - $date_end >= 0 ? true: false;
    if ($available == false || $expired) {
        header("Location: http://www.preparados.gob.mx/macrosimulacro");
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


    //require_once("premio_fns.php");
    $keep = false;
    session_unset();
    if (
        isset($_POST['responsable']) && 
        isset($_POST['niveles']) &&
        isset($_POST['hipotesis']) &&
        isset($_POST['tipoInmueble']) &&
        isset($_POST['otro']) &&
        isset($_POST['dependencias']) &&
        isset($_POST['institucion']) &&
        isset($_POST['propiedad']) &&
        isset($_POST['pob_flotante']) &&
        isset($_POST['participantes']) &&
        isset($_POST['discapacidad']) &&
        isset($_POST['correo']) &&
        isset($_POST['Street']) &&
        isset($_POST['Neighborhood']) &&
        isset($_POST['Postal']) &&
        isset($_POST['County']) &&
        isset($_POST['State']) &&
        isset($_POST['Latitude']) &&
        isset($_POST['Longitude'])
    ) {
        if(sendForm()) {
            session_start();
            $_SESSION['correo'] = $_POST['correo'];
            header("Location: ./confirmacion");
        }
        else {
            $keep = true;
        }
    }
    
    function sendForm() {
        require_once('sendMail.php');
        $estado = trim($_POST['State']);
        $respon = substr(trim($_POST['responsable']), 0, 500);
        $correo = substr(trim(mb_strtolower($_POST['correo'])), 0, 500);

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $error_msg = 'El correo electrónico es inválido. Verifíca que esté bien escrito';
            return false;
        }
        else if (!isStateValid($estado)) {
            $error_msg = 'El estado es inválido. Selecciona una ubicación válida';
            return false;
        }
        else if (!isCountyValid($_POST['County'], $estado)) {
            $error_msg = 'El municipio es inválido. Selecciona una ubicación válida';
            return false;
        }
    }
    function isStateValid($Estado) {
        require_once("db_fns.php");
        require_once("db_global.php");

        $conn = dbConnect(user, pass, server);

        $paramsArray = Array(
            ":estado" => $Estado
        );

        $queryStr = "SELECT ESTADO FROM COLONIA2 WHERE ESTADO = :estado group by estado";

        $query = oci_parse($conn, $queryStr);

        foreach ($paramsArray as $key => $value) {
            oci_bind_by_name($query, $key, $paramsArray[$key]);
        }

        if (oci_execute($query)) {
            dbClose($conn, $query);
            return true;
        }
        else {
            dbClose($conn, $query);
            return false;
        }
    }
    function isCountyValid($county, $Estado) {
        require_once("db_fns.php");
        require_once("db_global.php");

        $conn = dbConnect(user, pass, server);

        $paramsArray = Array(
            ":nombre" => trim($county),
            ":estado" => $Estado
        );

        $queryStr = "SELECT MUNICIPIO FROM COLONIA2 WHERE ESTADO = :estado AND LOWER(MUNICIPIO) = LOWER(:nombre) group by municipio";

        $query = oci_parse($conn, $queryStr);

        foreach ($paramsArray as $key => $value) {
            oci_bind_by_name($query, $key, $paramsArray[$key]);
        }

        if (oci_execute($query)) {
            dbClose($conn, $query);
            return true;
        }
        else {
            dbClose($conn, $query);
            return false;
        }
    }
    function isCountyValid2($county, $idEstado) {
        require_once("db_fns.php");
        require_once("db_global.php");

        $conn = dbConnect(user, pass, server);

        $paramsArray = Array(
            ":nombre" => trim($county),
            ":id" => $idEstado
        );

        $queryStr = "SELECT ID_MUNICIPIO FROM MUNICIPIO ".
            "WHERE ID_ESTADO = :id AND LOWER(NOMBRE) = LOWER(:nombre) ";

        $query = oci_parse($conn, $queryStr);

        foreach ($paramsArray as $key => $value) {
            oci_bind_by_name($query, $key, $paramsArray[$key]);
        }

        if (oci_execute($query)) {
            dbClose($conn, $query);
            return true;
        }
        else {
            dbClose($conn, $query);
            return false;
        }
    }
    function getEstados() {
        require_once("db_fns.php");
        require_once("db_global.php");

        $conn = dbConnect(user, pass, server);

        $queryStr = "select estado id, initcap(estado) estado from colonia2 where estado != 'DISTRITO FEDERAL' group by estado order by estado asc";

        $query = oci_parse($conn, $queryStr);

        $resultados = Array();

        oci_execute($query);

        while ( ($row = oci_fetch_assoc($query)) != false) {
            $resultados[] = [
                'estado' => $row['ESTADO'],
                'id' => $row['ID'],
            ];
        }
        dbClose($conn, $query);
        return $resultados;
    }
    function getEstados2() {
        require_once("db_fns.php");
        require_once("db_global.php");

        $conn = dbConnect(user, pass, server);

        $queryStr = "SELECT * FROM ESTADO";

        $query = oci_parse($conn, $queryStr);

        $resultados = Array();

        oci_execute($query);

        while ( ($row = oci_fetch_assoc($query)) != false) {
            $resultados[] = ['id_estado'=>$row['ID_ESTADO'], 'nombre'=>$row['NOMBRE']];
        }
        dbClose($conn, $query);
        return $resultados;
    }
    function registrarInmueble2($parametros) {
        require_once("db_fns.php");
        require_once("db_global.php");

        $conn = dbConnect(user, pass, server);

        $paramsArray = Array(
            ":estado" => $parametros["estado"],
            ":municipio" => $parametros["municipio"],
            ":lugar" => $parametros["lugar"],
            ":hipotesis" => $parametros["hipotesis"],
            ":dependencias" => $parametros["dependencias"],
            ":participantes" => $parametros["participantes"],
            ":hora" => $parametros["hora"],
            ":correo" => trim($parametros["correo"]),
            ":lat" => $parametros["lat"],
            ":lon" => $parametros["lon"],
            ":responsable" => $parametros["responsable"],
            ":niveles" => $parametros["niveles"],
            ":discapacidad" => $parametros["discapacidad"],
            ":tipo" => $parametros["tipo"],
            ":institucion" => $parametros["institucion"],
            ":propiedad" => $parametros["propiedad"],
            ":pob_flotante" => $parametros["pob_flotante"],
        );

        if ($parametros["estado"] == 2 || $parametros["estado"] == "2"){
            $paramsArray[":tipo_sim"] = $parametros["tipo_simulacro"];
            $queryStr = "INSERT INTO MAYO2020 (ESTADO, MUNICIPIO, LUGAR, HIPOTESIS, DEPENDENCIAS, PARTICIPANTES, HORA, CORREO, LAT, LON, RESPONSABLE, TIPOINMUEBLE, NIVELES, DISCAPACIDAD, INSTITUCION, PROPIEDAD, POB_FLOT, TIPO_SIMULACRO) ".
            "VALUES (:estado, :municipio, :lugar, :hipotesis, :dependencias, :participantes, :hora, :correo, :lat, :lon, :responsable, :tipo, :niveles, :discapacidad, :institucion, :propiedad, :pob_flotante, :tipo_sim)";
        }
        else {
            $queryStr = "INSERT INTO MAYO2020 (ESTADO, MUNICIPIO, LUGAR, HIPOTESIS, DEPENDENCIAS, PARTICIPANTES, HORA, CORREO, LAT, LON, RESPONSABLE, TIPOINMUEBLE, NIVELES, DISCAPACIDAD, INSTITUCION, PROPIEDAD, POB_FLOT) ".
            "VALUES (:estado, :municipio, :lugar, :hipotesis, :dependencias, :participantes, :hora, :correo, :lat, :lon, :responsable, :tipo, :niveles, :discapacidad, :institucion, :propiedad, :pob_flotante)";

        }


        $query = oci_parse($conn, $queryStr);

        foreach ($paramsArray as $key => $value) {
            oci_bind_by_name($query, $key, $paramsArray[$key]);
        }

        if(oci_execute($query)) {
            dbClose($conn, $query);
            return true;
        } else {
            dbClose($conn, $query);
            return false;
        }
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

        <!-- <div class="row">
            <a class="btn-small" href="http://www.preparados.gob.mx/macrosimulacro">Página inicial</a>
        </div> -->

        <div class="fixed-action-btn" style="position: fixed; bottom: 5em;">
            <a target="_blank" href="http://www.preparados.gob.mx/blog" class="btn-floating btn blue"><i class="material-icons">help</i></a>
        </div>

        <?php if (isset($error_msg)) { ?>
            <div id="div-error" class="valign-wrapper center" style="background-color: lightcoral;">
                <i class="material-icons alerted" style="margin-left: 1em;">error_outline</i>
                <p style="width: 100%;"><?=$error_msg?></p>
                <button id="btn-error-close" class="btn-small" type="button" style="margin-right: 1em;"><i class="material-icons">close</i></button>
            </div>
        <?php } ?>

        <form method="post" id="submit-form">
                <p class="center">Registro de candidaturas para el Premio Nacional de Protección Civil 2020</p>
                <div id="primera-parte" class="row">
                    <div class="row">
                        <h6 class="center">Ingresa los siguientes campos</h6>
                    </div>
                    <div class="row">
                        <div class="input-field">
                            <i class="material-icons prefix">account_circle</i>
                            <input required placeholder="Nombre Completo" name="responsable" id="responsable" type="text" class="validate" data-length="200" maxlength="200" <?php if ($keep) echo 'value="'.$_POST['responsable'].'"'; ?>>
                            <label for="responsable">Ingresa tu nombre completo</label>
                            <span class="helper-text" data-error="Completa este campo" data-success="Correcto"></span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="input-field">
                            <i class="material-icons prefix">phone</i>
                            <input placeholder="Ingresa número telefónico" name="contacto" id="contacto" type="text" class="validate" data-length="200" maxlength="200" <?php if ($keep) echo 'value="'.$_POST['telefono'].'"'; ?>>
                            <label for="contacto">Ingresa un número de contacto</label>
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
                        <h6 class="center">Dirección del Inmueble</h6>
                    </div>
                    <div class="row" id="location-helper">
                        <p>Selecciona tu estado, municipio y colonia</p>
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
                        <button class="btn-large disabled" type="button" id="btn-continuar"><i class="material-icons right">arrow_forward</i>
                            Continuar
                        </button>
                        <button id="btn-submit" class="btn-large modal-trigger disabled" data-target="modal"><i class="material-icons right">send</i>
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
                        <button type="submit" class="btn modal-close">Aceptar</button>
                        <button type="button" data-target="modal" class="btn-flat modal-close">Cancelar</button>
                    </div>
                </div>
                <div id="error-modal" class="modal">
                    <div class="modal-content">
                        <h4>Error</h4>
                        <p>Haz seleccionado una ubicación equivocada</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" data-target="modal" class="btn modal-close">Entendido</button>
                    </div>
                </div>
                <div id="wait-modal" class="modal">
                    <div class="modal-content">
                        <h4>Espera</h4>
                        <p>Espera a que el mapa cargue, esto puede tardar unos segundos.</p>
                        <div class="progress" id="mapa-loading">
                            <div class="indeterminate"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        
                    </div>
                </div>
                <div class="mapContainer" id="mapa-container" style="height: 1px;">
                    <div id="map"></div>
                    <div class="logos">
                        <img src="http://atlasnacionalderiesgos.gob.mx/rutasvolcan/images/SSyPC_CNPC_CENACOM_blanco.png" alt="CENACOM">
                    </div>
                </div>
            </form>

    </div>
    
    <script type="text/javascript" src="js/formulario.js"></script>
</body>
</html>
