<?php
    $today = time();
    $date_start = strtotime('2020-03-05 06:00:00.0');  // UTC for 5 March, 00:00 GMT-6 (Mexico City)
    $date_end = strtotime('2020-03-21 06:00:00.0');  // UTC for 20 May, 00:00 GMT-6 (Mexico City)
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


    require_once("macro_fns.php");
    $possible_niveles = array("1-3","4-9","10-19","20-29","30 o más");
    $possible_hipotesis = getHipotesis();
    $possible_participantes = array("1-9","10-49","50-99","100-499","500 o más");
    $possible_discapacitados = array("0","1-9","10-49","50-99","100-499","500 o más");

    $possible_institucion = array('F'=>'Federal', 'E'=>'Estatal', 'M'=>'Municipal', 'P'=>'Privado');
    $possible_propiedad = array('P'=>'Propio', 'A'=>'Arrendado', 'C'=>'Comodato');
    //$possible_pob_fija = array("1-9","10-49","50-99","100-499","500 o más");
    $possible_pob_flotante = array("0","1-9","10-49","50-99","100-499","500 o más");
    $tipo_ejercicio = array("N"=>"Nacional, el día 21 de Mayo",
                            "E"=>"Estatal, el día 3 de Abril",
                            //"M"=>"Municipal"
                        );

    $max_discapacidad = 9999; // 9,999
    $hora = '16:00';
    $keep = false;
    $tipoInmueble = getTipoInmueble();
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
            header("Location: ./seguimiento");
        }
        else {
            $keep = true;
        }
    }
    else if (isset($_POST['correo']) && isset($_POST['id_registro'])) {
        // Usuario agrega registro anterior a la tabla MAYO
        $correo = mb_strtolower(trim($_POST['correo']));
        $id_reg = $_POST['id_registro'];
        $tmp = registroEnMayo($correo, $id_reg);
        if ($tmp){
            if (($tmp["id_estado"] == "2" || $tmp["id_estado"] == 2) && $tmp["tipo_simulacro"] != "E"){
                cambiaElTipoSimulacro($correo, $id_reg, 'E');
            }
            else {
                $error_msg = "Ya se agregó el inmueble ".$id_reg;
            }
        }
        else {
            if (agregarRegistroAnterior($correo, $id_reg)){
                $ultimo_id = consultaUltimoIdInmueble($correo);
                cambialeElID($correo, $ultimo_id, $id_reg);
            } else {
                $error_msg = "No se pudo registrar el inmueble ".$id_reg;
            }
        }
        $anteriores = getOldRegist($correo);
    }
    else if (isset($_POST['correo'])) {
        // Usuario ingresa solamente correo para ver si ya tiene registros
        $correo = mb_strtolower(trim($_POST['correo']));
        $anteriores = getOldRegist($correo);
    }
    function startsWith ($string, $startString) { 
        $len = strlen($startString); 
        return (substr($string, 0, $len) === $startString); 
    }
    function hipoEsValida($hipo) {
        global $possible_hipotesis;
        foreach ($possible_hipotesis as $key => $value) {
            if (in_array($hipo, $value)) return true;
        }
        return false;
    }
    function cambialeElID($correo, $old_id, $new_id) {
        require_once("db_fns.php");
        require_once("db_global.php");
    
        $conn = dbConnect(user, pass, server);
    
        $paramsArray = Array(
            ":correo" => $correo,
            ":old_id" => $old_id,
            ":new_id" => $new_id
        );
    
        $queryStr = "UPDATE MAYO2020 SET ID_INMUEBLE = :new_id ".
          "WHERE LOWER(CORREO) = LOWER(:correo) AND ID_INMUEBLE = :old_id ";
    
        $query = oci_parse($conn, $queryStr);
    
        foreach ($paramsArray as $key => $value) {
            oci_bind_by_name($query, $key, $paramsArray[$key]);
        }
    
        if (oci_execute($query)){
            dbClose($conn, $query);
            return true;
        }
        dbClose($conn, $query);
        return false;
    }
    function cambiaElTipoSimulacro($correo, $id_inm, $tipo) {
        require_once("db_fns.php");
        require_once("db_global.php");
    
        $conn = dbConnect(user, pass, server);
    
        $paramsArray = Array(
            ":correo" => $correo,
            ":id" => $id_inm,
            ":tipo" => $tipo
        );
    
        $queryStr = "UPDATE MAYO2020 SET TIPO_SIMULACRO = :tipo ".
          "WHERE LOWER(CORREO) = LOWER(:correo) AND ID_INMUEBLE = :id ";
    
        $query = oci_parse($conn, $queryStr);
    
        foreach ($paramsArray as $key => $value) {
            oci_bind_by_name($query, $key, $paramsArray[$key]);
        }
    
        if (oci_execute($query)){
            dbClose($conn, $query);
            return true;
        }
        dbClose($conn, $query);
        return false;
    }
    function sendForm() {
        require_once('sendMail.php');
        global $error_msg, $max_discapacidad, $hora, $possible_participantes, $possible_niveles, $possible_hipotesis;
        global $possible_institucion, $possible_propiedad, $possible_pob_fija, $possible_pob_flotante, $tipo_ejercicio;
        $niveles = $_POST['niveles'];
        $participantes = $_POST['participantes'];
        $discapacidad = intval($_POST['discapacidad']);
        $lat = floatval($_POST['Latitude']);
        $lon = floatval($_POST['Longitude']);
        $estado = trim($_POST['State']);
        $lugar = substr(join(", ", [$_POST['Street'], $_POST['Neighborhood'], $_POST['Postal'] ]), 0, 1024);
        if ( $lugar == ", , "){
            $lugar = "N/A";
        }
        else if (startsWith($lugar, ", , ")) {
            $lugar = "N/A, CP " . substr($lugar, 4);
        }
        else if (startsWith($lugar, ", ")) {
            $lugar = "N/A, " . substr($lugar, 2);
        }
        $hipo = substr(trim($_POST['hipotesis']), 0, 1024);
        $depen = substr(trim($_POST['dependencias']), 0, 500);
        $inst = substr(trim($_POST['institucion']), 0, 1);
        $prop = substr(trim($_POST['propiedad']), 0, 1);
        $flota = substr(trim($_POST['pob_flotante']), 0, 20);
        $respon = substr(trim($_POST['responsable']), 0, 500);
        $correo = substr(trim(mb_strtolower($_POST['correo'])), 0, 500);
        if (empty($_POST['tipoInmueble'])){
            $error_msg = 'Selecciona un tipo de inmueble';
            return false;
        }
        $tipo = substr(join(', ', $_POST["tipoInmueble"]), 0, 500);
        if ($_POST["otro"] == 'true'){
            $otrotipo = substr(trim($_POST["otroTipoInmueble"]), 0, 50);
            if (strlen($otrotipo) == 0) {
                $error_msg = 'Te falta completar el campo de Otro Tipo de Inmueble';
                return false;
            }
            $tipo .= ": ".$otrotipo;
        }
        if (strlen($tipo) == 0) {
            $error_msg = 'Te falta completar el campo de Tipo de Inmueble';
            return false;
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $error_msg = 'El correo electrónico es inválido. Verifíca que esté bien escrito';
            return false;
        }
        else if (!in_array($niveles, $possible_niveles)) {
            $error_msg = 'El valor de niveles del inmueble es inválido';
            return false;
        }
        else if (!hipoEsValida($hipo)) {
            $error_msg = 'El valor de hipótesis es inválido';
            return false;
        }
        else if (!in_array($participantes, $possible_participantes)) {
            $error_msg = 'El valor de participantes es inválido';
            return false;
        }
        else if (!array_key_exists($inst, $possible_institucion)) {
            $error_msg = 'El valor de tipo de institución es inválido';
            return false;
        }
        else if (!array_key_exists($prop, $possible_propiedad)) {
            $error_msg = 'El valor de tipo de propiedad es inválido';
            return false;
        }
        else if (!in_array($flota, $possible_pob_flotante)) {
            $error_msg = 'El valor de población flotante es inválido';
            return false;
        }
        else if ($discapacidad < 0 || $discapacidad > $max_discapacidad) {
            $error_msg = 'El valor de personas con discapacidad es inválido';
            return false;
        }
        else if ($lat == 0) {
            $error_msg = 'El valor de latitud es inválido. Selecciona una ubicación válida';
            return false;
        }
        else if ($lon == 0) {
            $error_msg = 'El valor de longitud es inválido. Selecciona una ubicación válida';
            return false;
        }
        else if (!isStateValid($estado)) {
            $error_msg = 'El estado es inválido. Selecciona una ubicación válida';
            return false;
        }
        else if ($estado == "2" || $estado == 2) {
            if (!isset($_POST["ejercicio"])) {
                
                $error_msg = 'Selecciona ejercicio al que deseas participar';
                return false;
            }
            else if (isset($_POST["ejercicio"]) && !array_key_exists($_POST["ejercicio"], $tipo_ejercicio)) {
               
                $error_msg = 'Selecciona ejercicio al que deseas participar';
                return false;
            }
        }
        else if (!isCountyValid($_POST['County'], $estado)) {
            $error_msg = 'El municipio es inválido. Selecciona una ubicación válida';
            return false;
        }
        $tmp = registrarInmueble2(array("estado"=>$estado, "municipio"=>$_POST['County'], "lugar"=>$lugar,
                                        "responsable"=>$respon, "tipo"=>$tipo, "niveles"=>$niveles,
                                        "discapacidad"=>$discapacidad, "hipotesis"=>$hipo,
                                        "tipo_simulacro"=>$_POST["ejercicio"],
                                        "dependencias"=>$depen,"institucion"=>$inst,"propiedad"=>$prop,"pob_flotante"=>$flota,
                                        "participantes"=>$participantes, "hora"=>$hora, "correo"=>$correo,
                                        "lat"=>$lat, "lon"=>$lon));
        if ($tmp) {
            $id_temp = consultaUltimoIdInmueble($correo);
            if ($id_temp) {
                enviarCorreoConfirmacion($correo, $id_temp, $lugar);
            }
            else {
                error_log('No se envió correo a '.$correo." porque no se consiguió el ID registrado");
            }
        }
        else {
            $error_msg = 'Hubo un error al subir tus datos, por favor verifícalos';
        }
        return $tmp;
        // return true;
    }
    function getOldRegist($correo) {
        // Obtiene todos los registros de $correo en INMUEBLE2
        require_once("db_fns.php");
        require_once("db_global.php");

        $conn = dbConnect(user, pass, server);

        $paramsArray = Array(
            ":correo" => trim($correo) 
        );

        $queryStr = "SELECT i.*, TO_CHAR(fecha, 'dd-MON-yy', 'NLS_DATE_LANGUAGE = spanish') as fecha, INITCAP(e.comun) estado, i.estado id_estado FROM ESTADO e, INMUEBLE2 i ".
            "WHERE LOWER(i.CORREO) = LOWER(:correo) AND e.id_estado = i.estado ORDER BY i.id_inmueble";

        $query = oci_parse($conn, $queryStr);

        foreach ($paramsArray as $key => $value) {
            oci_bind_by_name($query, $key, $paramsArray[$key]);
        }

        oci_execute($query);
        $todos = Array();
        $ar = Array();

        while ( ($row = oci_fetch_assoc($query)) != false) {
            $ar["id_inmueble"] = $row["ID_INMUEBLE"];
            $ar["estado"] = $row["ESTADO"];
            $ar["id_estado"] = $row["ID_ESTADO"];
            $ar["municipio"] = $row["MUNICIPIO"];
            $ar["lugar"] = $row["LUGAR"];
            $ar["dependencias"] = $row["DEPENDENCIAS"];
            $ar["responsable"] = $row["RESPONSABLE"];
            $ar["participantes"] = $row["PARTICIPANTES"];
            $ar["discapacidad"] = $row["DISCAPACIDAD"];
            $ar["tipo"] = $row["TIPOINMUEBLE"];
            $ar["hora"] = $row["HORA"];
            $ar["lat"] = $row["LAT"];
            $ar["lon"] = $row["LON"];
            $ar["correo"] = $correo;
            $ar["fecha"] = $row["FECHA"];

            array_push($todos, $ar);
        }

        dbClose($conn, $query);
        return $todos;
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
    function consultaUltimoIdInmueble($correo) {
        require_once("db_fns.php");
        require_once("db_global.php");
    
        $conn = dbConnect(user, pass, server);
    
        $paramsArray = Array(
            ":correo" => $correo
        );
    
        $queryStr = "SELECT ID_INMUEBLE FROM MAYO2020 ".
          "WHERE LOWER(CORREO) = LOWER(:correo) ".
          "ORDER BY ID_INMUEBLE DESC";
    
        $query = oci_parse($conn, $queryStr);
    
        foreach ($paramsArray as $key => $value) {
            oci_bind_by_name($query, $key, $paramsArray[$key]);
        }
    
        oci_execute($query);
        $id = false;
    
        while ( ($row = oci_fetch_assoc($query)) != false) {
            $id = $row["ID_INMUEBLE"];
            break;
        }
    
        dbClose($conn, $query);
        return $id;
    }
    function registroEnEnero($correo, $id_inm){
        // Verificar si existe registro en INMUEBLE2
        require_once("db_fns.php");
        require_once("db_global.php");

        $conn = dbConnect(user, pass, server);

        $paramsArray = Array(
            ":correo" => trim($correo),
            ":id" => $id_inm
        );

        $queryStr = "SELECT i.*, TO_CHAR(fecha, 'dd-MON-yy', 'NLS_DATE_LANGUAGE = spanish') as fecha, INITCAP(e.comun) estado, i.estado id_estado FROM ESTADO e, INMUEBLE2 i ".
            "WHERE LOWER(i.CORREO) = LOWER(:correo) AND i.id_inmueble = :id AND e.id_estado = i.estado ORDER BY i.id_inmueble";

        $query = oci_parse($conn, $queryStr);

        foreach ($paramsArray as $key => $value) {
            oci_bind_by_name($query, $key, $paramsArray[$key]);
        }

        oci_execute($query);
        $ar = Array();

        while ( ($row = oci_fetch_assoc($query)) != false) {
            $ar = $row;
            break;
        }

        dbClose($conn, $query);
        return $ar;
    }
    function registroEnMayo($correo, $id_inm){
        // Verificar si existe registro en MAYO2020
        require_once("db_fns.php");
        require_once("db_global.php");

        $conn = dbConnect(user, pass, server);

        $paramsArray = Array(
            ":correo" => trim($correo),
            ":id" => $id_inm
        );

        $queryStr = "SELECT i.*, TO_CHAR(fecha, 'dd-MON-yy', 'NLS_DATE_LANGUAGE = spanish') as fecha, INITCAP(e.comun) estado, i.estado id_estado FROM ESTADO e, MAYO2020 i ".
            "WHERE LOWER(i.CORREO) = LOWER(:correo) AND i.ID_INMUEBLE = :id AND e.id_estado = i.estado";

        $query = oci_parse($conn, $queryStr);

        foreach ($paramsArray as $key => $value) {
            oci_bind_by_name($query, $key, $paramsArray[$key]);
        }

        $ar = Array();
        if (!oci_execute($query)){
            dbClose($conn, $query);
            return $ar;
        }
        
        while ( ($row = oci_fetch_assoc($query)) != false) {
            $ar["id_inmueble"] = $row["ID_INMUEBLE"];
            $ar["estado"] = $row["ESTADO"];
            $ar["id_estado"] = $row["ID_ESTADO"];
            $ar["municipio"] = $row["MUNICIPIO"];
            $ar["lugar"] = $row["LUGAR"];
            $ar["dependencias"] = $row["DEPENDENCIAS"];
            $ar["responsable"] = $row["RESPONSABLE"];
            $ar["participantes"] = $row["PARTICIPANTES"];
            $ar["discapacidad"] = $row["DISCAPACIDAD"];
            $ar["tipo"] = $row["TIPOINMUEBLE"];
            $ar["hora"] = $row["HORA"];
            $ar["lat"] = $row["LAT"];
            $ar["lon"] = $row["LON"];
            $ar["correo"] = $correo;
            $ar["fecha"] = $row["FECHA"];
            $ar["tipo_simulacro"] = $row["TIPO_SIMULACRO"];
        }

        dbClose($conn, $query);
        return $ar;
    }
    function agregarRegistroAnterior($correo, $id_inm){
        // Leer datos de INMUEBLE2 y pasarlo a MAYO2020

        $anterior = registroEnEnero($correo, $id_inm);
        if (empty($anterior)){
            return false;
        }
        require_once("db_fns.php");
        require_once("db_global.php");

        $conn = dbConnect(user, pass, server);

        $paramsArray = Array(
            ":id" => $anterior["ID_INMUEBLE"],
            ":estado" => $anterior["ID_ESTADO"],
            ":municipio" => $anterior["MUNICIPIO"],
            ":lugar" => $anterior["LUGAR"],
            ":hipotesis" => $anterior["HIPOTESIS"],
            ":dependencias" => $anterior["DEPENDENCIAS"],
            ":participantes" => $anterior["PARTICIPANTES"],
            ":hora" => $anterior["HORA"],
            ":correo" => trim($anterior["CORREO"]),
            ":lat" => $anterior["LAT"],
            ":lon" => $anterior["LON"],
            ":responsable" => $anterior["RESPONSABLE"],
            ":niveles" => $anterior["NIVELES"],
            ":discapacidad" => $anterior["DISCAPACIDAD"],
            ":tipo" => $anterior["TIPOINMUEBLE"],
            ":institucion" => $anterior["INSTITUCION"],
            ":propiedad" => $anterior["PROPIEDAD"],
            ":pob_flotante" => $anterior["POB_FLOT"],
        );
        if ($anterior["ID_ESTADO"] == 2 || $anterior["ID_ESTADO"] == "2"){
            $queryStr = "INSERT INTO MAYO2020 (ID_INMUEBLE, ESTADO, MUNICIPIO, LUGAR, HIPOTESIS, DEPENDENCIAS, PARTICIPANTES, HORA, CORREO, LAT, LON, RESPONSABLE, TIPOINMUEBLE, NIVELES, DISCAPACIDAD, INSTITUCION, PROPIEDAD, POB_FLOT, TIPO_SIMULACRO) ".
            "VALUES (:id, :estado, :municipio, :lugar, :hipotesis, :dependencias, :participantes, :hora, :correo, :lat, :lon, :responsable, :tipo, :niveles, :discapacidad, :institucion, :propiedad, :pob_flotante, 'E')";
        }
        else {
            $queryStr = "INSERT INTO MAYO2020 (ID_INMUEBLE, ESTADO, MUNICIPIO, LUGAR, HIPOTESIS, DEPENDENCIAS, PARTICIPANTES, HORA, CORREO, LAT, LON, RESPONSABLE, TIPOINMUEBLE, NIVELES, DISCAPACIDAD, INSTITUCION, PROPIEDAD, POB_FLOT) ".
            "VALUES (:id, :estado, :municipio, :lugar, :hipotesis, :dependencias, :participantes, :hora, :correo, :lat, :lon, :responsable, :tipo, :niveles, :discapacidad, :institucion, :propiedad, :pob_flotante)";
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
    <title>Macrosimulacro 2020</title>
    
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <!-- Materialize -->
    <!-- Compiled and minified CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <!-- Compiled and minified JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <!-- Iconos -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <!-- Mapa -->
    <link rel="stylesheet" href="https://js.arcgis.com/4.11/esri/css/main.css">
    <script src="https://js.arcgis.com/4.11/"></script>
    
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
            <img class="cnpc" src="http://www.atlasnacionalderiesgos.gob.mx/Imagenes/Logos/SSyPC_CNPC_h.png" alt="gob">
        </div>
        <h1 class="flow-text center">Macrosimulacro 2020</h1>
        <!-- <p class="blue-text lighten-3 center">Mayo 2020</p> -->
        <hr>

        <div class="row">
            <a class="btn-small" href="http://www.preparados.gob.mx/macrosimulacro">Página inicial</a>
        </div>

        <div class="fixed-action-btn" style="position: fixed; bottom: 5em;">
            <a target="_blank" href="http://www.preparados.gob.mx/blog/dudas-macrosimulacro" class="btn-floating btn blue"><i class="material-icons">help</i></a>
        </div>

        <?php if (isset($error_msg)) { ?>
            <div id="div-error" class="valign-wrapper center" style="background-color: lightcoral;">
                <i class="material-icons alerted" style="margin-left: 1em;">error_outline</i>
                <p style="width: 100%;"><?=$error_msg?></p>
                <button id="btn-error-close" class="btn-small" type="button" style="margin-right: 1em;"><i class="material-icons">close</i></button>
            </div>
        <?php } ?>

        <?php if (empty($_POST)) { ?>
            <form method="POST">
                <p>Por favor ingresa tu correo para continuar</p>
                <div class="row">
                    <div class="input-field">
                        <input id="correo-ingreso" class="validate" type="email" name="correo" required placeholder="Ingresa tu correo">
                        <label for="correo-ingreso"></label>
                        <span class="helper-text" data-error="Completa este campo" data-success="Correcto"></span>
                    </div>
                </div>
                <button class="btn" type="submit">Continuar</button>
            </form>
        <?php } else { ?>

            <?php if(isset($anteriores) && !empty($anteriores)){ ?>
                <div class="row">
                    <a class="btn-small" href="registro.php<?php if (http_build_query($_GET)) echo "?".http_build_query($_GET);?>">Atrás</a>
                </div>
                <div id="tabla-registros" class="row">
                    <?php $mes = isset($_GET["estado"]) && $_GET["estado"] == "Baja California" ? "Abril y Mayo" : "Mayo"; ?>
                    <p class="center">A continuación se muestran tus registros anteriores de Enero. Correo: <strong><?=$correo?></strong></p>
                    <p>Haz clic en el botón de <strong>Añadir</strong> correspondiente si deseas registrar el mismo inmueble para el ejercicio de <?=$mes?> 2020</p>
                    <table class="responsive-table hoverable highlight">
                        <thead>
                            <tr style="background-color: #800040; color: white;">
                                <th># de registro</th>
                                <th>Fecha de registro</th>
                                <th>Estado</th>
                                <th>Municipio</th>
                                <th>Dirección</th>
                                <th>Responsable</th>
                                <th>Registrar de nuevo</th>
                            </tr>
                        </thead>
                        <tbody>
                    <?php foreach ($anteriores as $key => $v) { ?>
                            <tr>
                                <td><?=$v["id_inmueble"]?></td>
                                <td><?=$v["fecha"]?></td>
                                <td><?=$v["estado"]?></td>
                                <td><?=$v["municipio"]?></td>
                                <td><?=$v["lugar"]?></td>
                                <td><?=$v["responsable"]?></td>
                                <?php $ahora = registroEnMayo($correo, $v["id_inmueble"]);
                                if (empty($ahora)){ ?>
                                    <td>
                                    <form method="post">
                                        
                                        <input type="hidden" name="correo" value="<?=$correo?>">
                                        <input type="hidden" name="id_registro" value="<?=$v['id_inmueble']?>">
                                        <?php if ($v["id_estado"] == 2 || $v["id_estado"] == "2") { ?>
                                            <button class="btn-small tooltipped" data-position="left" data-tooltip="Añadir para Abril y Mayo" type="submit">Añadir</button>
                                            <p>Para Abril y Mayo</p>
                                        <?php } else {  ?>
                                            <button class="btn-small tooltipped" data-position="left" data-tooltip="Añadir para Mayo" type="submit">Añadir</button>
                                            <p>Para Mayo</p>
                                        <?php } ?>
                                    </form>
                                    </td>
                                <?php } else { ?>
                                    
                                    <?php if (($v["id_estado"] == 2 || $v["id_estado"] == "2") && $ahora["tipo_simulacro"] != "E" ) { ?>
                                        <td>
                                            
                                        <form method="post">
                                            <input type="hidden" name="correo" value="<?=$correo?>">
                                            <input type="hidden" name="id_registro" value="<?=$v['id_inmueble']?>">
                                            <button class="btn-small tooltipped" data-position="left" data-tooltip="Añadir para Abril" type="submit">Añadir</button>
                                            <p>Para Abril</p>
                                        </form>
                                        </td>
                                    <?php } else {  ?>
                                        <td class="teal lighten-2">Ya está registrado </td>
                                    <?php } ?>
                                <?php } ?>
                                
                            </tr>
                    <?php } ?>
        
                        </tbody>
                    </table>
                    <p>Si deseas registrar un nuevo inmueble, haz clic en el siguiente botón</p>
                </div>
                <div class="row center">
                    <button id="btn-add-register" type="button" class="btn-floating tooltipped" data-position="right" data-tooltip="Registrar otro inmueble"><i class="material-icons">add</i></button>
                    <button id="btn-table" style="display: none;" type="button" class="btn-floating tooltipped" data-position="right" data-tooltip="Mostrar tabla"><i class="material-icons">swap_vert</i></button>
                </div>
                <div class="row" id="form-registro" style="display: none;">
                    <form method="post" id="submit-form">
                        <p class="center">Registro de Inmueble para el Macrosimulacro</p>
                        <div id="primera-parte" class="row">
                            <div class="row">
                                <h6 class="center">Ingresa los siguientes campos</h6>
                            </div>
                            <?php if ($_GET["estado"] == "Baja California") { ?>
                                <div class="row">
                                    <p>Selecciona el ejercicio para el cual deseas participar</p>
                                    <?php foreach($tipo_ejercicio as $val => $txt) { ?>
                                        <label for="radio-e-<?=$val?>">
                                            <input id="radio-e-<?=$val?>" class="with-gap" name="ejercicio" type="radio" value="<?=$val?>" required <?php if ($keep && $val == $_POST['ejercicio'] || (isset($_GET["ejercicio"]) && $val == 'E' && $_GET["ejercicio"]=="3ABRIL2020") ) echo 'checked=""'; ?>>
                                            <span><?=$txt?></span>
                                        </label>
                                    <?php } ?>
                                    <br>
                                </div>
                            <?php } ?>
                            <div class="row">
                                <div class="input-field">
                                    <i class="material-icons prefix">account_circle</i>
                                    <input required placeholder="Responsable del inmueble" name="responsable" id="responsable" type="text" class="validate" data-length="200" maxlength="200" <?php if ($keep) echo 'value="'.$_POST['responsable'].'"'; ?>>
                                    <label for="responsable">Nombre del Responsable o Encargado</label>
                                    <span class="helper-text" data-error="Completa este campo" data-success="Correcto"></span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="input-field">
                                    <select name="tipoInmueble[]" id="tipoInmueble" class="validate" multiple>
                                        <option value="" disabled>Elije uno o varios</option>
                                        <?php foreach($tipoInmueble as $grupo => $tipos) { ?>
                                            <optgroup label="<?=$grupo?>">
                                            <?php foreach($tipos as $tipo) {
                                                if ($keep && in_array($tipo, $_POST['tipoInmueble'])) { ?>
                                                    <option value="<?=$tipo?>" selected><?=$tipo?></option>
                                                <?php } else { ?>
                                                    <option value="<?=$tipo?>"><?=$tipo?></option>
                                                <?php } ?>
                                            <?php } ?>
                                            </optgroup>
                                        <?php } ?>
                                    </select>
                                    <label for="tipoInmueble">Tipo de inmueble</label>
                                    <span class="helper-text" data-error="Completa este campo" data-success="Correcto"></span>
                                </div>
                                <input required type="hidden" name="otro" id="checkbox-otro" value="false">
                                <div class="input-field" style="display: none;">
                                    <input type="text" name="otroTipoInmueble" id="otroTipoInmueble" maxlength="50" class="validate" data-length="50" <?php if ($keep) echo 'value="'.$_POST['otroTipoInmueble'].'"'; ?>>
                                    <label for="otroTipoInmueble">Ingresa el tipo de Inmueble que no aparece en la selección</label>
                                    <span class="helper-text" data-error="Completa este campo" data-success="Correcto"></span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="input-field">
                                    <select name="niveles" id="niveles" class="validate">
                                        <option value="" disabled selected>Elije el rango de niveles del inmueble</option>
                                        <?php foreach($possible_niveles as $nivel) { ?>
                                            <option value="<?=$nivel?>" <?php if ($keep && $nivel == $_POST['niveles']) echo 'selected=""'; ?>><?=$nivel?></option>
                                        <?php } ?>
                                    </select>
                                    <label for="niveles">No. de niveles del inmueble</label>
                                    <span class="helper-text" data-error="Completa este campo" data-success="Correcto"></span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="input-field">
                                    <select name="hipotesis" id="hipotesis">
                                        <option value="" disabled selected>Elije una hipótesis</option>
                                        <?php foreach($possible_hipotesis as $grupo => $tipos) { ?>
                                            <optgroup label="<?=$grupo?>">
                                            <?php foreach($tipos as $hipotesis) { ?>
                                                <option value="<?=$hipotesis?>" <?php if ($keep && $hipotesis == $_POST['hipotesis']) echo 'selected=""'; ?>><?=$hipotesis?></option>    
                                            <?php } ?>
                                        <?php } ?>
                                    </select>
                                    <label for="hipotesis">Hipótesis</label>
                                    <span class="helper-text" data-error="Completa este campo" data-success="Correcto"></span>
                                </div>
                            </div>
            
                            <div class="row" id="div-radio">
                                <p>El tipo de institución del inmueble es:</p>
                                <?php foreach($possible_institucion as $val => $txt) { ?>
                                    <label for="radio-<?=$val?>">
                                        <input id="radio-<?=$val?>" class="with-gap" name="institucion" type="radio" value="<?=$val?>" required <?php if ($keep && $val == $_POST['institucion']) echo 'checked=""'; ?>>
                                        <span><?=$txt?></span>
                                    </label>
                                <?php } ?>
                            </div>
            
                            <div class="row" id="div-radio2">
                                <p>La propiedad del inmueble es:</p>
                                <?php foreach($possible_propiedad as $val => $txt) { ?>
                                    <label for="p-radio-<?=$val?>">
                                        <input id="p-radio-<?=$val?>" class="with-gap" name="propiedad" type="radio" value="<?=$val?>" required <?php if ($keep && $val == $_POST['propiedad']) echo 'checked=""'; ?>>
                                        <?php if($val == 'C') { ?>
                                            <span class="with-gap tooltipped" data-position="bottom" data-tooltip="Contrato por el cual se da o recibe prestada"><?=$txt?></span>
                                        <?php } else { ?>
                                            <span><?=$txt?></span>
                                        <?php } ?>
                                    </label>
                                <?php } ?>
                            </div>
            
                            <div class="row">
                                <div class="input-field">
                                    <input required type="text" name="dependencias" id="dependencias" data-length="200" maxlength="200" class="validate" <?php if ($keep) echo 'value="'.$_POST['dependencias'].'"'; ?>>
                                    <label for="dependencias">Nombre de Institución/Dependencia/Empresa</label>
                                    <span class="helper-text" data-error="Completa este campo" data-success="Correcto"></span>
                                </div>
                            </div>
            
                            <div class="row">
                                <div class="input-field">
                                    <select name="participantes" id="participantes" class="validate" required>
                                        <option value="" disabled selected>Elije un rango de personas</option>
                                        <?php foreach($possible_participantes as $part) { ?>
                                            <option value="<?=$part?>" <?php if ($keep && $part == $_POST['participantes']) echo 'selected=""'; ?>><?=$part?></option>
                                        <?php } ?>
                                    </select>
                                    <label for="participantes">¿Cuántas personas ocupan el inmueble? (Población fija)</label>
                                    <span class="helper-text" data-error="Completa este campo" data-success="Correcto"></span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="input-field">
                                    <select name="pob_flotante" id="pob_flotante" class="validate" required>
                                        <option value="" disabled selected>Elije un rango de pob flotante</option>
                                        <?php foreach($possible_pob_flotante as $pob) { ?>
                                            <option value="<?=$pob?>" <?php if ($keep && $pob == $_POST['pob_flotante']) echo 'selected=""'; ?>><?=$pob?></option>
                                        <?php } ?>
                                    </select>
                                    <label for="pob_flotante">¿Cuántas personas en promedio están de maneral temporal en el inmueble? (Población flotante)</label>
                                    <span class="helper-text" data-error="Completa este campo" data-success="Correcto"></span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="input-field">
                                    <input required type="number" min="0" max="<?=$max_discapacidad?>" name="discapacidad" id="discapacidad"  class="validate" <?php if ($keep) echo 'value="'.$_POST['discapacidad'].'"'; ?>>
                                    <label for="discapacidad">¿Existen personas con discapacidad? ¿Cuántas?</label>
                                    <span class="helper-text" data-error="Número inválido" data-success="Correcto"></span>
                                </div>
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
                            <div class="row center">
                                <button class="btn-large disabled" type="button" id="btn-continuar"><i class="material-icons right">arrow_forward</i>
                                    Continuar
                                </button>
                            </div>
                        </div>
            
                        <div id="segunda-parte" class="row hide">
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
                            <div class="progress" id="colonia-loading" style="display: none;">
                                <div class="indeterminate"></div>
                            </div>
                            <div class="row" id="colonias-div" style="display: none;">
                                <div class="input-field">
                                    <select name="colonia" id="colonia-select" class="validate">
                                        <option value="" disabled>Elije la colonia</option>
                                    </select>
                                    <label for="estado-select">Colonia</label>
                                    <span class="helper-text" data-error="Completa este campo" data-success="Correcto"></span>
                                </div>
                                <p>¿No aparece tu colonia? <button id="btn-colonia-missing" type="button" class="btn-small">Clic aquí</button></p>
                            </div>
                            <!-- MAPA -->
                            <div class="row" id="mapa-div" style="display: none;">
                                <div class="col s12 m8">
                                    <p class="center">Haz clic en el mapa sobre tu inmueble, dentro del área coloreada</p>
                                    <p class="red-text lighten-1"><i class="material-icons">warning</i>No te preocupes si la dirección del inmueble no es exactamente a la que aparece en la siguiente sección, solo asegúrate de que el punto naranja coincida con tu edificio. Más adelante podrás cambiar este dato, más información <a target="_blank" href="http://www.preparados.gob.mx/dudas-macrosimulacro">aquí.</a></p>
                                </div>
            
                                <div class="col s12 m4">
                                    <div class="row center" style="font-size: 0.8rem;">
                                        <p>La información se completará una vez hagas clic en el mapa sobre tu inmueble.</p>
                                        <strong>Estos datos no los puedes modificar</strong>
                                    </div>
            
                                    <div class="row" id="mensaje" style="font-size: 0.8rem;">
                                        <label>Dirección</label>
                                        <input type="text" id="Street" name="Street" readonly <?php if ($keep) echo 'value="'.$_POST['Street'].'"'; ?>>
            
                                        <label>Colonia</label>
                                        <input type="text" id="Neighborhood" name="Neighborhood" readonly <?php if ($keep) echo 'value="'.$_POST['Neighborhood'].'"'; ?>>
            
                                        <label>Código postal</label>
                                        <input type="text" id="Postal" name="Postal" readonly <?php if ($keep) echo 'value="'.$_POST['Postal'].'"'; ?>>
            
                                        <label>Municipio</label>
                                        <input type="text" id="County" name="County" readonly <?php if ($keep) echo 'value="'.$_POST['County'].'"'; ?>>
            
                                        <label>Estado</label>
                                        <input type="text" id="StateLong" name="StateLong" readonly <?php if ($keep) echo 'value="'.$_POST['StateLong'].'"'; ?>>
                                        <input type="hidden" id="State" name="State" readonly <?php if ($keep) echo 'value="'.$_POST['State'].'"'; ?>>
            
                                        <label>Latitud</label>
                                        <input type="text" id="Latitude" name="Latitude" readonly <?php if ($keep) echo 'value="'.$_POST['Latitude'].'"'; ?>>
            
                                        <label>Longitud</label>
                                        <input type="text" id="Longitude" name="Longitude" readonly <?php if ($keep) echo 'value="'.$_POST['Longitude'].'"'; ?>>
                                    </div>
                                </div>
                            </div>
                            <div class="row center">
                                <button class="btn-large" type="button" id="btn-atras"><i class="material-icons left">arrow_back</i>
                                    Atrás
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
                                <p>Haz selecciona una ubicación equivocada</p>
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
            <?php } else { ?>
                <div class="row">
                    <a class="btn-small" href="registro.php<?php if (http_build_query($_GET)) echo "?".http_build_query($_GET);?>">Atrás</a>
                </div>
                <?php if(isset($anteriores) && empty($anteriores)) { ?>
                    <p>No tienes inmuebles registrados con tu correo del <strong style="color:red;">ejercicio pasado</strong>, pero a continuación puedes registrar uno nuevo. Recuerda que puedes ver todos tus registros realizados <a class="btn-small" href="./seguimiento/">aquí</a>.</p>
                <?php } ?>
            <form method="post" id="submit-form">
                <p class="center">Registro de Inmueble para el Macrosimulacro</p>
                <div id="primera-parte" class="row">
                    <div class="row">
                        <h6 class="center">Ingresa los siguientes campos</h6>
                    </div>
                    <?php if ($_GET["estado"] == "Baja California") { ?>
                        <div class="row">
                            <p>Selecciona el ejercicio para el cual deseas participar</p>
                            <?php foreach($tipo_ejercicio as $val => $txt) { ?>
                                <label for="radio-e-<?=$val?>">
                                    <input id="radio-e-<?=$val?>" class="with-gap" name="ejercicio" type="radio" value="<?=$val?>" required <?php if ($keep && $val == $_POST['ejercicio'] || (isset($_GET["ejercicio"]) && $val == 'E' && $_GET["ejercicio"]=="3ABRIL2020")) echo 'checked=""'; ?>>
                                    <span><?=$txt?></span>
                                </label>
                            <?php } ?>
                            <br>
                        </div>
                    <?php } ?>
                    <div class="row">
                        <div class="input-field">
                            <i class="material-icons prefix">account_circle</i>
                            <input required placeholder="Responsable del inmueble" name="responsable" id="responsable" type="text" class="validate" data-length="200" maxlength="200" <?php if ($keep) echo 'value="'.$_POST['responsable'].'"'; ?>>
                            <label for="responsable">Nombre del Responsable o Encargado</label>
                            <span class="helper-text" data-error="Completa este campo" data-success="Correcto"></span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="input-field">
                            <select name="tipoInmueble[]" id="tipoInmueble" class="validate" multiple>
                                <option value="" disabled>Elije uno o varios</option>
                                <?php foreach($tipoInmueble as $grupo => $tipos) { ?>
                                    <optgroup label="<?=$grupo?>">
                                    <?php foreach($tipos as $tipo) {
                                        if ($keep && in_array($tipo, $_POST['tipoInmueble'])) { ?>
                                            <option value="<?=$tipo?>" selected><?=$tipo?></option>
                                        <?php } else { ?>
                                            <option value="<?=$tipo?>"><?=$tipo?></option>
                                        <?php } ?>
                                    <?php } ?>
                                    </optgroup>
                                <?php } ?>
                            </select>
                            <label for="tipoInmueble">Tipo de inmueble</label>
                            <span class="helper-text" data-error="Completa este campo" data-success="Correcto"></span>
                        </div>
                        <input required type="hidden" name="otro" id="checkbox-otro" value="false">
                        <div class="input-field" style="display: none;">
                            <input type="text" name="otroTipoInmueble" id="otroTipoInmueble" maxlength="50" class="validate" data-length="50" <?php if ($keep) echo 'value="'.$_POST['otroTipoInmueble'].'"'; ?>>
                            <label for="otroTipoInmueble">Ingresa el tipo de Inmueble que no aparece en la selección</label>
                            <span class="helper-text" data-error="Completa este campo" data-success="Correcto"></span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="input-field">
                            <select name="niveles" id="niveles" class="validate">
                                <option value="" disabled selected>Elije el rango de niveles del inmueble</option>
                                <?php foreach($possible_niveles as $nivel) { ?>
                                    <option value="<?=$nivel?>" <?php if ($keep && $nivel == $_POST['niveles']) echo 'selected=""'; ?>><?=$nivel?></option>
                                <?php } ?>
                            </select>
                            <label for="niveles">No. de niveles del inmueble</label>
                            <span class="helper-text" data-error="Completa este campo" data-success="Correcto"></span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="input-field">
                            <select name="hipotesis" id="hipotesis">
                                <option value="" disabled selected>Elije una hipótesis</option>
                                <?php foreach($possible_hipotesis as $grupo => $tipos) { ?>
                                    <optgroup label="<?=$grupo?>">
                                    <?php foreach($tipos as $hipotesis) { ?>
                                        <option value="<?=$hipotesis?>" <?php if ($keep && $hipotesis == $_POST['hipotesis']) echo 'selected=""'; ?>><?=$hipotesis?></option>    
                                    <?php } ?>
                                <?php } ?>
                            </select>
                            <label for="hipotesis">Hipótesis</label>
                            <span class="helper-text" data-error="Completa este campo" data-success="Correcto"></span>
                        </div>
                    </div>
    
                    <div class="row" id="div-radio">
                        <p>El tipo de institución del inmueble es:</p>
                        <?php foreach($possible_institucion as $val => $txt) { ?>
                            <label for="radio-<?=$val?>">
                                <input id="radio-<?=$val?>" class="with-gap" name="institucion" type="radio" value="<?=$val?>" required <?php if ($keep && $val == $_POST['institucion']) echo 'checked=""'; ?>>
                                <span><?=$txt?></span>
                            </label>
                        <?php } ?>
                    </div>
    
                    <div class="row" id="div-radio2">
                        <p>La propiedad del inmueble es:</p>
                        <?php foreach($possible_propiedad as $val => $txt) { ?>
                            <label for="p-radio-<?=$val?>">
                                <input id="p-radio-<?=$val?>" class="with-gap" name="propiedad" type="radio" value="<?=$val?>" required <?php if ($keep && $val == $_POST['propiedad']) echo 'checked=""'; ?>>
                                <?php if($val == 'C') { ?>
                                    <span class="with-gap tooltipped" data-position="bottom" data-tooltip="Contrato por el cual se da o recibe prestada"><?=$txt?></span>
                                <?php } else { ?>
                                    <span><?=$txt?></span>
                                <?php } ?>
                            </label>
                        <?php } ?>
                    </div>
    
                    <div class="row">
                        <div class="input-field">
                            <input required type="text" name="dependencias" id="dependencias" data-length="200" maxlength="200" class="validate" <?php if ($keep) echo 'value="'.$_POST['dependencias'].'"'; ?>>
                            <label for="dependencias">Nombre de Institución/Dependencia/Empresa</label>
                            <span class="helper-text" data-error="Completa este campo" data-success="Correcto"></span>
                        </div>
                    </div>
    
                    <div class="row">
                        <div class="input-field">
                            <select name="participantes" id="participantes" class="validate" required>
                                <option value="" disabled selected>Elije un rango de personas</option>
                                <?php foreach($possible_participantes as $part) { ?>
                                    <option value="<?=$part?>" <?php if ($keep && $part == $_POST['participantes']) echo 'selected=""'; ?>><?=$part?></option>
                                <?php } ?>
                            </select>
                            <label for="participantes">¿Cuántas personas ocupan el inmueble? (Población fija)</label>
                            <span class="helper-text" data-error="Completa este campo" data-success="Correcto"></span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="input-field">
                            <select name="pob_flotante" id="pob_flotante" class="validate" required>
                                <option value="" disabled selected>Elije un rango de pob flotante</option>
                                <?php foreach($possible_pob_flotante as $pob) { ?>
                                    <option value="<?=$pob?>" <?php if ($keep && $pob == $_POST['pob_flotante']) echo 'selected=""'; ?>><?=$pob?></option>
                                <?php } ?>
                            </select>
                            <label for="pob_flotante">¿Cuántas personas en promedio están de maneral temporal en el inmueble? (Población flotante)</label>
                            <span class="helper-text" data-error="Completa este campo" data-success="Correcto"></span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="input-field">
                            <input required type="number" min="0" max="<?=$max_discapacidad?>" name="discapacidad" id="discapacidad"  class="validate" <?php if ($keep) echo 'value="'.$_POST['discapacidad'].'"'; ?>>
                            <label for="discapacidad">¿Existen personas con discapacidad? ¿Cuántas?</label>
                            <span class="helper-text" data-error="Número inválido" data-success="Correcto"></span>
                        </div>
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
                    <div class="row center">
                        <button class="btn-large disabled" type="button" id="btn-continuar"><i class="material-icons right">arrow_forward</i>
                            Continuar
                        </button>
                    </div>
                </div>
    
                <div id="segunda-parte" class="row hide">
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
                    <div class="progress" id="colonia-loading" style="display: none;">
                        <div class="indeterminate"></div>
                    </div>
                    <div class="row" id="colonias-div" style="display: none;">
                        <div class="input-field">
                            <select name="colonia" id="colonia-select" class="validate">
                                <option value="" disabled>Elije la colonia</option>
                            </select>
                            <label for="estado-select">Colonia</label>
                            <span class="helper-text" data-error="Completa este campo" data-success="Correcto"></span>
                        </div>
                        <p>¿No aparece tu colonia? <button id="btn-colonia-missing" type="button" class="btn-small">Clic aquí</button></p>
                    </div>
                    <!-- MAPA -->
                    <div class="row" id="mapa-div" style="display: none;">
                        <div class="col s12 m8">
                            <p class="center">Haz clic en el mapa sobre tu inmueble, dentro del área coloreada</p>
                            <p class="red-text lighten-1"><i class="material-icons">warning</i>No te preocupes si la dirección del inmueble no es exactamente a la que aparece en la siguiente sección, solo asegúrate de que el punto naranja coincida con tu edificio. Más adelante podrás cambiar este dato, más información <a target="_blank" href="http://www.preparados.gob.mx/dudas-macrosimulacro">aquí.</a></p>
                        </div>
    
                        <div class="col s12 m4">
                            <div class="row center" style="font-size: 0.8rem;">
                                <p>La información se completará una vez hagas clic en el mapa sobre tu inmueble.</p>
                                <strong>Estos datos no los puedes modificar</strong>
                            </div>
    
                            <div class="row" id="mensaje" style="font-size: 0.8rem;">
                                <label>Dirección</label>
                                <input type="text" id="Street" name="Street" readonly <?php if ($keep) echo 'value="'.$_POST['Street'].'"'; ?>>
    
                                <label>Colonia</label>
                                <input type="text" id="Neighborhood" name="Neighborhood" readonly <?php if ($keep) echo 'value="'.$_POST['Neighborhood'].'"'; ?>>
    
                                <label>Código postal</label>
                                <input type="text" id="Postal" name="Postal" readonly <?php if ($keep) echo 'value="'.$_POST['Postal'].'"'; ?>>
    
                                <label>Municipio</label>
                                <input type="text" id="County" name="County" readonly <?php if ($keep) echo 'value="'.$_POST['County'].'"'; ?>>
    
                                <label>Estado</label>
                                <input type="text" id="StateLong" name="StateLong" readonly <?php if ($keep) echo 'value="'.$_POST['StateLong'].'"'; ?>>
                                <input type="hidden" id="State" name="State" readonly <?php if ($keep) echo 'value="'.$_POST['State'].'"'; ?>>
    
                                <label>Latitud</label>
                                <input type="text" id="Latitude" name="Latitude" readonly <?php if ($keep) echo 'value="'.$_POST['Latitude'].'"'; ?>>
    
                                <label>Longitud</label>
                                <input type="text" id="Longitude" name="Longitude" readonly <?php if ($keep) echo 'value="'.$_POST['Longitude'].'"'; ?>>
                            </div>
                        </div>
                    </div>
                    <div class="row center">
                        <button class="btn-large" type="button" id="btn-atras"><i class="material-icons left">arrow_back</i>
                            Atrás
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
                        <p>Haz selecciona una ubicación equivocada</p>
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
            <?php } ?>
        <?php } ?>

    </div>
    
    <script type="text/javascript" src="js/formulario.js"></script>
    <script type="text/javascript" src="js/mapa.js"></script>

</body>
</html>
