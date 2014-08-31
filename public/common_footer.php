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
    <div class="common-footer">
        © FortSu 2013 | 
        <a href="<?php echo $rel_path; ?>index.php" rel="nofollow" title="Gestionar entrenamientos">Entrenamientos</a> | 
        <a href="<?php echo $rel_path; ?>contacto.php" rel="nofollow" title="Formulario de contacto">Contacto</a> | 
        <a href="<?php echo $rel_path; ?>aviso_legal.php" rel="nofollow" title="Aviso legal">Aviso legal</a> | 
        <a href="https://github.com/fortsu/entrenos" title="Para contribuir o saber cómo funciona: fork me on GitHub!">Código fuente</a>
    </div>
