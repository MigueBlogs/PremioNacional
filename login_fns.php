<?php
    session_start();
    require_once("db_fns.php");

    // ESTATUS DE SESION
    // 0 Usuario o contraseña incorrecto
    // 1 Iniciada correctamente
    // 2 Ya se había iniciado sesión

    if(isset($_POST['login']) && isset($_POST['username']) && isset($_POST['pwd'])) {
        $username = $_POST['username'];
        $pwd = $_POST['pwd'];

        if(!isset($_SESSION['username'])){
            login($username, $pwd);
            ActHora($username);
        }else{
            echo json_encode(["status" => "2", "message" => "Ya se había iniciado una sesión. Por favor, cierra la sesión activa e intenta nuevamente."]);
        };
    };

    function login($username, $pwd) {
        require_once("db_global.php");

        $conn = dbConnect(user, pass, server);
       

        $paramsArray = Array(
            ":username" => $username,
            ":pwd" => $pwd
        );

        $queryStr = "SELECT CORREO, NOMBRE, AP_PATERNO FROM AUTOR ".
            "WHERE CORREO = :username ".
            "AND PWD = :pwd";
        
        $query = oci_parse($conn, $queryStr);

        foreach ($paramsArray as $key => $value) {
            oci_bind_by_name($query, $key, $paramsArray[$key]);
        }

        oci_execute($query);
        $ar = Array();

        

        while ( ($row = oci_fetch_assoc($query)) != false) {
            $username = $row["CORREO"];
            $ar["username"] = $username;
            $name = $row["NOMBRE"];
            $ar["name"] = $name;
            $lastname = $row["AP_PATERNO"];
            $ar["lastname"] = $lastname;
            $ar["status"] = 1;
            $ar["message"] = "Sesión iniciada";
            #echo json_encode($ar);
        }
        
        if(isset($ar["username"])) {
            //mandamos a la info de la sesión los datos del usuario
            $_SESSION['username'] = $ar["username"];
            $_SESSION['name'] = $ar["name"];
            $_SESSION['lastname'] = $ar["lastname"];
            echo json_encode($ar);
        } else {
            $ar["status"] = 0;
            $ar["message"] = "Usuario o contraseña incorrectos";
            echo json_encode($ar);
        }
        dbClose($conn, $query);
    }

    function ActHora($dato) {
        $paramsArray = Array(
            ":correo" => $dato,
        );
        
        require_once("db_global.php");

        $conn2 = dbConnect(user, pass, server);

        $queryStrDate = "UPDATE autor SET ACCESO = SYSDATE  WHERE CORREO = :correo";

        $query2 = oci_parse($conn2, $queryStrDate);

        foreach ($paramsArray as $key => $value) {
            oci_bind_by_name($query2, $key, $paramsArray[$key]);
        }        

        oci_execute($query2);

        dbClose($conn2, $query2);
        return true;
    }
?>