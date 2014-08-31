<?php
    $current_script = pathinfo($_SERVER['PHP_SELF'], PATHINFO_BASENAME);
    $dirname_parent = dirname($_SERVER['SCRIPT_FILENAME']);
    $dirname_included = dirname(__FILE__);
    //echo "Path included: " . $dirname_included . " | parent: " . $dirname_parent;
    $rel_path = "./";
    if ($dirname_parent != $dirname_included){
        $rel_path = "../";
    } 
?>
    <div id="home_footer">
	    <div id="caja_izda" class="coment"> 
		    © FortSu 2013
        </div> 
	    <div id="caja_dcha" class="coment">
<?php 
        if($current_script !== "comparador.php") { 
?>
		    <a href="http://www.fortsu.com" title="El mejor precio para tus zapatillas de correr">Busca material</a> | 
<?php 
        } else { 
?>
            <a href="http://entrenos.fortsu.com" title="Gestionar entrenamientos">Entrenamientos</a> | 
<?php
        }
 
        if($current_script !== "contacto.php") { 
?>
		    <a href="<?php echo $rel_path; ?>contacto.php" title="Formulario de contacto">Contacto</a> | 
<?php 
        } else { 
?>
            <a href="http://entrenos.fortsu.com" title="Gestionar entrenamientos">Entrenamientos</a> | 
<?php
        }

        if($current_script !== "aviso_legal.php") { 
?>
		    <a href="<?php echo $rel_path; ?>aviso_legal.php" title="Aviso legal">Aviso legal</a> | 
<?php 
        } else { 
?>
            <a href="<?php echo $rel_path; ?>index.php" title="Gestionar entrenamientos">Entrenamientos</a> |
<?php
        }
?>
            <a href="https://github.com/fortsu/entrenos" title="Para contribuir o saber cómo funciona: fork me on GitHub!">Código fuente</a>
	    </div>
    </div>
