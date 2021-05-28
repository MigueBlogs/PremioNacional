<?php 
    $today = time();
    $date_start = strtotime('2021-05-31 13:00:00.0');  // UTC for 31 May, 08:00 GMT-5 (México City)
    $date_end = strtotime('2021-06-30 23:00:00.0');  // UTC for 30 June, 18:00 GMT-5 (México City)
    $available = $today - $date_start >= 0 ? true : false;
    $expired = $today - $date_end >= 0 ? true: false;
    
?>
<!DOCTYPE html>
<html lang="es">
<head>
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

    <noscript>
        <div id="div-js-error" class="valign-wrapper center" style="background-color: lightcoral;">
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
        <h1 class="flow-text center">Premio Nacional de Protección Civil 2021</h1>
        <hr>
        <?php if (!$available) { ?>
            <h5>¡La inscripción aún no está disponible!</h5>
            <p>El portal de inscripciones para el Premio Nacional de Protección Civil 2021 aún no está disponible. 
                Te pedimos estar atento a las redes sociales para la fecha de inicio de la convocatoria.
            </p>

        <?php } else if ($expired) { ?>
            <h5>¡Gracias por participar!</h5>
            <p>El tiempo para registrar tu candidatura ha finalizado. 
                Si realizaste tu registro correctamente, mantente al pendiente de la publicación de los ganadores en el Diario Oficial de la Federación un día antes de la entrega del Premio.
            </p>
        <?php } ?>
        <p>
            Síguenos en nuestras redes sociales:
        </p>
        <div class="center">
            <a target="_blank" href="http://www.facebook.com/cnpcmx"><img alt="fb" src="img/f_logo_RGB-Blue_58.png" width="50px" height="50px"></a>
            <a target="_blank" href="http://twitter.com/CNPC_mx"><img src="img/twitter.png" width="50px" height="50px"></a>
            <a target="_blank" href="http://www.youtube.com/coordinacionnacionaldeproteccioncivil"><img alt="yt" src="img/youtube.png" width="50px" height="50px"></a>
        </div>
        <p>
            <a class="btn guinda white-text" href="http://www.preparados.gob.mx" target="_blank">Volver a www.preparados.gob.mx</a>
        </p>
    </div>
</body>
</html>