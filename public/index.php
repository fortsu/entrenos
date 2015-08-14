<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/global_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/database.php';

require_once $base_path . "/check_access.php";

if ($_SESSION['user_id'] > 0) {
    $redirect = "/calendar.php";
    $log->debug("Redirect logged user " . $_SESSION['login'] . " (" . $_SESSION['user_id'] . ") to " . $redirect);
    header('Location: ' . $redirect);
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
	<link rel="shortcut icon" href="images/favicon.ico">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <title>Gestiona tus actividades deportivas - FortSu</title>
    <link rel="stylesheet" href="/estilo/entrenos.min.css?<?php echo $fv ?>" type="text/css"/>
    <meta name="keywords" content="registro,actividades,deporte" />
    <meta name="author" content="entrenos.fortsu.com" />
</head>

<body>
<div id="home_header">
	<div id="login_box">
		<form id="form_login" action="forms/formUser.php" method="post" onsubmit="login.sending_submit(); return true;">
            <input id="action" name="action" type="hidden" value="check" />
            <input id="timezone" name="timezone" type="hidden" value="1" />
            <div>
		        <input id="login" name="login" type="text" value="email o usuario" onblur="if(this.value==''){this.value='email o usuario';this.style.opacity='0.5';}" onfocus="if(this.value=='email o usuario') this.value='';this.style.opacity='1';" tabindex="1" class="modern_med" style="margin:2px"/>
				<input id="remember" name="remember" type="checkbox" value="1" tabindex="3" checked/><span style="font-size:smaller">Recordar</span>
            </div>
            <div>
        	    <input id="input_password" name="input_password" type="text" value="contraseña" onblur="if(this.value==''){this.value='contraseña'; this.setAttribute('type','text');this.style.opacity='0.5';}" onfocus="if(this.value=='contraseña')this.value='';this.setAttribute('type','password');this.style.opacity='1';" tabindex="2" class="modern_med" style="margin:2px"/>
		        <input type="submit" id="submit_button" value="Entrar" tabindex="4"/>
            </div>
		</form>
        <?php
            if (!empty($_SESSION['login_error'])) {
                echo "<br />";
                echo "\r\n";
                echo "<div id=\"error_login\">" . $_SESSION['login_error'] . "</div>";
                unset($_SESSION['login_error']);
            }
        ?>
        <div style="margin:8px">
		    <a href="register.php" title="Darse de alta">Abrir cuenta</a> |
            <a href="https://graph.facebook.com/oauth/authorize?client_id=115493161855991&amp;redirect_uri=<?php echo $base_url; ?>/oauth/fb_login.php&amp;scope=publish_stream,offline_access,email" style="background-color:transparent">
                <img title="Entra con tu cuenta de Facebook" alt="Facebook login" src="images/connect_favicon.png">
            </a> |
            <a href="password_request.php" title="Recupera acceso a tu cuenta">¿Contraseña olvidada?</a>
        </div>
    </div>
</div>
<div class="home-body">
    <img src="images/logo_687x243.png" title="FortSu, sin esfuerzo no hay éxito" alt="logo FortSu" />
    <p>Sin esfuerzo no hay éxito</p>
</div>
<?php
    include_once "./home_footer.php";
?>
</body>
</html>
