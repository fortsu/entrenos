<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/global_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/database.php';
use Entrenos\Token;
use Entrenos\User;

if (isset($_REQUEST['token'])) {
    $token = $_REQUEST['token'];
    $log->info($_SERVER['REMOTE_ADDR'] . " | " . $_SERVER['HTTP_USER_AGENT'] . " | Recovering password. Token: " . $token);
    try {
        $current_token = new Token (FALSE, array('token'=>$token));
        $user_id = $current_token->exists($conn);
?>

<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8">
    <title>FortSu</title>
    <script type="text/javascript" src="js/entrenos.min.js"></script>
    <script type="text/javascript" src="js/jquery/jquery.min.js"></script>
    <link rel="stylesheet" href="estilo/entrenos.min.css" type="text/css"/>
    <script>
        function validate(myform) {
            var out = document.getElementById('output');
            var clave1 = myform.input_password.value;
            if (myform.input_password.value === myform.input_password.getAttribute('data-orig')){
                myform.input_password.className += " field-error";
                myform.input_password.style.opacity='1';
                return false;
            }
            var clave2 = myform.input_password2.value;
            if (myform.input_password2.value === myform.input_password2.getAttribute('data-orig')){
                myform.input_password2.className += " field-error";
                myform.input_password2.style.opacity='1';
                return false;
            }
            if (clave1 === clave2) {
                //out.innerHTML = "<span style='color: #0f0;'> OK</span>";
                return true;
            } else {
                out.innerHTML = "<span style='color: #f00;'> ERROR</span>";
                alert ("La repetición de la contraseña no coincide con el original:\r\n\tOriginal: " + clave1 + "\r\n\tRepetición: " + clave2);
                return false;
            }
        }
    </script>
</head> 
 
<body> 
<?php
    include_once "./non_logged_header.php";
        if ($user_id > 0) { // 0 means no user found, -1 date expired
            $log->info("User " . $user_id . " matches with token " . $current_token->token);
            $current_user = new User(array('id'=>$user_id));
            // Retrieving data from user
            $current_user->userFromId($conn);
            # Display form to set a new password
?>
	<div id="registerForm">
        <p>Introduce la nueva contraseña</p>
		<form id="form_register" action="forms/formUser.php" method="post" onsubmit="return validate(this);">
		    <p><input type="email_display" name="emailaddress" value="<?php echo $current_user->email ?>" class="modern" disabled="disabled"/></p>
            <p><input type="text" name="input_password" value="contraseña" data-orig="contraseña" onblur="if(this.value==''){this.value=this.getAttribute('data-orig'); this.setAttribute('type','text'); this.style.opacity='0.3';}" onfocus="jQuery(this).removeClass('field-error');if(this.value==this.getAttribute('data-orig')) this.value=''; this.setAttribute('type','password'); this.style.opacity='1';" class="modern" /></p>
            <p><input type="text" name="input_password2" value="repita contraseña" data-orig="repita contraseña" onblur="if(this.value==''){this.value=this.getAttribute('data-orig'); this.setAttribute('type','text'); this.style.opacity='0.3';}" onfocus="jQuery(this).removeClass('field-error');jQuery('#output').empty();if(this.value==this.getAttribute('data-orig')) this.value=''; this.setAttribute('type','password'); this.style.opacity='1';" class="modern" /><span id="output"></span></p>
            <!-- HTML5 way of forms:
            <p><input type="text" name="input_password" value="" placeholder="contraseña" class="modern" required /></p>
            -->          
            <input name="timezone" type="hidden" value="1" />
            <input name="action" type="hidden" value="new_password" />
            <input type="hidden" name="user_agent" value="<?php echo $_SERVER['HTTP_USER_AGENT']; ?>">
            <input type="hidden" name="remote_ip" value="<?php echo $_SERVER['REMOTE_ADDR']; ?>">
            <input type="hidden" name="token" value="<?php echo $token; ?>">
            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
            <input type="hidden" name="email" value="<?php echo $current_user->email ?>">
            <input type="submit" id="submit_button" value="Enviar"/>
            <p class="nota">La repetición sirve para descartar fallos en la escritura.</p>
		<form />        
	</div>
<?php
        } else if ($user_id === 0) { // no user found
            $log->error("No user found for token " . $current_token->token . " | " . $_SERVER['REMOTE_ADDR'] . " | " . $_SERVER['HTTP_USER_AGENT']);
            echo "<div id=\"registerForm\">";
                echo "<p>No se ha encontrado un usuario válido para la solicitud.</p>";
                echo "<p>Volver a la <a href=\"index.php\">página de inicio</a>.</p>";
            echo "</div>";
        } else { // token found but already expired
            $log->info("Token " . $current_token->token . " found but already expired. Removed");
            echo "<div id=\"registerForm\">";
                echo "<p>La petición ha caducado porque han pasado más de 6 horas desde su solicitud.</p>";
                echo "<p>Volver a <a href=\"password_request.php\">solicitar una nueva contraseña</a></p>";
            echo "</div>";
        }

    include_once "./home_footer.php";
?>
 
</body> 
</html>

<?php
    } catch (Exception $e) {
        echo "Se ha producido un error. Póngase en contacto con usuarios@fortsu.com";
        $log->error($e->getMessage());
    }
} else {
    $log->error("No token provided | " . $_SERVER['REMOTE_ADDR'] . " | " . $_SERVER['HTTP_USER_AGENT'],0);
    header ('Location: ' . $base_url);
}
exit();
?>
