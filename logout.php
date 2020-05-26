<?php
    session_start();
    #require_once "pagina_fns.php";

    session_destroy();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="shortcut icon" href="http://www.atlasnacionalderiesgos.gob.mx/Imagenes/Logos/cenapred_icon.ico"/>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Inicio de Sesión</title>
    <!-- Materialize -->
    <!-- Compiled and minified CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <!-- Compiled and minified JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <!-- Iconos -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <!-- JQUERY -->
    <script  src="https://code.jquery.com/jquery-3.3.1.min.js"  integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="  crossorigin="anonymous"></script>

    <style>
        .sessionActive { display: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="center">
            <img style="max-width: 80%; height: auto;" src="http://www.atlasnacionalderiesgos.gob.mx/Imagenes/Logos/SSyPC_CNPC_h.png" alt="gob">
        </div>
        <h3 class="center">Sistema de candidaturas para el Premio Nacional de Protección Civil 2020</h3>
        <div class="container">
            <form method="POST">
                <div class="center">
                    <h2>Inicio de sesión</h2>
                    <blockquote>
                        <h5 id="message"></h5>
                    </blockquote>
                </div>
                <div class="row">
                    <div class="col s12">
                    <div class="row">
                        <div class="input-field col s12">
                        <i class="material-icons prefix">email</i>
                        <input type="text" name="username" id="username" class="autocomplete">
                        <label for="username">Usuario (correo)</label>
                        </div>
                    </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col s12">
                    <div class="row">
                        <div class="input-field col s12">
                        <i class="material-icons prefix">vpn_key</i>
                        <input type="password" name="pwd" id="pwd" class="autocomplete">
                        <label for="pwd">Contraseña</label>
                        </div>
                    </div>
                    </div>
                </div>
                <button class="center btn waves-effect waves-light" id="enviar" type="submit" name="action">Entrar
                    <i class="material-icons right">person</i>
                </button>
            </form>
        </div>
    </div>
    <script src="./js/login.js"></script>
</body>
</html>