<?php
    require_once("db_fns.php");
    if (isset($_POST["idMunicipio"]) && isset($_POST["idEstado"])) {
        echo json_encode(getColonias($_POST["idEstado"], $_POST["idMunicipio"]));
    }
    else if(isset($_POST["idEstado"])) {
        echo json_encode(getMunicipios($_POST["idEstado"]));
    } else if(isset($_GET["inmuebles"])) {
        getInmuebles();
    } else if(isset($_GET["inmueblesMapa"])) {
        getInmueblesMapa($_GET["mapa"]);
    } else if(isset($_GET["inmueblesCDMX"])){
        getInmueblesCDMX();
    } else if(isset($_POST["hipotesis"])){
        switch ($_POST["hipotesis"]) {
            case 'N':
                echo json_encode(getHipotesis());
                break;
            case 'E':
                echo json_encode(getHipotesisBC());
                break;
            default:
                break;
        }
    }
    // else {
    //     echo json_encode(["sin datos"]);
    // }
    function getInmuebles() {
        require_once("db_global.php");

        $conn = dbConnect(user, pass, server);

        $queryStr = "SELECT E.NOMBRE ESTADO, I.MUNICIPIO MUNICIPIO, I.HIPOTESIS HIPOTESIS, I.LAT LAT, I.LON LON, I.TIPOINMUEBLE TIPOINMUEBLE, I.PARTICIPANTES FROM INMUEBLE2 I ".
            "INNER JOIN ESTADO E ".
            "ON I.ESTADO = E.ID_ESTADO";

        $query = oci_parse($conn, $queryStr);

        $resultados = Array();

        oci_execute($query);

        while ( ($row = oci_fetch_assoc($query)) != false) {
            $resultados[] = [
                "estado" => $row['ESTADO'],
                "municipio" => $row['MUNICIPIO'],
                "hipotesis" => $row['HIPOTESIS'],
                "lat" => $row['LAT'],
                "lon" => $row['LON'],
                "tipoInmueble" => $row["TIPOINMUEBLE"],
                "participantes" => $row["PARTICIPANTES"]
            ];
        }
        #This section is commented meanwhile the developers of CDMX prepare his web site to register more buildings
        /*$cdmx = json_decode(file_get_contents('/home/preparados/macrosimulacro/cdmx.json'), true);
        foreach ($cdmx as $key => $value) {
            $resultados[] = $value;
        }*/
        echo json_encode($resultados);
    }

    function getInmueblesMapaMayo() {
        require_once("db_global.php");

        $conn = dbConnect(user, pass, server);

        $queryStr = "SELECT E.NOMBRE ESTADO, I.MUNICIPIO MUNICIPIO, I.HIPOTESIS HIPOTESIS, I.LAT LAT, I.LON LON, I.TIPOINMUEBLE TIPOINMUEBLE, I.PARTICIPANTES FROM MAYO2020 I ".
            "INNER JOIN ESTADO E ".
            "ON I.ESTADO = E.ID_ESTADO";

        $query = oci_parse($conn, $queryStr);

        $resultados = Array();

        oci_execute($query);

        while ( ($row = oci_fetch_assoc($query)) != false) {
            $resultados[] = [
                "estado" => $row['ESTADO'],
                "municipio" => $row['MUNICIPIO'],
                "hipotesis" => $row['HIPOTESIS'],
                "lat" => $row['LAT'],
                "lon" => $row['LON'],
                "tipoInmueble" => $row["TIPOINMUEBLE"],
                "participantes" => $row["PARTICIPANTES"]
            ];
        }
        /*This section is commented meanwhile the developers of CDMX prepare his web site to register more buildings
        $cdmx = json_decode(file_get_contents('/home/preparados/macrosimulacro/cdmx.json'), true);
        foreach ($cdmx as $key => $value) {
            $resultados[] = $value;
        }*/
        echo json_encode($resultados);
    }

    function getInmueblesMapa($tabla) {
        require_once("db_global.php");

        $conn = dbConnect(user, pass, server);

        $queryStr = "SELECT E.NOMBRE ESTADO, I.MUNICIPIO MUNICIPIO, I.HIPOTESIS HIPOTESIS, I.LAT LAT, I.LON LON, I.TIPOINMUEBLE TIPOINMUEBLE, I.PARTICIPANTES FROM $tabla I ".
            "INNER JOIN ESTADO E ".
            "ON I.ESTADO = E.ID_ESTADO";

        $query = oci_parse($conn, $queryStr);

        $resultados = Array();

        oci_execute($query);

        while ( ($row = oci_fetch_assoc($query)) != false) {
            $resultados[] = [
                "estado" => $row['ESTADO'],
                "municipio" => $row['MUNICIPIO'],
                "hipotesis" => $row['HIPOTESIS'],
                "lat" => $row['LAT'],
                "lon" => $row['LON'],
                "tipoInmueble" => $row["TIPOINMUEBLE"],
                "participantes" => $row["PARTICIPANTES"]
            ];
        }
        /*This section is commented meanwhile the developers of CDMX prepare his web site to register more buildings
        $cdmx = json_decode(file_get_contents('/home/preparados/macrosimulacro/cdmx.json'), true);
        foreach ($cdmx as $key => $value) {
            $resultados[] = $value;
        }*/
        echo json_encode($resultados);
    }

    function getInmueblesCDMX() {
        require_once("db_global.php");

        $conn = dbConnect(user, pass, server);

        $queryStr = "SELECT E.NOMBRE ESTADO, I.MUNICIPIO MUNICIPIO, I.HIPOTESIS HIPOTESIS, I.LAT LAT, I.LON LON, I.TIPOINMUEBLE TIPOINMUEBLE, I.INSTITUCION FROM INMUEBLE2 I ".
            "INNER JOIN ESTADO E ".
            "ON I.ESTADO = E.ID_ESTADO ".
            "WHERE ESTADO = 9 ";

        $query = oci_parse($conn, $queryStr);

        $resultados = Array();

        oci_execute($query);

        while ( ($row = oci_fetch_assoc($query)) != false) {
            $resultados[] = [
                "estado" => $row['ESTADO'],
                "municipio" => $row['MUNICIPIO'],
                "hipotesis" => $row['HIPOTESIS'],
                "lat" => $row['LAT'],
                "lon" => $row['LON'],
                "tipoInmueble" => $row["TIPOINMUEBLE"],
                "institucion" => $row["INSTITUCION"]
            ];
        }

        echo json_encode($resultados);
    }

    function getTipoInmueble() {
        require_once("db_global.php");

        $conn = dbConnect(user, pass, server);

        $queryStr = "SELECT * FROM TIPOINMUEBLE";

        $query = oci_parse($conn, $queryStr);

        $resultados = Array();

        oci_execute($query);

        while ( ($row = oci_fetch_assoc($query)) != false) {
            $resultados[$row['GRUPO']][] = $row['TIPO'];
        }
        return $resultados;
    }
    function getHipotesis() {
        require_once("db_global.php");

        $conn = dbConnect(user, pass, server);

        $queryStr = "SELECT * FROM HIPOTESIS";

        $query = oci_parse($conn, $queryStr);

        $resultados = Array();

        oci_execute($query);

        while ( ($row = oci_fetch_assoc($query)) != false) {
            $resultados[$row['CATEGORIA']][] = $row['HIPOTESIS'];
        }
        return $resultados;
    }
    function getHipotesisBC() {
        require_once("db_global.php");

        $conn = dbConnect(user, pass, server);

        $queryStr = "SELECT * FROM HIPOTESIS WHERE ID = 1";

        $query = oci_parse($conn, $queryStr);

        $resultados = Array();

        oci_execute($query);

        while ( ($row = oci_fetch_assoc($query)) != false) {
            $resultados[$row['CATEGORIA']][] = $row['HIPOTESIS'];
        }
        return $resultados;
    }

    function getMunicipios($State) {
        require_once("db_global.php");

        $conn = dbConnect(user, pass, server);

        $paramsArray = Array(
            ":Estado" => trim($State),
        );

        $queryStr = "select municipio id, initcap(municipio) municipio from colonia2 where UPPER(estado) = UPPER(:Estado) group by municipio order by municipio asc";

        $query = oci_parse($conn, $queryStr);

        foreach ($paramsArray as $key => $value) {
            oci_bind_by_name($query, $key, $paramsArray[$key]);
        }

        $resultados = Array();

        oci_execute($query);

        while ( ($row = oci_fetch_assoc($query)) != false) {
            $resultados[] = [
                "municipio" => $row["MUNICIPIO"],
                "id" => $row["ID"],
            ];
        }
        dbClose($conn, $query);
        return $resultados;
    }
    function getMunicipios2($idEstado) {
        require_once("db_global.php");

        $conn = dbConnect(user, pass, server);

        $paramsArray = Array(
            ":idEstado" => intval(trim($idEstado))
        );

        $queryStr = "SELECT ID_MUNICIPIO, NOMBRE FROM MUNICIPIO ".
            "WHERE ID_ESTADO = :idEstado ".
            "ORDER BY NOMBRE";

        $query = oci_parse($conn, $queryStr);

        foreach ($paramsArray as $key => $value) {
            oci_bind_by_name($query, $key, $paramsArray[$key]);
        }

        $resultados = Array();

        oci_execute($query);

        while ( ($row = oci_fetch_assoc($query)) != false) {
            $resultados[] = [
                "idMunicipio" => $row["ID_MUNICIPIO"],
                "nombre" => $row["NOMBRE"]
            ];
        }
        dbClose($conn, $query);
        return $resultados;
    }

    function getColonias($idEstado, $idMunicipio) {
        require_once("db_global.php");

        $conn = dbConnect(user, pass, server);

        $paramsArray = Array(
            ":idE" => trim($idEstado),
            ":idM" => trim($idMunicipio),
        );

        $queryStr = "select id, initcap(nombre) nombre, CP from colonia2 where UPPER(estado) = UPPER(:idE) and UPPER(municipio) = UPPER(:idM) order by nombre asc";

        $query = oci_parse($conn, $queryStr);

        foreach ($paramsArray as $key => $value) {
            oci_bind_by_name($query, $key, $paramsArray[$key]);
        }

        $resultados = Array();

        oci_execute($query);

        while ( ($row = oci_fetch_assoc($query)) != false) {
            $resultados[] = [
                "nombre" => $row["NOMBRE"],
                "cp" => $row["CP"],
                "id" => $row["ID"]
            ];
        }
        dbClose($conn, $query);
        return $resultados;
    }
    function getColonias2($idEstado, $idMunicipio) {
        require_once("db_global.php");

        $conn = dbConnect(user, pass, server);

        $paramsArray = Array(
            ":idE" => intval(trim($idEstado)),
            ":idM" => intval(substr(trim($idMunicipio),-3))
        );

        $queryStr = "SELECT ID_COLONIA, NOMBRE, CP FROM COLONIA ".
            "WHERE ESTADO = :idE AND MUNICIPIO = :idM ".
            "ORDER BY NOMBRE";

        $query = oci_parse($conn, $queryStr);

        foreach ($paramsArray as $key => $value) {
            oci_bind_by_name($query, $key, $paramsArray[$key]);
        }

        $resultados = Array();

        oci_execute($query);

        while ( ($row = oci_fetch_assoc($query)) != false) {
            $resultados[] = [
                "idColonia" => $row["ID_COLONIA"],
                "nombre" => $row["NOMBRE"],
                "cp" => $row["CP"]
            ];
        }
        dbClose($conn, $query);
        return $resultados;
    }

?>