<?php 
    session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>FortSu</title>
    <meta name="description" content="Gestión de actividades deportivas online" />
    <meta name="keywords" content="deporte,correr,ciclismo,atletismo,maratón,carrera,pulsómetro,gps,gpx,polar,garmin,suunto,zapatillas,bicicleta,ropa deportiva" />
    <meta name="author" content="FortSu.com" />
    <meta charset="UTF-8" />
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <link rel="stylesheet" href="estilo/entrenos.min.css" type="text/css"/>
    <script type="text/javascript" src="js/entrenos.min.js"></script>
    <script type="text/javascript" src="js/jquery/jquery.min.js"></script>
    <script type="text/javascript" src="js/jqueryui/current/js/jquery-ui.min.js"></script>
</head>
<body>
<?php
    include_once "./non_logged_header.php";
?>
    <div id="error_parsing" style="margin:100px 25%;font-size:x-large;color:black;width:50%;">
        <p style="margin:18px 0px 12px 0px;">¡Vaya!, se ha producido un error</p>
        <?php
            if (isset($_SESSION['error'])) {
        ?>
                <div id="error" style="margin:5px 0px;">
                    <a href="javascript:void(0)" onclick="displayDiv('arrow_error','error_details');return false;" class="prueba"><img id="arrow_error" src="images/right_arrow.png" style="vertical-align: text-top;"><span>Pulse para ver más detalles:</span></a>
                    <div id="error_details" style="display:none;font-size:small;margin:10px;">
                        <?php echo $_SESSION['error']; ?>
                    </div>
                </div>
        <?php
                # destroying session
                session_unset();
	            session_destroy();
            }

    echo "</div>";
    include_once "./home_footer.php";
?>
</body>
</html>
