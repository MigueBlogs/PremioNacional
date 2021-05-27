<?php
    require_once("db_fns.php");

    if (isset($_POST["id"]) &&
        isset($_POST["nombre"]) &&
        isset($_POST["correo"]) &&
        isset($_POST["seguro_borrar"]))
    {
        if ($_POST["seguro_borrar"] != "sí") { die();}

        $archivo = getArchivo($_POST["id"], $_POST["correo"]);
        $success = borrarRegistro($_POST["id"], $_POST["nombre"], $_POST["correo"]);
        if ($success && $archivo) {
            unlink("/var/www/html/uploads/premionacional2021/".$archivo);
        }
        echo json_encode(array("status"=>$success));
    }

    function getArchivo($id, $correo){
        require_once("db_global.php");

        $conn = dbConnect(user, pass, server);

        $paramsArray = Array(
            ":id"=> $id,
            ":correo"=>$correo
        );

        $queryStr = "SELECT ARCHIVO FROM REGISTRO WHERE ID = :id AND LOWER(CORREO) = LOWER(:correo)";

        $query = oci_parse($conn, $queryStr);

        foreach ($paramsArray as $key => $value) {
            oci_bind_by_name($query, $key, $paramsArray[$key]);
        }

        oci_execute($query);
        $archivo =null;

        while ( ($row = oci_fetch_assoc($query)) != false) {
            $archivo = $row["ARCHIVO"]->load();
            $archivo = end(explode("/", $archivo));
            break;
        }

        dbClose($conn, $query);
        return $archivo;
    }

    function getArchivoByID($id){
        require_once("db_global.php");

        $conn = dbConnect(user, pass, server);

        $paramsArray = Array(
            ":id"=> $id
        );

        $queryStr = "SELECT ARCHIVO FROM REGISTRO WHERE ID = :id ";

        $query = oci_parse($conn, $queryStr);

        foreach ($paramsArray as $key => $value) {
            oci_bind_by_name($query, $key, $paramsArray[$key]);
        }

        oci_execute($query);
        $archivo =null;

        while ( ($row = oci_fetch_assoc($query)) != false) {
            $archivo = $row["ARCHIVO"]->load();
            $archivo = end(explode("/", $archivo));
            break;
        }
        
        if($archivo==''){
            return false;
        }

        dbClose($conn, $query);
        return $archivo;
    }

    function editarArchivo($datos) {

        $oldFile = getArchivoByID($datos[':id']);
        if($oldFile==''){
            return false;
        }
        unlink("/var/www/html/uploads/premionacional2021/".$oldFile);
        require_once("db_global.php");

        $conn = dbConnect(user, pass, server);

        $queryStr = "UPDATE REGISTRO SET ARCHIVO=(:archivo) WHERE ID=(:id)";

        $paramsArray = $datos;

        $query = oci_parse($conn, $queryStr);

        foreach ($paramsArray as $key => $value) {
            oci_bind_by_name($query, $key, $paramsArray[$key]);
        }
        if (oci_execute($query, OCI_NO_AUTO_COMMIT)){
            oci_commit($conn);
            return true;
        }
        else{
            $error_msg = 'Hubo un problema al actualizar archivos. Verifica que el formato o el peso del archivo sea correcto.';
            error_log('Fallo el update de archivos:');
            error_log(oci_error($query)['message']);
            oci_rollback($conn);
            return false;
        }

    }

    function borrarRegistro($id, $nombre, $correo) {
        require_once("db_global.php");

        $conn = dbConnect(user, pass, server);

        $paramsArray = Array(
            ":id"=> $id,
            ":nombre"=>$nombre,
            ":correo"=>$correo
        );

        $queryStr = "DELETE FROM REGISTRO WHERE ID = :id AND LOWER(NOMBRE) = LOWER(:nombre) AND LOWER(CORREO) = LOWER(:correo)";

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

    function getUsuarios() {
        require_once("db_global.php");

        $conn = dbConnect(user, pass, server);

        $paramsArray = Array();

        $queryStr = "select AP_PATERNO as apellido, TO_CHAR(ACCESO, 'dd-MM-yy HH24:MI:SS') as ACCESO from autor";

        $query = oci_parse($conn, $queryStr);

        foreach ($paramsArray as $key => $value) {
            oci_bind_by_name($query, $key, $paramsArray[$key]);
        }

        oci_execute($query);
        $ar = Array();

        while ( ($row = oci_fetch_assoc($query)) != false) {
            $result = Array();
            $result["apellidoUsuario"] = $row["APELLIDO"];
            $result["acceso"] = $row["ACCESO"];

            $ar[] = $result;
        }

        dbClose($conn, $query);
        #echo json_encode($ar);
        return $ar;
    }


    function getCandidaturas(){
        require_once("db_global.php");

        $conn = dbConnect(user, pass, server);

        $paramsArray = Array();

        $queryStr = "SELECT R.ID, R.NOMBRE, R.CORREO, R.TELEFONO, R.ARCHIVO, M.NOMBRE as MUNICIPIO, E.CORTO as ESTADO, R.FECHA, R.TIPO, R.CATEGORIA FROM MUNICIPIO M, REGISTRO R, ESTADO E where M.ID_MUNICIPIO = R.MUNICIPIO and E.ID_ESTADO =R.ESTADO order by R.FECHA desc";
        $query = oci_parse($conn, $queryStr);

        foreach ($paramsArray as $key => $value) {
            oci_bind_by_name($query, $key, $paramsArray[$key]);
        }

        oci_execute($query);
        $ar = Array();

        while ( ($row = oci_fetch_assoc($query)) != false) {
            $result = Array();
            $result["id"] = $row["ID"];
            $result["nombre"] = $row["NOMBRE"];
            $result["correo"] = $row["CORREO"];
            $result["telefono"] = $row["TELEFONO"];
            $result["archivo"] = $row["ARCHIVO"];
            $result["municipio"] = $row["MUNICIPIO"];
            $result["estado"] = $row["ESTADO"];
            $result["fecha"] = $row["FECHA"];
            $result["tipo"] = $row["TIPO"];
            $result["categoria"] = $row["CATEGORIA"];
            $ar[] = $result;
        }

        dbClose($conn, $query);
        return $ar;
    }

    function getEstadosCandidaturas(){
        require_once("db_global.php");

        $conn = dbConnect(user, pass, server);

        $paramsArray = Array();

        $queryStr = "SELECT E.CORTO as ESTADO, COUNT(case when R.categoria = 'Prevención' then 1 end) as TOTAL_PREVENCION,
            COUNT(case when R.categoria != 'Prevención' then 1 end) as TOTAL_AYUDA
        FROM REGISTRO R, ESTADO E 
        where E.ID_ESTADO =R.ESTADO 
        group by R.ESTADO, E.CORTO 
        order by ESTADO asc";

        $query = oci_parse($conn, $queryStr);

        foreach ($paramsArray as $key => $value) {
            oci_bind_by_name($query, $key, $paramsArray[$key]);
        }

        oci_execute($query);
        $ar = Array();

        while ( ($row = oci_fetch_assoc($query)) != false) {
            $result = Array();
            $result["estado"] = $row["ESTADO"];
            $result["total_prevencion"] = $row["TOTAL_PREVENCION"];
            $result["total_ayuda"] = $row["TOTAL_AYUDA"];
            $ar[] = $result;
        }

        dbClose($conn, $query);
        return $ar;
    }


?>