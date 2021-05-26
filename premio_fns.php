<?php
    require_once("db_fns.php");

    require_once("sendMail.php");
    $error_msg = "";
    $success_msg = "";
    $allowTypes = array('zip','rar');
    $max_file_size = 10485760; // 10MB
    $target_dir = "/var/www/html/uploads/premionacional2021/";
    // $target_dir = "D:/xampp/htdocs/uploads/premionacional2021/";

    if(isset($_POST["idEstado"])) {
        echo json_encode(getMunicipios($_POST["idEstado"]));
        
    }

    function registrar($datos) {
        global $error_msg;
        require_once("db_global.php");

        $conn = dbConnect(user, pass, server);

        $queryStr = "INSERT INTO REGISTRO (NOMBRE, CORREO, TELEFONO, ARCHIVO, ESTADO, MUNICIPIO, TIPO, CATEGORIA) 
        VALUES (:nombre,LOWER(:correo),:telefono,:archivo,:estado,:municipio,:tipo,:categoria)";

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
            $error_msg = 'Hubo un problema con tu registro. Verifica que tus datos sean correctos.';
            error_log('Fallo el insertado de datos al premio nacional:');
            error_log(oci_error($query)['message']);
            oci_rollback($conn);
            return false;
        }

    }
    function getUltimoRegistro($correo) {
        require_once("db_global.php");

        $conn = dbConnect(user, pass, server);

        $queryStr = "SELECT ID, NOMBRE, TELEFONO FROM REGISTRO WHERE LOWER(CORREO) = LOWER(:correo) ORDER BY ID DESC";

        $paramsArray = Array(
            ":correo" => trim($correo),
        );

        $query = oci_parse($conn, $queryStr);

        foreach ($paramsArray as $key => $value) {
            oci_bind_by_name($query, $key, $paramsArray[$key]);
        }

        $resultados = Array();

        oci_execute($query);

        while ( ($row = oci_fetch_assoc($query)) != false) {
            $resultados = $row;
            break;
        }
        return $resultados;
    }
    
    function getRegistros() {
        require_once("db_global.php");

        $conn = dbConnect(user, pass, server);

        $queryStr = "SELECT E.NOMBRE ESTADO, M.NOMBRE MUNICIPIO, R.ID ID, R.NOMBRE NOMBRE, R.CORREO CORREO, R.TELEFONO TELEFONO, R.ARCHIVO ARCHIVO, R.FECHA FECHA 
        FROM REGISTRO R, ESTADO E, MUNICIPIO M 
        WHERE R.ESTADO = E.ID_ESTADO AND R.MUNICIPIO = M.ID_MUNICIPIO";

        $query = oci_parse($conn, $queryStr);

        $resultados = Array();

        oci_execute($query);

        while ( ($row = oci_fetch_assoc($query)) != false) {
            $resultados[] = $row;
        }
        return $resultados;
    }

    function getMunicipios($id_estado) {
        require_once("db_global.php");

        $conn = dbConnect(user, pass, server);

        $paramsArray = Array(
            ":estado" => trim($id_estado),
        );

        $queryStr = "SELECT ID_MUNICIPIO, initcap(NOMBRE) NOMBRE from MUNICIPIO where ID_ESTADO = :estado order by NOMBRE asc";

        $query = oci_parse($conn, $queryStr);

        foreach ($paramsArray as $key => $value) {
            oci_bind_by_name($query, $key, $paramsArray[$key]);
        }

        $resultados = Array();

        oci_execute($query);

        while ( ($row = oci_fetch_assoc($query)) != false) {
            $resultados[] = [
                "municipio" => $row["NOMBRE"],
                "id" => $row["ID_MUNICIPIO"],
            ];
        }
        dbClose($conn, $query);
        return $resultados;
    }
    
    function getEstados() {
        require_once("db_global.php");

        $conn = dbConnect(user, pass, server);

        $queryStr = "SELECT ID_ESTADO, NOMBRE FROM ESTADO ORDER BY ID_ESTADO";

        $query = oci_parse($conn, $queryStr);

        $resultados = Array();

        oci_execute($query);

        while ( ($row = oci_fetch_assoc($query)) != false) {
            $resultados[] = [
                'estado' => $row['NOMBRE'],
                'id' => $row['ID_ESTADO'],
            ];
        }
        dbClose($conn, $query);
        return $resultados;
    }

?>