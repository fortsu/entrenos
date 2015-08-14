<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8">
    <meta name="robots" content="noindex"/>
	<link rel="shortcut icon" href="images/favicon.ico">
    <title>Contacto - FortSu</title>
    <script type="text/javascript" src="js/entrenos.min.js"></script>
    <script type="text/javascript" src="js/jquery/jquery.min.js"></script>
    <link rel="stylesheet" href="estilo/entrenos.min.css" type="text/css"/>
    <script>
        function checkEmail(input) {
            var out = document.getElementById('output');
            if (/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test(input.value)) {
                out.innerHTML = "<span style='color:#008000;font-weight:bold;font-family:'Lucida Sans',Verdana,​Helvetica,​Arial;'> Ok</span>";
                return true
            } else {
                out.innerHTML = "<span style='color: #800000;font-weight: bold;font-family:'Lucida Sans',Verdana,​Helvetica,​Arial'> No válido</span>";
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

<body style="font-family:'Helvetica Neue',​Helvetica,​Arial,​sans-serif;">
    <div style="min-height:100%;">

        <!-- starting global header -->
        <div class="common-header">
            <div class="common-header-logo">
                <a href="./" title="Ir a la página inicial" style="background-color:transparent;">
                    <img src="images/logo_86x30.png" width="86" height="30" alt="logo FortSu" style="display:block;">
                </a>
            </div>
            <div class="common-header-title-container" style="margin-right:10%;">
                <div class="common-header-title">
                    <h1 class="product-title">Formulario de contacto</h1>
                </div>
            </div>
        </div>
        <!-- closing global header -->
        <div>
	        <form id="contactForm" action="" onsubmit='send_form("contactForm","result","forms/sendFeedback.php");return false;' method="post" enctype="multipart/form-data" style="margin-top:75px;">
                <div>
                    <input type="email" name="email" placeholder="Correo electrónico" class="modern_contact" size="20" required onblur="if(this.value==''){jQuery('#output').empty()}else{checkEmail(this)}" onfocus="jQuery('#output').empty();" />
                    <span id="output"></span>
                </div>
                <input type="text" name="subject" placeholder="Asunto" size="25" class="modern_contact" required /><br/>
                <textarea name="comments" type="text" cols="30" rows="7" placeholder="Comentarios" class="modern_contact" required style="font-family:'Cantarell','Helvetica Neue',​Helvetica,​Arial,​sans-serif,monospace;"></textarea><br/>
                <input type="hidden" name="user_agent" value="<?php echo $_SERVER['HTTP_USER_AGENT']; ?>">
                <input type="hidden" name="remote_ip" value="<?php echo $_SERVER['REMOTE_ADDR']; ?>">
                <input type="hidden" name="request_uri" value="<?php echo $_SERVER['REQUEST_URI']; ?>">
                <input type="submit" id="submit_button" value=" Enviar " style="font-size:14px; font-weight:bold; overflow:visible; padding:3px 20px;width:auto; margin:10px 0px;"/>
                <p class="nota">Todos los campos son obligatorios</p>
                <input type="checkbox" name="agreement" value="accept" checked="checked"/><span class="coment"> Acepto los <a href="aviso_legal.php">términos de servicio</a> y la <a href="aviso_legal.php#privacidad">política de privacidad</a> de FortSu.</span>
	        </form>
            <div id="result" style="display:none;width:40%;left:30%;"></div>
        </div>
    </div>

    <div class="common-footer">
        © FortSu 2013 |
        <a href="//<?php echo $_SERVER['SERVER_NAME']; ?>" title="Gestionar entrenamientos">Entrenamientos</a> |
        <a href="//<?php echo $_SERVER['SERVER_NAME']; ?>/aviso_legal.php" title="Formalidades">Aviso legal</a> |
        <a href="http://www.fortsu.es" title="El mejor precio para tus zapatillas">Buscador de zapatillas</a>
    </div>

</body>
</html>
