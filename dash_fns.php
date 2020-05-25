<?php
require_once("db_fns.php");
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

        $queryStr = "SELECT R.NOMBRE, R.CORREO, R.TELEFONO, R.ARCHIVO, M.NOMBRE as MUNICIPIO, E.NOMBRE as ESTADO, R.FECHA FROM MUNICIPIO M, REGISTRO R, ESTADO E where M.ID_MUNICIPIO = R.MUNICIPIO and E.ID_ESTADO =R.ESTADO order by R.FECHA desc";
        //entre I2 y group tenía: WHERE INSTITUCION='F'
        $query = oci_parse($conn, $queryStr);

        foreach ($paramsArray as $key => $value) {
            oci_bind_by_name($query, $key, $paramsArray[$key]);
        }

        oci_execute($query);
        $ar = Array();

        while ( ($row = oci_fetch_assoc($query)) != false) {
            $result = Array();
            $result["nombre"] = $row["NOMBRE"];
            $result["correo"] = $row["CORREO"];
            $result["telefono"] = $row["TELEFONO"];
            $result["archivo"] = $row["ARCHIVO"];
            $result["municipio"] = $row["MUNICIPIO"];
            $result["estado"] = $row["ESTADO"];
            $result["fecha"] = $row["FECHA"];
            $ar[] = $result;
        }

        dbClose($conn, $query);
        echo json_encode($ar);
        return $ar;
    }


?>