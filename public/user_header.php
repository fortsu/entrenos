    <div id="corporate">
        <div id="logo">
            <a href="<?php echo $base_url; ?>" title="Ir a la página inicial" style="background-color:transparent;">
                <img src="<?php echo $base_url; ?>/images/logo_146x52.png">
            </a>
        </div>
<?php
    if ($current_user->id > 0) {
?>
        <div id="feedback">
            <div>
                <?php
                // $current_user must have been checked in parent page!
	            echo " " . $current_user->username . " (<a href=\"" . $base_url . "/logout.php\" style=\"font-family:'Lucida Sans',Verdana,Arial,Helvetica,sans-serif;\">desconectar</a>)";
                ?>
            </div>
            <a href="javascript:void(0)" onclick="launch_feedback('feedback_form','result','feedbackForm')">¿Algo que contar?</a>
        </div>
        <!-- float divs must appear before the center section -->
        <div id="anuncio">
<?php
    $messages = array("Mejor gestión de deportes, próximamente en sus pantallas",
                      "Puedes <a href=\"" . $base_url . "/user_settings.php\">mostrar tus rutas</a> en mapas de OpenStreetMaps o Google Maps",
                      "Apunta fallos, mejoras o sugerencias en el <a href=\"https://github.com/fortsu/entrenos/issues\">sistema de seguimiento de errores</a>",
                      "Código fuente disponible: <a href=\"https://github.com/fortsu/entrenos\">fork me on GitHub!</a>");
    $minutes = intval(date('i'));
    $msg_index = $minutes % count($messages);
    echo $messages[$msg_index];
?>
        </div>
    </div>

    <div id="feedbackForm" style="display:none;">
        <form id="feedback_form" action="" onsubmit='sendFormCheck(this,"result","forms/sendFeedback.php");return false;' method="post" enctype="multipart/form-data">
            <input type="hidden" name="user_id" value="<?php echo $current_user->id; ?>">
            <input type="hidden" name="php_script" value="<?php echo $_SERVER['SCRIPT_NAME']; ?>">
            <input type="hidden" name="request_uri" value="<?php echo $_SERVER['REQUEST_URI']; ?>">
            <input type="hidden" name="user_agent" value="<?php echo $_SERVER['HTTP_USER_AGENT']; ?>">
            <input type="hidden" name="remote_ip" value="<?php echo $_SERVER['REMOTE_ADDR']; ?>">
            <input type="hidden" name="email" value="<?php echo $current_user->email; ?>">         
            <br />
            <input name="email_display" type="text" value="<?php echo $current_user->email; ?>" disabled="disabled"/>
            <br />
<!-- Placeholder and dataset not supported on MSIE < 11: https://developer.mozilla.org/en-US/docs/Web/Guide/HTML/Using_data_attributes -->
            <input name="subject" type="text" value="Asunto" data-orig="Asunto" onblur="checkFormInputBlur(this);" onfocus="checkFormInputFocus(this);" tabindex="1" />
            <br />
            <textarea name="comments" type="text" cols="30" rows="9" tabindex="2" value="Comentarios" data-orig="Comentarios" onfocus="checkFormInputFocus(this);" onblur="checkFormInputBlur(this);">Comentarios</textarea>
            <br />
            <input type="submit" value="Enviar" tabindex="4">
	    </form>
        <div id="result" style="display:none;"></div>
        <div id="div_close">
            <a href="javascript:void(0);" onclick="popup('feedbackForm');"><img src="<?php echo $base_url; ?>/images/close-icon_16.png" alt="Cerrar" title="Cerrar"/></a>
        </div>
    </div>
<?php
    } else {
?>
        <!-- float divs must appear before the center section -->
        <div id="anuncio">
            Encuentra <a href="http://www.fortsu.com">el <strong>mejor precio</strong> para tus zapatillas</a> comparando tiendas online
        </div>  
    </div>
<?php
    }
?>
