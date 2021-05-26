<?php
    session_start();

    if (!isset($_SESSION["completed"])) {
        header("Location ./");
        die();
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-145898219-1"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'UA-145898219-1');
    </script>
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <link rel="shortcut icon" href="http://www.atlasnacionalderiesgos.gob.mx/Imagenes/Logos/cenapred_icon.ico"/>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1.0, maximum-scale=1.0">
    <title>Premio Nacional 2021</title>
    
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <!-- Materialize -->
        <!-- Compiled and minified CSS -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
        <!-- Compiled and minified JavaScript -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <!-- Iconos -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <link rel="stylesheet" href="./CSS/main.css">

    <style>
        .card-panel p.flow-text {
            margin: 0 auto;
        }
    </style>

    <noscript>
        <div id="div-js-error" class="valign-wrapper center red white-text">
            <i class="material-icons alerted" style="margin-left: 1em;">error_outline</i>
            <p style="width: 100%;">JavaScript está deshabilitado, esta página necesita tener activado JavaScript para que funcione correctamente.</p>
            <button id="btn-error-close" class="btn-small" type="button" style="margin-right: 1em;"><i class="material-icons">close</i></button>
        </div>
    </noscript>
    
</head>
<body>
    <div class="container">
        <div class="center">
            <img class="cnpc" src="http://www.atlasnacionalderiesgos.gob.mx/Imagenes/Logos/SSyPC_CNPC_h.png" alt="Gobierno de México">
        </div>
        <h1 class="flow-text center" style="margin: 1em auto;">Premio Nacional de Protección Civil 2021</h1>
        <hr>

        <div class="row">
            <div class="card-panel green white-text center">
                <p class="flow-text">¡Registro realizado correctamente!</p>
            </div>
        </div>

        <div class="row">
            <?php if ($_SESSION["confirmacion"]) { ?>
                <div class="card-panel blue white-text center">
                    <p class="flow-text">Se ha enviado exitosamente un correo confirmando tu registro.</p>
                </div>
            <?php } else { ?>
                <div class="card-panel red white-text center">
                    <p class="flow-text">No se pudo enviar el correo electrónico de confirmación para la dirección que ingresaste. Es posible que la hayas ingresado erróneamente.</p>
                </div>
                <p>Correo ingresado: <strong><?=$_SESSION["correo"]?></strong></p>
            <?php } ?>
            <h5>Tu número de folio es <?=$_SESSION["id_registro"]?></h5>
        </div>

        <div class="row">
            <a class="btn guinda white-text" href="http://www.preparados.gob.mx">Volver a Preparados</a>
        </div>
    </div>
</body>
</html>