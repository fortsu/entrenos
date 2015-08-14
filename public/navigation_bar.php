<?php
    $current_script = pathinfo($_SERVER['PHP_SELF'], PATHINFO_BASENAME);
?>
<div id="navegacion">
    <table id="nav">
        <thead>
            <tr>
<?php
    if ($current_user->id > 0) {
?>
                <th><a href="/calendar.php" <?php if($current_script === "calendar.php") echo "class=\"current\""; ?>>Calendario</a></th>
                <th><a href="/goals.php" <?php if($current_script === "goals.php") echo "class=\"current\""; ?>>Objetivos</a></th>
                <th><a href="/tags.php" <?php if($current_script === "tags.php") echo "class=\"current\""; ?>>Etiquetas</a></th>
                <!--<th><a href="plans.php" <?php if($current_script === "plans.php") echo "class=\"current\""; ?>>Planes</a></th>-->
                <th><a href="/charts.php" <?php if($current_script === "charts.php") echo "class=\"current\""; ?>>Estadísticas</a></th>
                <th><a href="/equipment.php" <?php if($current_script === "equipment.php") echo "class=\"current\""; ?>>Equipamiento</a></th>
                <th><a href="/search.php" <?php if($current_script === "search.php") echo "class=\"current\""; ?>>Búsqueda</a></th>
                <th><a href="/user_settings.php" <?php if($current_script === "user_settings.php") echo "class=\"current\""; ?>>Ajustes</a></th>
                <?php
                    if($current_user->email == "dgranda@gmail.com") {
                        echo "<th><a href=\"/admin/\">Admin</a></th>";
                    }
    } else {
?>
                <th><a >Calendario</a></th>
                <th><a >Objetivos</a></th>
                <th><a >Etiquetas</a></th>
                <th><a >Estadísticas</a></th>
                <th><a >Equipamiento</a></th>
                <th><a >Búsqueda</a></th>
                <th><a >Ajustes</a></th>
<?php
    }
?>
            </tr>
        </thead>
    </table>
</div>
