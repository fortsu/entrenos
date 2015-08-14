<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/global_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/database.php';
use Entrenos\Activity;
use Entrenos\User;

/**
* Pretty URLs BASE_URL/actividad/<activity_id> mapped here via web server rewrite
* Direct (via query string parameter) access enabled as well
**/
if (!isset($_REQUEST['activity_id'])) {
    $log->error("No activity provided. Redirecting to home page");
    header("Location: " . $base_url);
    exit();
} else {
    $current_act = new Activity(array('id' => $_REQUEST['activity_id']));
    if (!$current_act->getActivity($conn)) {
        $log->error("Activity " . $current_act->id . " does not exists in DB. Redirecting to home page");
        header("Location: " . $base_url . "/calendar.php");
        exit();
    }
}

/**
* Access check: logged users with id > 0; guests with id 0
* Activity belongs to current user -> go ahead!
* Activity does not belong to current user: display only if it is public
**/
require_once $base_path . "/check_access.php";
if ($current_act->user_id !== $_SESSION['user_id']) {
    if ($current_act->visibility > 0) { //not private
        $current_user = new User(array('id'=> $_SESSION['user_id']));
        $current_user->username = "invitado";
    } else {
        // TODO: display error message regarding lack of privileges
        //Redirected guests to start page
        $log->debug($_SERVER['REMOTE_ADDR'] . " | " . $_SERVER['HTTP_USER_AGENT'] . " | Guests trying to access activity page");
       	header("Location: " . $base_url);
        exit();
    }
}

    // Setting Google Maps as map option for guests. OSM sometimes too slow, may provide bad user experience
    $maps_choice = "gmaps";
    if (!empty($_SESSION['maps'])) {
        $maps_choice = $_SESSION['maps'];
    }

    $has_gpx = intval(file_exists($current_act->path)); // false was stored as empty string
    $log->info($current_user->id . " | Rendering activity " . $current_act->id . " | GPX: " . $has_gpx . " | Visible: " . $current_act->visibility . " | Maps: " . $maps_choice);

    $header_title = $current_act->title;
    if (empty($header_title)) {
        $header_title = "Actividad de " . $current_act->start_time;
    }
    $current_url = $base_url . "/activity.php?activity_id=" . $current_act->id;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex,nofollow,noarchive" />
    <link rel="shortcut icon" href="/images/favicon.ico">
    <link rel="canonical" href="<?php echo $current_url; ?>"/>
    <title><?php echo $header_title; ?> - FortSu</title>
    <meta name="description" content="Gestión de actividades deportivas online" />
    <meta name="keywords" content="" />
    <meta name="author" content="FortSu.com" />
    <meta property="og:title" content="<?php echo $header_title; ?> - FortSu" />
    <meta property="og:description" content=" " />
    <meta property="og:url" content="<?php echo $current_url; ?>" />
    <meta property="og:image" content="/images/logo_146x52.png" />
    <link rel="stylesheet" href="/estilo/entrenos.min.css?<?php echo $fv ?>" type="text/css"/>
    <script async type="text/javascript" src="/js/jquery/jquery.min.js"></script>
    <script async type="text/javascript" src="/js/entrenos.min.js?<?php echo $fv ?>"></script>
<?php
    if ($maps_choice === "gmaps") {
?>
    <link rel="stylesheet" href="https://developers.google.com/maps/documentation/javascript/examples/standard.css" type="text/css" />
    <script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
    <script type="text/javascript" src="http://www.google.com/jsapi"></script>
<?php
    } else {
?>
    <link rel="stylesheet" href="/osm/current/theme/default/style.css" type="text/css"/>
    <script type="text/javascript" src="/osm/current/OpenLayers.js"></script>
    <script type="text/javascript" src="/osm/current/lib/deprecated.js"></script>
    <style>
        /*  Altering the location of the attribution text and scale line, see http://wiki.openstreetmap.org/wiki/OpenLayers_Simple_Example */
        div.olControlAttribution, div.olControlScaleLine {
          font-family: Verdana;
          font-size: 0.7em;
          bottom: 3px;
      }
    </style>
<?php
    }
?>
</head>
<?php

    echo "<body onload='loadWorkout(\"" . $current_act->id . "\", \"" . $current_act->user_id . "\",\"track_info\"," . $has_gpx . ", \"" . $maps_choice . "\");return false;'>\r\n";
    echo "<div id=\"blanket\" style=\"display:none;\"></div>\r\n";
    echo "<div id=\"main\" class=\"container\">\r\n";
    include $base_path . '/user_header.php';
    include $base_path . '/navigation_bar.php';
?>
        <div id="data_container">
            <div id="track_info"></div>
            <div id="map_canvas"></div>
            <div id="extra_data"></div>
            <br />
            <div id="elevation_chart" class="oculto"></div>
            <div id="laps_data"></div>
        </div>
    </div>
</body>
</html>
