<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/global_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/database.php';
require_once $base_path . '/check_access.php';

$daily_report_url = $base_url . "/users/" . $current_user->id . "/reports/daily_report.php";

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Configuración para <?php echo $current_user->username; ?> - FortSu</title>
    <meta name="robots" content="noindex,nofollow,noarchive" />
	<link rel="shortcut icon" href="images/favicon.ico">
    <meta name="description" content="Gestión de actividades deportivas online" />
    <meta name="keywords" content="deporte,correr,ciclismo,atletismo,maratón,carrera,pulsómetro,gps,gpx,polar,garmin,suunto,zapatillas,bicicleta,ropa deportiva" />
    <meta name="author" content="FortSu.com" />
    <meta charset="UTF-8" />
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <link rel="stylesheet" href="estilo/entrenos.min.css?<?php echo $fv ?>" type="text/css"/>
    <link rel="stylesheet" href="js/jqueryui/current/themes/base/jquery-ui.min.css" type="text/css"/>
    <script type="text/javascript" src="js/jquery/jquery.min.js"></script>
    <script type="text/javascript" src="js/jqueryui/current/js/jquery-ui.min.js"></script>
    <script type="text/javascript" src="js/entrenos.min.js?<?php echo $fv ?>"></script>
</head>
<body>
<div id="blanket" style="display:none;"></div>
    <div id="main">
    <?php
        include 'user_header.php';
        include 'navigation_bar.php';

        if (isset($_SESSION['errors'])) {
            $error_txt = $_SESSION['errors'];
            echo "<div id=\"error_parsing\" >";
            echo $error_txt;
            echo "<a href=\"" . basename(__FILE__) . "\" onclick='hideLayer(\"error_parsing\");return false'> [cerrar] </a>";
            echo "</div>";
            unset($_SESSION['errors']);
        }
    ?>
        <br />
        Página de configuración para <b><?php echo $current_user->username; ?></b> (<?php echo $current_user->email; ?>)<br />
        <ul>
        <li>
            <div class="settings_header">Visibilidad por defecto para las actividades</div>
            <div class="settings_header_sub">La configuración de cada actividad específica prevalece sobre la general.</div>
            <form>
                <input type="radio" name="default_visibility" value="public" disabled /> Públicas<br/>
                <input type="radio" name="default_visibility" value="friends" disabled /> Amigos <span class="settings_note">(la pueden ver los usuarios de FortSu que el propietario decida)</span><br />
                <input type="radio" name="default_visibility" value="private" checked="checked" /> Privadas <span class="settings_note">(solamente el propietario puede acceder a ellas)</span>
            </form>
        </li>
<?php
    // Maps
    $osm_checked = "";
    $gmaps_checked = "";
    ${$current_user->maps . "_checked"} = "checked";
    // CSRF
    $_SESSION["csrf"] = md5(uniqid(mt_rand(), true));
?>
        <li>
            <div class="settings_header">Preferencias de mapas</div>
            <div class="settings_header_sub">Tu <strong>privacidad</strong> en el punto de mira.</div>
            <div id="maps_form_result" class="oculto"></div>
            <form id="maps_form" name="maps_form" action="<?php echo $base_url; ?>/forms/formUser.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="select_map">
                <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">
                <input type="hidden" name="source_ip" value="<?php echo $_SERVER['REMOTE_ADDR']; ?>">
                <input type="hidden" name="user_agent" value="<?php echo $_SERVER['HTTP_USER_AGENT']; ?>">
                <input type="hidden" name="csrf" value="<?php echo $_SESSION['csrf']; ?>">
                <input type="hidden" name="maps_current" value="<?php echo $current_user->maps; ?>">
                <input type="radio" id ="maps_choice_osm" name="maps_choice" value="osm" <?php echo $osm_checked; ?> onclick="select_map(this);"/> <a href="http://openlayers.org/" title="Sitio web de OpenLayers (en inglés)">OpenLayers</a> con mapas <a href="http://www.openstreetmap.org" title="Sitio web de OpenStreetMap">OpenStreetMap</a>
                <br/>
                <input type="radio" id ="maps_choice_gmaps" name="maps_choice" value="gmaps" <?php echo $gmaps_checked; ?> onclick="select_map(this);"/> <a href="https://developers.google.com/maps/?hl=es" title="Sitio web de Google Maps">Google Maps</a>
            </form>
        </li>
        <li>
            <div class="settings_header">Exportar todas las actividades</div>
            <div class="settings_header_sub"><strong>Tus datos</strong> son <strong>tuyos</strong>; sí, <strong>tuyos</strong>. Incluye información contenida en base de datos en <a href="http://es.wikipedia.org/wiki/JSON">formato JSON</a> además del track (si existe).</div>
            <ul>
                <li>
                    <a href="forms/formExport.php?id=all&format=gpx">Formato GPX comprimidas en un archivo zip</a>
                </li>
                <li>
                    <a href="forms/formExport.php?id=all&format=tcx" onclick="alert('Exportar las actividades en formato TCX no está disponible todavía');return false;" >Formato TCX comprimidas en un archivo zip</a>
                </li>
            </ul>
        </li>
        <li>
            <div class="settings_header">Enlace al informe diario de actividad</div>
            <div class="settings_header_sub">Para foros, para tu blog, para lo que quieras.</div>
                <div style="margin:20px;">
                    <span id="link_report"><a href="<?php echo $daily_report_url; ?>" title="Última actividad registrada por <?php echo $current_user->username; ?>" target="_blank"><?php echo $daily_report_url; ?></a>
                    </span>
                </div>
        </li>
        </ul>
    </div>
</body>
</html>
