<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Confirmación de registro</title>
    <style>
        h1 {
            background: #285C4D;
            margin: 0;
            padding: 0.5em;
            color: white;
        }

        a {
            color: #EAA739;
            font-weight: bold;
        }

        .dorado {
            color: #B38E5D;
            text-decoration: underline;
        }
        .center {
            text-align: center;
        }
    </style>
</head>
<body>
<div class="container">
    <h1 class="dorado center">¡Tu candidatura ha sido registrada!</h1>
    <hr>
    <h5>Premio Nacional 2020</h5>

    <div class="section">
        <p>Estimado <strong><span>:registrationName</span></strong>: </p>
        <p>En la Coordinación Nacional de Protección Civil hemos registrado tu candidatura, la cuál será revisada conforme a las bases establecidas en la convocatoria.
        <br>Por lo que te pedimos estés al tanto de tu correo electrónico, pues será la vía de comunicación para cualquier aclaración.
        <br>De la misma manera sigue nuestras redes sociales de <a href="https://www.facebook.com/CNPCmx/">Facebook</a> y <a href="https://twitter.com/CNPC_mx">Twitter</a> para futuros anuncios del evento.</p>
    </div>
    <div class="divider"></div>
    <div class="section">
        <p>DATOS DEL REGISTRO:</p>
        <ul>
            <li><strong><h4>Tu número de candidatura es: <span>:registrationID</span></h4></strong></li>
            <li><strong><h4>Tu típo de registro es: <span>:registrationType</span></h4></strong></li>
            <li><strong><h4>La categoría de tu registro es: <span>:registrationCat</span></h4></strong></li>
        </ul>
    </div>
</div>
</body>
</html>