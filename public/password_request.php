<?php 
    session_start();
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
                //out.innerHTML = "<span style='color: #0f0;'> OK</span>";
                return true
            } else {
                out.innerHTML = "<span style='color: #f00;'> Error</span>";
                return false
            }
        }
        function validate(myform) {
            if (checkEmail(myform.emailaddress))
                return true;
            else {
                alert("Revise la dirección de correo electrónico introducida");
                return false;
            }
        }
    </script>
</head> 
 
<body> 
<?php
    include_once "./non_logged_header.php";
    echo "<div id=\"registerForm\">";

    if (isset($_SESSION['error'])) {
        echo "No se ha podido ejecutar la petición";
        echo "<br/>";
        echo $_SESSION['error'];
        # destroying session
        session_unset();
	    session_destroy();
    } else if (isset($_SESSION['success'])) {
        echo $_SESSION['success'];
        # destroying session
        session_unset();
	    session_destroy();
        echo "<p>Ir a <a href=\"index.php\">la página inicial</a></p>";
    } else { 
?>
        <p>Introduce la dirección de correo electrónico asociada a tu cuenta en FortSu:</p>
		<form id="form_register" action="forms/formUser.php" method="post" onsubmit="return validate(this);">
		    <p><input type="email" name="emailaddress" value="correo electrónico" onblur="if(this.value==''){this.value='correo electrónico'; this.style.opacity='0.3';jQuery('#output').empty()}; if(this.value!=='correo electrónico') checkEmail(this);" onfocus="jQuery('#output').empty();if(this.value=='correo electrónico') this.value=''; this.style.opacity='1';" class="modern" /><span id="output"></span></p>
            <!-- HTML5 way of forms:
            <p><input type="text" name="input_password" value="" placeholder="contraseña" class="modern" required /></p>
            -->          
            <input name="timezone" type="hidden" value="1" />
            <input name="action" type="hidden" value="request_password" />
            <input type="hidden" name="remote_ip" value="<?php echo $_SERVER['REMOTE_ADDR']; ?>">
            <input type="hidden" name="user_agent" value="<?php echo $_SERVER['HTTP_USER_AGENT']; ?>">
            <input type="submit" id="submit_button" value="Enviar"/>
		<form />
<?php
}
	echo "</div>";
    include_once "./home_footer.php";
?>
 
</body> 
</html>
