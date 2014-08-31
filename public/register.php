<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/global_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/database.php';
session_start();
use Entrenos\User;
use Entrenos\Token;
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<link rel="shortcut icon" href="images/favicon.ico">
    <title>FortSu</title>
    <script type="text/javascript" src="js/entrenos.min.js"></script>
    <script type="text/javascript" src="js/jquery/jquery.min.js"></script>
    <link rel="stylesheet" href="estilo/entrenos.min.css" type="text/css"/>
    <script>
        function checkEmail(input) {
            var out = document.getElementById('output');
            if (/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test(input.value)) {
                return true
            } else {
                out.innerHTML = "<span style='color: #f00;'> ERROR</span>";
                return false
            }
        }

        //TODO: check upfront if username is available in the system
        function validate(myform) {
            if (myform.username.value === myform.username.getAttribute('data-orig')){
                myform.username.className += " field-error";
                myform.username.style.opacity='1';
                return false;
            }
            if (checkEmail(myform.emailaddress) === false) {
                return false;
            }
            if (myform.input_password.value === myform.input_password.getAttribute('data-orig')){
                myform.input_password.className += " field-error";
                myform.input_password.style.opacity='1';
                return false;
            } 
            return true;
        }
    </script>
</head> 
 
<body> 
	<div id="register_header">
		<a href="index.php" title="Ir a la página inicial" style="background-color:transparent"><img src="images/logo_146x52.png" alt="logo FortSu"></a>
	</div>
 
	<div id="registerForm">

<?php
if (isset($_REQUEST['token'])) {
    $token = $_REQUEST['token'];
    $log->info($_SERVER['REMOTE_ADDR'] . " | " . $_SERVER['HTTP_USER_AGENT'] . " | New user trying to validate. Token: " . $token);
    try {

        $current_token = new Token (FALSE, array('token'=>$token));
        $value = $current_token->exists($conn);
        if ($value > 0) {
            $log->info("User " . $current_token->user_id . " matches with token " . $current_token->token);
            $current_user = new User(array('id'=>$current_token->user_id));
            $current_user->userFromId($conn);
            $current_user->enable($conn);
            $source_daily_report_url = "./../reports/dia.php";
            $user_daily_report = "users/" . $current_user->id . "/reports/daily_report.php";
            if (!copy($source_daily_report_url, $user_daily_report)) {
                $log->error("Error when copying daily report: " . json_encode(error_get_last()));
            } else {
                $log->info("Daily report script copied to " . $user_daily_report);
            }
            $current_user->remove_token($token, $conn);
            echo "<p>Se ha activado la cuenta de usuario <b>" . $current_user->username . "</b>.</p>";
            echo "<p>Vaya a la <a href=\"index.php\">página de inicio</a> para acceder a FortSu.</p>";
            $log->info("User id " . $current_user->id . " successfully validated");
        } else if ($value < 0) { // token expired
            $current_token->delete($conn);
            $log->info("Token " . $current_token->token . " found but already expired. Removed");
            //removing not enabled entry in users table
            $expired_user = new User(array('id'=>$current_token->user_id));
            $expired_user->delete_from_db($conn);
            $log->info("Removed expired entry " . $expired_user->id . " from DB");
            echo "<p>Han pasado más de 6 horas desde la solicitud de creación de la cuenta de usuario.</p>";
            echo "<p>Volver a <a href=\"register.php\">solicitar una cuenta de usuario</a></p>";
        } else { // invalid token, no user found
            $log->error("No user found for token " . $current_token->token . " | " . $_SERVER['REMOTE_ADDR'] . " | " . $_SERVER['HTTP_USER_AGENT']);
            echo "<p>No se ha encontrado un usuario válido para el token proporcionado.</p>";
            echo "<p>¿Quieres <a href=\"register.php\">abrir una nueva cuenta</a>?</p>";
        }
    } catch (Exception $e) {
        echo "<p>Se ha producido un error al validar la cuenta. Póngase en contacto con usuarios@fortsu.com</p>";
        $log->error($e->getMessage());
    }
} else if (isset($_SESSION['error'])) {
    echo $_SESSION['error'];
    # destroying session
    session_unset();
	session_destroy();
    echo "<p>Ir a <a href=\"index.php\">la página inicial</a></p>";
} else if (isset($_SESSION['success'])) {
    echo $_SESSION['success'];
    # destroying session
    session_unset();
	session_destroy();
    echo "<p>Ir a <a href=\"index.php\">la página inicial</a></p>";
} else {

?>
		<form id="form_register" action="forms/formUser.php" method="post" onsubmit="return validate(this);">
            <p><input type="text" name="username" value="nombre de usuario" data-orig="nombre de usuario" onblur="if(this.value==''){this.value=this.getAttribute('data-orig'); this.style.opacity='0.3';}" onfocus="jQuery(this).removeClass('field-error');if(this.value==this.getAttribute('data-orig')) this.value=''; this.style.opacity='1';" class="modern" /></p>
		    <p><input type="email" name="emailaddress" value="correo electrónico" onblur="if(this.value==''){this.value='correo electrónico'; this.style.opacity='0.3';jQuery('#output').empty()}; if(this.value!=='correo electrónico') checkEmail(this);" onfocus="jQuery('#output').empty();if(this.value=='correo electrónico') this.value=''; this.style.opacity='1';" class="modern" /><span id="output"></span></p>
            <p><input type="text" name="input_password" value="contraseña" data-orig="contraseña" onblur="if(this.value==''){this.value=this.getAttribute('data-orig'); this.setAttribute('type','text'); this.style.opacity='0.3';}" onfocus="jQuery(this).removeClass('field-error');if(this.value==this.getAttribute('data-orig')) this.value=''; this.setAttribute('type','password'); this.style.opacity='1';" class="modern" /></p>
            <!-- HTML5 way of forms:
            <p><input type="text" name="username" placeholder="nombre de usuario" class="modern" required /></p>
            <p><input type="email" name="emailaddress" value="" placeholder="correo electrónico" class="modern" required /><span id="output"></span></p>
            <p><input type="text" name="input_password" value="" placeholder="contraseña" class="modern" required /></p>
            -->          
            <input name="timezone" type="hidden" value="1" />
            <input name="action" type="hidden" value="creation" />
            <input type="hidden" name="user_agent" value="<?php echo $_SERVER['HTTP_USER_AGENT']; ?>">
            <input type="hidden" name="remote_ip" value="<?php echo $_SERVER['REMOTE_ADDR']; ?>">
            <input type="submit" id="submit_button" value="Enviar"/>
            <p class="nota">Todos los campos son obligatorios</p>
            <input type="checkbox" name="agreement" value="accept" checked="checked"/><span class="coment"> Acepto los <a rel="nofollow" href="aviso_legal.php">términos de servicio</a> y la <a rel="nofollow" href="privacy.php">política de privacidad</a> de FortSu.</span>
		<form /> 
<?php
}
	echo "</div>";
    include_once "./home_footer.php";
?>
 
</body> 
</html>
