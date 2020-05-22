<?php
    require_once("db_fns.php");

    require_once("sendMail.php");
    $error_msg = "";
    $allowTypes = array('zip','rar');
    $max_file_size = 10485760; // 10MB
    $target_dir = "/var/www/html/uploads/premionacional2020/";
    if (
        isset($_POST["nombre"]) && 
        isset($_POST["correo"]) && 
        isset($_POST["correo"]) && 
        isset($_FILES["archivo"]) && 
        isset($_POST["estado"]) && 
        isset($_POST["municipio"])) 
    {
        
    }else if(isset($_POST["idEstado"])) {
        echo json_encode(getMunicipios($_POST["idEstado"]));
    }
    function SendForm() {
        global $target_dir, $error_msg;

        $nombre = substr(trim($_POST['nombre']), 0, 512);
        $correo =  substr(trim($_POST['correo']), 0, 128);
        $telefono = substr(trim($_POST['telefono']), 0, 10);
        $estado = intval($_POST['estado']);
        $municipio = intval($_POST['municipio']);

        if (validateForm($nombre, $correo, $telefono, $estado, $municipio)){
            $allfiles = array();
            foreach($_FILES['archivo']['name'] as $key=>$val)
            {
                $imageFileType = strtolower(pathinfo($target_dir . basename($_FILES['imagen']['name'][$key]), PATHINFO_EXTENSION));

                $tmp_uid = uniqid();
                $target_file = $target_dir . $tmp_uid . "." . $imageFileType;
                $url_file = "http://www.preparados.gob.mx/uploads/premionacional2020/". $tmp_uid . "." . $imageFileType;
                array_push($allfiles, $url_file);
                if (!move_uploaded_file($_FILES["archivo"]["tmp_name"][$key], $target_file)){
                    $error_msg = 'No se pudo subir tu archivo';
                    return;
                }
            }
            $datos = [
                ":nombre"=>$nombre,
                ":correo"=>$correo,
                ":telefono"=>$telefono,
                ":estado"=>$estado,
                ":municipio"=>$municipio
            ];
            if (registrar($datos)) {
                $tmp = getUltimoRegistro($correo);
                if (enviarCorreoConfirmacion($correo, $nombre)){

                }
                else {
                    $error_msg = 'No se pudo enviar el correo de confirmación para la dirección que ingresaste.';
                }
            }
            else {
                $error_msg = 'No se pudo realizar el registro de tu solicitud';
            }

        }
        return false;
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
        $filesSent = count(array_filter($_FILES['archivo']['name']));

        if ($filesSent > 1){
            $error_msg = 'No se puede subir más de un archivo. Sube tus documentos en un archivo tipo zip o rar';
            return false;
        }
        else if ($filesSent == 0)
        {
            $error_msg = 'Sube tus documentos en un archivo tipo zip o rar';
            return false;
        }
        else
        {
            foreach($_FILES['archivo']['name'] as $key=>$val)
            {
                $fileType = strtolower(pathinfo($target_dir . basename($_FILES['archivo']['name'][$key]), PATHINFO_EXTENSION));
                if(!in_array($fileType, $allowTypes)) {
                    // not valid extension
                    $error_msg = "Extensión de archivo inválida. Debe ser de tipo zip o rar";
                    return false;
                }
                // Check file size
                if ($_FILES["archivo"]["size"][$key] > $max_file_size) {
                    // file too large
                    $error_msg = "Tamaño de archivo muy grande";
                    return false;
                }   
            }
        }
        return true;
    }

    function registrar($datos) {
        global $error_msg;
        require_once("db_global.php");

        $conn = dbConnect(user, pass, server);

        $queryStr = "INSERT INTO REGISTRO (NOMBRE, CORREO, TELEFONO, ARCHIVO, ESTADO, MUNICIPIO) 
        VALUES (:nombre,LOWER(:correo),:telefono,:archivo,:estado,:municipio)";

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

        $queryStr = "SELECT ID_ESTADO, NOMBRE FROM ESTADO";

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