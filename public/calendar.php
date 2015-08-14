<?php
use Entrenos\User;
use Entrenos\Activity;
use Entrenos\Sport;
use Entrenos\Utils\Utils;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/global_config.php';
require_once $base_path . "/../config/database.php";
require $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';

require_once $base_path . "/check_access.php";

?>
<!DOCTYPE HTML>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="robots" content="noindex,nofollow,noarchive" />
    <title>Calendario de actividades - FortSu</title>
	<link rel="shortcut icon" href="images/favicon.ico">
    <meta name="description" content="Gestión de actividades deportivas online" />
    <meta name="keywords" content="deporte,correr,ciclismo,atletismo,maratón,carrera,pulsómetro,gps,gpx,polar,garmin,suunto,zapatillas,bicicleta,ropa deportiva" />
    <meta name="author" content="entrenos.fortsu.com" />

    <link rel="stylesheet" href="estilo/entrenos.min.css?<?php echo $fv ?>" type="text/css"/>
    <link rel="stylesheet" href="js/jqueryui/current/themes/base/jquery-ui.min.css" type="text/css"/>

    <script src="js/jquery/jquery.min.js"></script>
    <!-- To support transitional upgrade to jquery above 1.9 -->
    <script src="js/jquery/jquery-migrate-1.2.1.min.js"></script>
    <script src="js/jqueryui/current/js/jquery-ui.min.js"></script>
    <script src="js/jqueryui/jquery.ui.datepicker-es.js"></script>
    <script src="js/entrenos.min.js?<?php echo $fv ?>"></script>
    <!-- Think about combining js files: http://stackoverflow.com/questions/5511989/combine-multiple-javascript-files-into-one-js-file -->
    <script src="communicator-api/current/prototype/prototype.js" ></script>
    <script src="communicator-api/current/garmin/util/Util-Broadcaster.js" ></script>
    <script src="communicator-api/current/garmin/util/Util-BrowserDetect.js" ></script>
    <script src="communicator-api/current/garmin/util/Util-DateTimeFormat.js" ></script>
    <script src="communicator-api/current/garmin/util/Util-PluginDetect.js" ></script>
    <script src="communicator-api/current/garmin/util/Util-XmlConverter.js" ></script>
    <script src="communicator-api/current/garmin/device/GarminObjectGenerator.js" ></script>
    <script src="communicator-api/current/garmin/device/GarminPluginUtils.js" ></script>
    <script src="communicator-api/current/garmin/device/GarminDevice.js" ></script>
    <script src="communicator-api/current/garmin/device/GarminDevicePlugin.js" ></script>
    <script src="communicator-api/current/garmin/device/GarminDeviceControl.js" ></script>
    <script src="communicator-api/current/garmin/activity/TcxActivityFactory.js" ></script>
    <script src="communicator-api/current/garmin/activity/GarminMeasurement.js" ></script>
    <script src="communicator-api/current/garmin/activity/GarminSample.js" ></script>
    <script src="communicator-api/current/garmin/activity/GarminSeries.js" ></script>
    <script src="communicator-api/current/garmin/activity/GarminActivity.js" ></script>
    <script src="communicator-api/garminFortsu.min.js" ></script>

    <script type="text/javascript">
        //garmin connect uses prototype!! -> http://docs.jquery.com/Using_jQuery_with_Other_Libraries
        jQuery.noConflict();
        jQuery(document).ready(function() {
            // Display/hide days of the month
            // Override style (just for current page)
            jQuery("#datepicker-entry").click(function() {
                jQuery(".ui-datepicker-calendar").css('display', 'table');
            });
            jQuery.datepicker.setDefaults( jQuery.datepicker.regional[ "es" ] );
		    jQuery( "#datepicker-entry" ).datepicker({
			    dateFormat: "yy-mm-dd",
			    firstDay: 1,
                changeMonth: true,
			    changeYear: true,
                maxDate: "+0D"
		    });
            jQuery("#datepicker-calendar").datepicker({
                dateFormat: "MM 'de' yy",
                firstDay: 1,
			    changeMonth: true,
                changeYear: true,
                showButtonPanel: true,
                maxDate: "+0D",
                onClose: function(dateText) {
                    // Get selected date
                    var month = jQuery("#ui-datepicker-div .ui-datepicker-month :selected").val();
                    var year = jQuery("#ui-datepicker-div .ui-datepicker-year :selected").val();
                    var selectedDate = new Date(year, month, 1);
                    // Appropiate format for comparison
                    var selectedDateF = jQuery.datepicker.formatDate("MM 'de' yy", selectedDate);
                    //console.log("Current: " + dateText + " | Selected: " + selectedDateF);
                    // Only requests calendar change if different date has been selected
                    if (selectedDateF != dateText) {
                        // Set selected date
                        jQuery(this).datepicker('setDate', selectedDate);
                        // Adding 1 as jquery month's index start with 0
                        var month_display = parseInt(month, 10) + 1;
                        // Load calendar
                        showCal(year, month_display, "calendar");
                        // Dirty hacks for default date
                        jQuery(this).datepicker("option", "defaultDate", selectedDate);
                        jQuery("#datepicker-calendar").attr("value", selectedDateF)
                    }
                }
		    });
	    });
    </script>
    <style>
        .ui-datepicker-calendar {
            display: none;
        }
    </style>
</head>
<body>
<div id="blanket" style="display:none;"></div>
<div id="main" class="container">
<?php
    include 'user_header.php';
    include 'navigation_bar.php';
    if (isset($_SESSION['errors'])) {
        $errors = $_SESSION['errors'];
        echo "<div id=\"error_parsing\" >";
        foreach ($errors as $error_txt) {
            echo $error_txt;
            echo "<br />";
        }
        echo "<a href=\"" . basename(__FILE__) . "\" onclick='hideLayer(\"error_parsing\");return false'> [cerrar] </a>";
        echo "</div>";
        unset($_SESSION['errors']);
    }
    if (isset($_SESSION['msg'])) {
        $msg_txt = $_SESSION['msg'];
        echo "<div id=\"msg_parsing\" >";
            echo $msg_txt;
            echo "<a href=\"" . basename(__FILE__) . "\" onclick='hideLayer(\"msg_parsing\");return false'> [cerrar] </a>";
        echo "</div>";
        unset($_SESSION['msg']);
    }

    $workouts = $current_user->getAllActivities($conn); // ToDo: try/catch blocks!
    $workouts_cal = array(); // id => ( date_act => 2011-01-19, time_act => 23:40:51)
    foreach ($workouts as $key => $activity) {
        $dateAndTime = Utils::getDateAndTimeFromDateTime ($activity->start_time);
        $workouts_cal[$key] = array('date_act' => $dateAndTime['date'], 'time_act' => $dateAndTime['time']);
	}

    // Displaying calendar
    // default parameters
    date_default_timezone_set('Europe/Berlin');
    $cal_year = date("Y");
    $cal_month = date("n");
    $meses = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");
    echo "<div id=\"calendar-wrapper\" style=\"width:576px\">";
        echo "<div style=\"margin-top:10px;text-align:center;\">";
            echo "<input type=\"text\" id=\"datepicker-calendar\" value='" . $meses[$cal_month-1] . " de " . $cal_year . "'/>";
        echo "</div>";
        echo "<div id=\"calendar\">";
            include 'info_calendar.php'; //when page is loaded it shows current month, changes via AJAX
        echo "</div>";
    echo "</div>";

    echo "<div id=\"most_recent\">";
        echo "<div style=\"color:gray;\">Últimas actividades registradas:</div>";
        if ($workouts) {
            $workouts_mrf = array_reverse($workouts); // most recent first
            echo "<table class=\"simple\">";
                echo "<thead>";
                    echo "<tr>";
                        echo "<th>Comienzo</th>";
                        echo "<th>Distancia</th>";
                        echo "<th>Duración</th>";
                        echo "<th>Ritmo</th>";
                        echo "<th>FCmed</th>";
                    echo "</tr>";
                echo "</thead>";
                echo "<tbody>";
                $num_display = 5;
                $num_act = count($workouts_mrf);
                if ($num_act < $num_display) {
                    $num_display = $num_act;
                }
                $log->info($current_user->id . "|Displaying " . $num_display . " activities out of " . $num_act);
                $workouts_mrf_tmp = array_slice($workouts_mrf, 0, $num_display);
                foreach ($workouts_mrf_tmp as $index => $element) {
                    echo "<tr>";
                        $dateAndTime = Utils::getDateAndTimeFromDateTime ($element->start_time);
                        echo "<td>\r\n";
                        echo "<div class=\"calendar_data\">\r\n";
                        echo "<a href=\"activity.php?activity_id=" . $element->id . "\" title=\"ver detalles\">" . $dateAndTime['date'] . " " . $dateAndTime['time'] . "</a>";
                        echo "</div>\r\n";
                        echo "</td>\r\n";
                        echo "<td>" . sprintf("%01.3f",round($element->distance/1000)/1000) . "</td>";
                        echo "<td>" . Utils::formatMs($element->duration) . "</td>";
                        echo "<td>" . Utils::formatPace($element->pace) . "</td>";
                        echo "<td>" . round($element->beats) . "</td>";
                    echo "</tr>";
                }
                echo "</tbody>";
            echo "</table>";
        } else {
            echo "<br />\r\n";
            echo "No se ha encontrado ninguna actividad de " . $current_user->username;
	    }
?>
        <div id="new_activity">
            <a href="javascript:void(0)" onclick="displayDiv('arrow_new','new_options');return false;"><img id="arrow_new" src="images/right_arrow.png"><span style="vertical-align:top;"> Nueva actividad</span></a>
            <div id="new_options" style="display: none;">
                <div id="new_gc"><a href="javascript:void(0)" onclick="loadGC('<?php echo $_SERVER['SERVER_NAME']; ?>')">Garmin plugin</a></div>
                <div id="new_upload"><a href="javascript:void(0)" onclick="popup('uploadfile')">Subir fichero</a></div>
                <div id="new_manual"><a href="javascript:void(0)" onclick="popup('newworkout')">Entrada manual</a></div>
            </div>
        </div>
    </div>

    <!-- ToDo: check http://valums.com/ajax-upload/ , normal way using javascript doesn't work with files -->
    <div id="uploadfile" style="display:none;">
         <div id="div_close">
            <a href="javascript:void(0)" onclick="popup('uploadfile');"><img src="images/close-icon_16.png" alt="Cerrar" title="Cerrar"/></a>
        </div>
        <form id="upload_form" action="forms/formWorkout.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload">
        <br />
            Nueva actividad en formato TCX, GPX, GPX+ o FIT:
            <br />
            <input name="userfiles[]" type="file" multiple="true" style="margin: 10px 0px;" onchange="handleFiles(this.files,'selected_files');">
            <br />
            <!-- Try to get 5 item columns -->
            <div id="selected_files"></div>
            <input type="submit" value="Enviar">
	    </form>
        <span class="nota" style="position: absolute; bottom: 15px;"><b>Nota:</b> seleccione varios ficheros manteniendo pulsada la tecla 'Ctrl'</span>
    </div>

    <div id='garminconnect' style="display:none;">
        <div id="div_close">
            <a href="javascript:void(0)" onclick="popup('garminconnect')"><img src="images/close-icon_16.png" alt="Cerrar" title="Cerrar"/></a>
        </div>
        <div id="msg"></div>
        <div id="devices"></div>
        <div id="progress"></div>
        <div id="data"></div>
        <div id="import_progress"></div>
    </div>

    <div id='newworkout' style="display:none;">
        <div id="div_close">
            <a href="javascript:void(0)" onclick="popup('newworkout')"><img src="images/close-icon_16.png" alt="Cerrar" title="Cerrar"/></a>
        </div>
        <form action="forms/formWorkout.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="manual">

            <input id="title" name="title" type="text" value="Título" class="modern_med" onblur="if(this.value==''){this.value='Título'; this.style.opacity='0.5';}else{this.style.opacity='1';}" onfocus="if(this.value=='Título')this.value='';this.style.opacity='1';" tabindex="1"/>

            <span class="modern_med"> Deporte: </span> <select id="sport_id" name="sport_id" tabindex="2">
<?php
            foreach (Sport::$display_es as $key => $value){
                echo "<option value=\"" . $key . "\"> " . $value . " </option>";
            }
?>
            </select>

            <br />
            <input id="distance" name="distance" type="text" value="Distancia (m)" class="modern_med" onblur="if(this.value=='') {this.value='Distancia (m)';this.style.opacity='0.5'}else{this.style.opacity='1'}" onfocus="if(this.value=='Distancia (m)') this.value='';this.style.opacity='1'" tabindex="3" />

            <input id="duration" name="duration" type="text" value="Duración (hh:mm:ss)" class="modern_med" onblur="if(this.value=='') {this.value='Duración (hh:mm:ss)';this.style.opacity='0.5'}else{this.style.opacity='1'}" onfocus="if(this.value=='Duración (hh:mm:ss)') this.value='';this.style.opacity='1';" tabindex="4" />
            <br />
            <input id="datepicker-entry" name="date" type="text" value="Fecha (AAAA-MM-DD)" class="modern_med" onblur="if(this.value=='' || this.value=='Fecha (AAAA-MM-DD)'){this.value='Fecha (AAAA-MM-DD)';this.style.opacity='0.5'}else{this.style.opacity='1'}" onfocus="if(this.value=='Fecha (AAAA-MM-DD)') this.value='';this.style.opacity='1';" tabindex="5" />

            <input id="time" name="time" type="text" value="Hora (hh:mm)" class="modern_med" onblur="if(this.value=='') {this.value='Hora (hh:mm)';this.style.opacity='0.5'}else{this.style.opacity='1'}" onfocus="if(this.value=='Hora (hh:mm)') this.value='';this.style.opacity='1';" tabindex="6" />
            <br />

            <input id="max_pace" name="max_pace" type="text" value="Pico ritmo (mm:ss) min/km" class="modern_med" onblur="if(this.value==''){this.value='Pico ritmo (mm:ss) min/km';this.style.opacity='0.5'}else{this.style.opacity='1'}" onfocus="if(this.value=='Pico ritmo (mm:ss) min/km')this.value='';this.style.opacity='1';" tabindex="7" />

<!--            <input id="pace" name="pace" type="text" value="Ritmo (min/s)" class="modern_med" onblur="if(this.value=='') {this.value='Ritmo (min/s)';this.style.opacity='0.5'}else{this.style.opacity='1'}" onfocus="if(this.value=='Ritmo (min/s)') this.value='';this.style.opacity='1';" tabindex="7" />

            <input id="speed" name="speed" type="text" value="Velocidad (km/h)" class="modern_med" onblur="if(this.value==''){this.value='Velocidad (km/h)';this.style.opacity='0.5'}else{this.style.opacity='1'}" onfocus="if(this.value=='Velocidad (km/h)') this.value='';this.style.opacity='1';" tabindex="9" />

            <input id="maxSpeed" name="maxSpeed" type="text" value="Velocidad máx (km/h)" class="modern_med" onblur="if(this.value==''){this.value='Velocidad máx (km/h)';this.style.opacity='0.5'}else{this.style.opacity='1'}" onfocus="if(this.value=='Velocidad máx (km/h)') this.value='';this.style.opacity='1';" tabindex="10" />
            <br />
-->

            <input id="calories" name="calories" type="text" value="Calorías (kcal)" class="modern_med" onblur="if(this.value=='') {this.value='Calorías (kcal)';this.style.opacity='0.5'}else{this.style.opacity='1'}" onfocus="if(this.value=='Calorías (kcal)') this.value='';this.style.opacity='1';" tabindex="8" />
            <br />

            <input id="beats" name="beats" type="text" value="Pulsaciones (ppm)" class="modern_med" onblur="if(this.value=='') {this.value='Pulsaciones (ppm)';this.style.opacity='0.5'}else{this.style.opacity='1'}" onfocus="if(this.value=='Pulsaciones (ppm)') this.value='';this.style.opacity='1';" tabindex="9" />

            <input id="max_beats" name="max_beats" type="text" value="Pico pulsaciones (ppm)" class="modern_med" onblur="if(this.value=='') {this.value='Pico pulsaciones (ppm)';this.style.opacity='0.5'}else{this.style.opacity='1'}" onfocus="if(this.value=='Pico pulsaciones (ppm)') this.value='';this.style.opacity='1';" tabindex="10" />
            <br />

            <input id="upositive" name="upositive" type="text" value="Altura ganada (m)" class="modern_med" onblur="if(this.value=='') {this.value='Altura ganada (m)';this.style.opacity='0.5'}else{this.style.opacity='1'}" onfocus="if(this.value=='Altura ganada (m)') this.value='';this.style.opacity='1';" tabindex="11" />

            <input id="unegative" name="unegative" type="text" value="Altura perdida (m)" class="modern_med" onblur="if(this.value=='') {this.value='Altura perdida (m)';this.style.opacity='0.5'}else{this.style.opacity='1'}" onfocus="if(this.value=='Altura perdida (m)') this.value='';this.style.opacity='1';" tabindex="12" />
            <br />

            <input id="tags" name="tags" type="text" value="Etiquetas" class="modern_med" onblur="if(this.value=='') {this.value='Etiquetas';this.style.opacity='0.5'}else{this.style.opacity='1'}" onfocus="if(this.value=='Etiquetas') this.value='';this.style.opacity='1';" tabindex="13" />
            <br />

            <input id="equipment" name="equipment" type="text" value="Equipamiento" class="modern_med" onblur="if(this.value=='') {this.value='Equipamiento';this.style.opacity='0.5'}else{this.style.opacity='1'}" onfocus="if(this.value=='Equipamiento') this.value='';this.style.opacity='1';" tabindex="14" />
            <br />

            <input id="equipment" name="equipment" type="text" value="Objetivos" class="modern_med" onblur="if(this.value=='') {this.value='Objetivos';this.style.opacity='0.5'}else{this.style.opacity='1'}" onfocus="if(this.value=='Objetivos') this.value='';this.style.opacity='1';" tabindex="15" />
            <br />
            <textarea id="comments" name="comments" rows="3" cols="50" class="modern_med" onblur="if(this.value=='') {this.value='Comentarios';this.style.opacity='0.5'}else{this.style.opacity='1'}" onfocus="if(this.value=='Comentarios') this.value='';this.style.opacity='1';" tabindex="16" />Comentarios</textarea>
            <br />
	        <input type="submit" id="submit_button" value="Enviar" tabindex="3" style="margin-top:10px"/>
	    </form>
    </div>
</div>
</body>
</html>
