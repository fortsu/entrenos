<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/global_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/database.php';
require_once $base_path . '/check_access.php';

use Entrenos\Athlete;
use Entrenos\Activity;
use Entrenos\Equipment;
use Entrenos\Goal;
use Entrenos\Sport;
use Entrenos\Tag;
use Entrenos\User;
use Entrenos\Utils\Utils;
use Entrenos\Utils\Search;
 
/***************************************
- Approach for search:
1.- Check if there is any activity stored in DB for current user (getSports)
2.- Build search parameters (request or default values per sport)
3.- Retrieve search results from search config provided
4.- Display (via display_search_results.php) inside div "search_results"

- What happens when search is updated by user (js triggered):
1.- searchFilter.php builds new search parameters
2.- search results (via display_search_results.php) are updated inside div "search_results"
***************************************/
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Búsqueda - FortSu</title>
	<link rel="shortcut icon" href="images/favicon.ico">
    <meta name="description" content="Gestión de actividades deportivas online" />
    <meta name="keywords" content="deporte,correr,ciclismo,atletismo,maratón,carrera,pulsómetro,gps,gpx,polar,garmin,suunto,zapatillas,bicicleta,ropa deportiva" />
    <meta name="author" content="FortSu.com" />
    <meta charset="UTF-8" />
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <link rel="stylesheet" href="estilo/entrenos.min.css?<?php echo $fv ?>" type="text/css"/>
    <script type="text/javascript" src="js/entrenos.min.js?<?php echo $fv ?>"></script>

    <link rel="stylesheet" href="js/jqueryui/current/themes/base/jquery-ui.min.css" type="text/css"/>
    <script type="text/javascript" src="js/jquery/jquery.min.js"></script>
    <!-- To support transitional upgrade to jquery above 1.9 -->
    <script src="js/jquery/jquery-migrate-1.2.1.min.js"></script>
    <script type="text/javascript" src="js/jqueryui/current/js/jquery-ui.min.js"></script>
    <script type="text/javascript" src="js/jqueryui/jquery.ui.datepicker-es.js"></script>

    <link rel="stylesheet" href="estilo/jslider/jquery.slider.min.css" type="text/css"/>
    <script type="text/javascript" src="js/jquery.slider.min.js"></script>

    <style type="text/css" media="screen">
	 .layout { padding: 50px; font-family: Georgia, serif; }
	 .layout-slider { margin: 10px 15px 25px 15px; width: 65%; display: inline-block; vertical-align: middle; }
	 .layout-slider-settings { font-size: 12px; padding-bottom: 10px; }
	 .layout-slider-settings pre { font-family: Courier; }
	</style>

    <script>
        $(document).ready(function() {
            $.datepicker.setDefaults( $.datepicker.regional[ "es" ] );
		    $( "#datepicker" ).datepicker({
			    dateFormat: "yy-mm-dd",
			    firstDay: 1,
                changeMonth: true,
			    changeYear: true
		    });
            $( "#range_from" ).datepicker({
                maxDate: "-1D",
                changeMonth: true,
                numberOfMonths: 2,
                onClose: function( selectedDate ) {
                    $( "#range_to" ).datepicker( "option", "minDate", selectedDate );
                }
            });
            $( "#range_to" ).datepicker({
                maxDate: "+0D",
                changeMonth: true,
                numberOfMonths: 2,
                onClose: function( selectedDate ) {
                    $( "#range_from" ).datepicker( "option", "maxDate", selectedDate );
                }
            });
            
        });
    </script>
</head>
<body>
<div id="blanket" style="display:none;"></div>
<div id="main">
<?php
    include $base_path . '/user_header.php';
    include $base_path . '/navigation_bar.php';
    
    // Default values for distance and pace depends on sport_id -> retrieving sports available for current user
    $user_sports = $current_user->getSports($conn);
    $log->debug($current_user->id . "|Sports available in DB: " . implode("|",$user_sports));

    // If there is at least one sport available in database means that current user has records stored there
    if (!empty($user_sports)) {

        $current_search = new Search(array("user_id" => $current_user->id));
        // inspecting request and looking for proper default values
        if (isset($_REQUEST['sport_id']) AND in_array($_REQUEST['sport_id'], $user_sports)) {
            $current_search->sport_id = $_REQUEST['sport_id'];
        } else {
            // Default sport is the one available from user whose id is the lowest
            $current_search->sport_id = $user_sports[0];
        }
        
        // Distance figures:
        // Database -> mm | Display -> km (running and cycling). Walking?, swimming?
        $distances = Activity::getDistances($conn, $current_user->id, $current_search->sport_id);
        $log->debug($current_user->id . "|Distances (mm): " . json_encode($distances));
        // ToDo: link scale_step to max and min distance difference and scale
        $scale_step = 5;
        // displaying nearest multiple of scale_step -> min to lower, max to higher
        $min_km = floor($distances['min']/(1000000*$scale_step))*$scale_step;
        $max_km = ceil($distances['max']/(1000000*$scale_step))*$scale_step;
        // Just one result or too close min and max
        if ($max_km - $min_km < $scale_step) {
            $mid_km = ($min_km + $max_km)/2;
            $min_km = $mid_km - $mid_km/2;
            $max_km = $mid_km + $mid_km/2;
        }
        $log->info($current_user->id . "|Min km: " . $min_km . " | max km: " . $max_km);
        // Arrows in distance slider
        $diff_km = $max_km - $min_km;
        $current_search->max_dist = ($min_km + $diff_km*2/3)*1000000;
        $current_search->min_dist = ($min_km + $diff_km/3)*1000000;

        // Pace calculations
        $pace_db = Activity::getPaces($conn, $current_user->id, $current_search->sport_id);
        // Translating pace into seconds to make calculations easier
        $pace_seconds['max'] = Utils::dbpace2seconds($pace_db['max']);
        $pace_seconds['min'] = Utils::dbpace2seconds($pace_db['min']);
        $log->debug($current_user->id . "|Paces db: " . json_encode($pace_db) . " | seconds: " . json_encode($pace_seconds));

        $step_pace = 15;
        // Just one result or too close min and max
        if ($pace_seconds['max'] - $pace_seconds['min'] < $step_pace) {
            $mid_pace_seconds = ($pace_seconds['max'] + $pace_seconds['min'])/2;
            $pace_seconds['min'] = $mid_pace_seconds - $mid_pace_seconds/2;
            $pace_seconds['max'] = $mid_pace_seconds + $mid_pace_seconds/2;
            $log->debug($current_user->id . "|Recalculated paces in seconds: " . json_encode($pace_seconds));
        }

        // displaying nearest multiple of 15 (step_pace): min -> lower, max -> upper
        $max_displayed = ceil($pace_seconds['max']/$step_pace)*$step_pace;
        $min_displayed = floor($pace_seconds['min']/$step_pace)*$step_pace;
        $diff_displayed = $max_displayed - $min_displayed;
        $log->debug($current_user->id . "|Max displayed: " . $max_displayed . " | min displayed: " . $min_displayed . " | diff: " . $diff_displayed);
        // Arrows in pace slider
        $min_pace_seconds = floor($min_displayed + $diff_displayed/3);
        $max_pace_seconds = ceil($min_displayed + $diff_displayed*2/3);
        $current_search->min_pace = Utils::seconds2dbpace($min_pace_seconds);
        $current_search->max_pace = Utils::seconds2dbpace($max_pace_seconds);
        $log->debug($current_user->id . "|Pace slider values. Max: " . $max_pace_seconds . " (" . $current_search->max_pace . ") | Min: " . $min_pace_seconds . " (" . $current_search->min_pace . ")");

        // Speed slider
        $speed_displayed["min"] = floor(60/Utils::seconds2dbpace($max_displayed));
        $speed_displayed["max"] = ceil(60/Utils::seconds2dbpace($min_displayed));
        $speed_displayed["min_arrow"] = floor(60/$current_search->max_pace);
        $speed_displayed["max_arrow"] = ceil(60/$current_search->min_pace);
        $speed_displayed["range"] = $speed_displayed["max"] - $speed_displayed["min"];
        $log->debug($current_user->id . "|Speed slider values: " . json_encode($speed_displayed));

        // Order search settings
        $current_search->order = "DESC";
        $current_search->order_by = "start_time";
        
        echo "<div id=\"search_container\">";
        echo "<div id=\"search_config\">";
            echo "<form name=\"myform\" action=\"searchFilter.php\" method=\"post\">";
                echo "<div id=\"search_columns\">";            
                // Tags
                echo "<div id=\"search_tags\" class=\"column_search\">";
                    echo "<span style=\"font-weight:bold\">Etiquetas:</span>";
                    echo "<br />\r\n";
                    try {
                        $user_tags = $current_user->getTags($conn);
                    } catch (Exception $e) {
                        echo "Error al recuperar las etiquetas del usuario";
                        $log->error($e->getMessage());
                    }
                    if (count($user_tags) > 0) {
                        foreach ($user_tags as $key => $tag) {
                            echo "<input type=\"checkbox\" name=\"search_filter\" value='tag_" . $tag->id . "' checked " . 
                                " onclick='updateSearch(\"". $current_user->id . "\", document.myform.search_filter)'> " . $tag->name;
                            echo "<br />\r\n";
                        }
                        echo "<input type=\"checkbox\" name=\"search_filter\" value='tag_notag' checked " . 
                                " onclick='updateSearch(\"". $current_user->id . "\", document.myform.search_filter)'> Sin etiqueta especificada";
                        echo "<br />\r\n";
                    } else {
                        echo "No se han encontrado etiquetas";
                        echo "<br />\r\n";
                    }
                echo "</div>";

                // Goals
                echo "<div id=\"search_goals\" class=\"column_search\">";
                echo "<span style=\"font-weight:bold\">Objetivos:</span>";
                echo "<br />\r\n";
                try {
                    $user_goals = $current_user->getGoals(FALSE,$conn);
                } catch (Exception $e) {
                    echo "Error al recuperar los objetivos del usuario";
                    $log->error($e->getMessage());
                }      
                if (count($user_goals) > 0) {
                    foreach ($user_goals as $key => $goal) {
                        echo "<input type=\"checkbox\" name=\"search_filter\" value='goal_" . $goal->id . "' checked " . 
                            " onclick='updateSearch(\"". $current_user->id . "\", document.myform.search_filter)'> " . $goal->name;
                        echo "<br />\r\n";
                    }
                    echo "<input type=\"checkbox\" name=\"search_filter\" value='goal_nogoal' checked " . 
                            " onclick='updateSearch(\"". $current_user->id . "\", document.myform.search_filter)'> Sin objetivo especificado";
                    echo "<br />\r\n";
                } else {
                    echo "No se han encontrado objetivos";
                    echo "<br />\r\n";
                }
                echo "</div>";

                // Equipment
                echo "<div id=\"search_equip\" class=\"column_search\">";
                echo "<span style=\"font-weight:bold\">Material:</span>";
                echo "<br />\r\n"; 
                $user_equip = Equipment::getUserEquipment($current_search->user_id, FALSE, $conn); //looking for all, active and non active ones
                if (count($user_equip) > 0) {
                    foreach ($user_equip as $key => $equip) {   //$equip is an array not an object
                        echo "<input type=\"checkbox\" name=\"search_filter\" value='equip_" . $equip['id'] . "' checked " .
                            " onclick='updateSearch(\"". $current_user->id . "\", document.myform.search_filter)'> " . $equip['name'];
                        echo "<br />\r\n";
                    }
                    echo "<input type=\"checkbox\" name=\"search_filter\" value='equip_noequip' checked " .
                        " onclick='updateSearch(\"". $current_user->id . "\", document.myform.search_filter)'> Sin equipamiento especificado";
                    echo "<br />\r\n";
                } else {
                    echo "No se ha encontrado material";
                    echo "<br />\r\n";
                }
                echo "</div>";
                echo "<div style=\"clear:both\"></div>";
            echo "</div>";
?>
            <div style="position:relative;padding:15px;"> <!-- sports and sliders container -->
                <div style="float:left;margin:10px;"> <!-- sports -->
                    <span style="font-weight:bold"> Deporte: </span> 
                    <select id="sport_select" name="search_filter" onchange='window.location.href="?sport_id="+this.value'>
<?php
            foreach ($user_sports as $loop_sport_id){
                $selected_txt = "selected=\"selected\"";
                if ($loop_sport_id != $current_search->sport_id) {
                    $selected_txt = "";
                }
                echo "<option value=\"" . $loop_sport_id . "\" " . $selected_txt . "> " . Sport::$display_es[$loop_sport_id] . " </option>";
            }
?>
                    </select>
                </div>
                <!-- Slider's credits: http://egorkhmelev.github.com/jslider/ -->
                <div style="float:left;width:70%;margin-left:15px"> <!-- sliders container -->
                    <div id="slider_dist_container">
                        <div>
                            <span style="font-weight:bold">Distancia:</span> 
                            <div class="layout-slider">
                                <input id="Slider_dist" type="slider" name="search_filter" value="<?php echo $current_search->min_dist/1000000; ?>;<?php echo $current_search->max_dist/1000000; ?>" />
                            </div>
                        </div>
                        <script type="text/javascript" charset="utf-8">
                            jQuery("#Slider_dist").slider({ 
                                from: <?php echo $min_km; ?>,
                                to: <?php echo $max_km; ?>,
                                scale: ['<?php echo $min_km; ?>','<?php echo ($min_km + $diff_km/4); ?>','<?php echo ($min_km + $diff_km/2); ?>','<?php echo ($min_km + $diff_km*3/4); ?>','<?php echo $max_km; ?>'], 
                                limits: false, 
                                step: 0.5, 
                                round: 1, 
                                dimension: '&nbsp;km',
                                skin: "blue",
                                callback: function( value ){ updateSearch("<?php echo $current_user->id; ?>", document.myform.search_filter); }
                            });
                        </script>
                    </div>
<?php
        if ($current_search->sport_id == 0) {
?>
                    <div id="slider_pace_container">
                        <div>
                            <span style="font-weight:bold">Ritmo:</span> 
                            <div class="layout-slider">
                                <input id="Slider_pace" type="slider" name="search_filter" value="<?php echo $min_pace_seconds; ?>;<?php echo $max_pace_seconds; ?>" />
                            </div>
                        </div>
                        <script type="text/javascript" charset="utf-8">
                            // Values (from, to, arrows) in seconds, scale in proper figures por pace
                            jQuery("#Slider_pace").slider({ 
                                from: <?php echo $min_displayed; ?>, 
                                to: <?php echo $max_displayed; ?>, 
                                scale: ['<?php echo Utils::seconds2pace($min_displayed); ?>','<?php echo Utils::seconds2pace($min_displayed + $diff_displayed/4); ?>','<?php echo Utils::seconds2pace($min_displayed + $diff_displayed/2); ?>','<?php echo Utils::seconds2pace($min_displayed + $diff_displayed*3/4); ?>','<?php echo Utils::seconds2pace($max_displayed); ?>'],
                                limits: false, 
                                step: 5,
                                dimension: '&nbspmin/km',
                                skin: "blue",
                                callback: function( value ){ updateSearch("<?php echo $current_user->id; ?>", document.myform.search_filter); },
                                calculate: function( value ){
                                    var mins = Math.floor( value / 60 );
                                    var seconds = ( value - mins*60 );
                                    // Prepare displaying (zero padding)
                                    function zeroPad (value2) {
                                        if (value2 < 10) {
                                            value2 = "0" + value2; // this will fail in other languages as value2 is a Number
                                        }
                                        return value2;
                                    }
                                    return zeroPad(mins) + ":" + zeroPad(seconds);
                                }, 
                            });
                        </script>
                    </div>
<?php
        } else {
?>
                    <div id="slider_speed_container">
                        <div>
                            <span style="font-weight:bold">Velocidad:</span> 
                            <div class="layout-slider">
                                <input id="Slider_speed" type="slider" name="search_filter" value="<?php echo $speed_displayed["min_arrow"]; ?>;<?php echo $speed_displayed["max_arrow"]; ?>" />
                            </div>
                        </div>
                        <script type="text/javascript" charset="utf-8">
                            // Values (from, to, arrows) in seconds, scale in proper figures por pace
                            jQuery("#Slider_speed").slider({ 
                                from: <?php echo $speed_displayed["min"]; ?>, 
                                to: <?php echo $speed_displayed["max"]; ?>, 
                                scale: ['<?php echo $speed_displayed["min"]; ?>','<?php echo $speed_displayed["min"]+$speed_displayed["range"]/4; ?>','<?php echo $speed_displayed["min"]+$speed_displayed["range"]/2; ?>','<?php echo $speed_displayed["min"]+$speed_displayed["range"]*3/4; ?>','<?php echo $speed_displayed["max"]; ?>'],
                                limits: false, 
                                step: 1,
                                dimension: '&nbsp;km/h',
                                skin: "blue", 
                                callback: function( value ){ updateSearch("<?php echo $current_user->id; ?>", document.myform.search_filter); }
                            });
                        </script>
                    </div>
<?php
        }
?>
                </div> <!-- sliders container -->
                <div style="clear:both;"></div>
            </div> <!-- sports and sliders container -->
    
            <div id="search_date">
                <div id="search_date_dropdown" class="column_date_search">
                    <span style="font-weight:bold"> Fecha: </span>
                    <select name="search_filter" onchange="updateSearch('<?php echo $current_user->id; ?>', document.myform.search_filter)">
                        <option value="any" selected="selected"> Cualquier fecha </option>
                        <option value="last_year"> Último año </option>
                        <option value="last_month"> Último mes </option>
                        <option value="last_week"> Última semana </option>
                        <option value="custom_day" disabled> Día concreto... </option>
                        <option value="custom_range" disabled> Intervalo personalizado... </option>
                    </select>
                </div>
                <span class="new"> ¡nuevo!</span>
                <div id="search_date_selection" class="column_date_search" style="display: none">
                    <input id="datepicker" name="search_selected_day" type="text">
                </div>
                <div id="search_date_selection_range" class="column_date_search" style="display: none">
                    <label for="range_from">Desde</label>
                    <input type="text" id="range_from" name="range_from" />
                    <label for="range_to"> a </label>";
                    <input type="text" id="range_to" name="range_to" />
                </div>
            </div> <!-- search_date -->

            </form>
        </div> <!-- search_config -->
            
        <div id="search_results">
<?php 
            // Default search does not include data like tags, goals or equipment
            try {
                $workouts = $current_search->filter($conn);
                if (!isset($current_search->step)) {
                    $current_search->step = 0;
                    $current_search->num_display = 15;
                    $current_search->total_results = count($workouts);
                }
                $log->info("Retrieving " . count($workouts) . " activities for user " . $current_search->user_id . " | Details: " . json_encode($current_search));
            } catch (Exception $e) {
                echo "Error al buscar actividades para el usuario " . $current_search->username . " | Error: " . $e->getMessage();
                $log->error($e->getMessage());
            }
            if ($current_search->total_results > 0) {
                include "./display_search_results.php";
            } else {
                echo "No se han encontrado actividades. Revise los filtros de búsqueda";
            }
?>
        </div>
<?php
    } else { 
        echo "No se han encontrado actividades para el usuario " . $current_user->username . ". Nada que buscar";
        $log->info($current_user->id . "|No records found in database, nothing to search");
    }
?>
    </div> <!-- search_container -->
</div> <!-- main -->
</body>
</html>
