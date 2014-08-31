<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/global_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/database.php';
require_once $base_path . '/check_access.php';
use Entrenos\Athlete;

// Specific settings
$week = date("W");
$month = date("m");
$year = date("Y");

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Estadísticas y gráficas - FortSu</title>
	<link rel="shortcut icon" href="images/favicon.ico">
    <meta name="description" content="Gestión de actividades deportivas online" />
    <meta name="keywords" content="deporte,correr,ciclismo,atletismo,maratón,carrera,pulsómetro,gps,gpx,polar,garmin,suunto,zapatillas,bicicleta,ropa deportiva" />
    <meta name="author" content="FortSu.com" />
    <meta charset="UTF-8" />
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />

    <link rel="stylesheet" href="estilo/entrenos.min.css?<?php echo $fv ?>" type="text/css"/>
    <link rel="stylesheet" href="js/jqueryui/current/themes/base/jquery-ui.min.css" type="text/css"/>

    <script type="text/javascript" src="js/entrenos.min.js?<?php echo $fv ?>"></script>
    <script type="text/javascript" src="js/jquery/jquery.min.js"></script>
    <script type="text/javascript" src="js/jqueryui/current/js/jquery-ui.min.js"></script>
    <script type="text/javascript" src="js/jqueryui/jquery.ui.datepicker-es.js"></script>

    <!--[if lt IE 9]><script language="javascript" type="text/javascript" src="js/jqplot/excanvas.min.js"></script><![endif]-->
    <script language="javascript" type="text/javascript" src="js/jqplot/jquery.jqplot.min.js"></script>
    <script language="javascript" type="text/javascript" src="js/jqplot/plugins/jqplot.dateAxisRenderer.min.js"></script>
    <script language="javascript" type="text/javascript" src="js/jqplot/plugins/jqplot.highlighter.min.js"></script>
    <script language="javascript" type="text/javascript" src="js/jqplot/plugins/jqplot.cursor.min.js"></script>
    <link rel="stylesheet" type="text/css" href="js/jqplot/jquery.jqplot.min.css" />

    <script>
        $(document).ready(function() {
            $.datepicker.setDefaults( $.datepicker.regional[ "es" ] );
		    $( "#datepicker" ).datepicker({
			    dateFormat: "yy-mm-dd",
			    firstDay: 1,
                changeMonth: true,
			    changeYear: true,
                maxDate: "+0D"
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
?>
    <div id="charts" style="position:relative;margin:15px 0px 5px 0px;">
        <a href="javascript:void(0)" onclick="displayDiv('arrow_charts','time_charts');return false;" class="prueba">
            <img id="arrow_charts" src="images/right_arrow.png">
            <span style="vertical-align:top;">Gráficas de distancia acumulada</span>
        </a>
        <div id="time_charts" style="display:none;margin:10px">
            <a href="javascript:void(0)" onclick='loadChart("W",<?php echo "\"".$week."\",\"".$month."\",\"".$year."\""; ?>,"bar_chart");return false'> Semana </a> | 
            <a href="javascript:void(0)" onclick='loadChart("M",<?php echo "\"".$week."\",\"".$month."\",\"".$year."\""; ?>,"bar_chart");return false'> Mes </a> | 
            <a href="javascript:void(0)" onclick='loadChart("Y",<?php echo "\"".$week."\",\"".$month."\",\"".$year."\""; ?>,"bar_chart");return false'> Año </a>
            <br />
            <br />
            <div id="bar_chart"></div>
        </div>
    </div>

    <div id="weight_stuff" style="position:relative;margin:5px 0px">
<?php
    try {
        $current_athlete = new Athlete($current_user->id);
        $athlete_history = $current_athlete->history($conn);
/*
        $athlete_weight = $current_athlete->getProp($conn,"weight"); //last date comes first
        $log->info("Weight data retrieved for user #" . $current_user->id . ": " . json_encode($athlete_weight));  
        $size = count($athlete_weight);
        $graph_data = "";
        for ($i = 0; $i < $size; $i++) {
            $graph_data = $graph_data . "['" . $athlete_weight[$i]['date'] . "'," . $athlete_weight[$i]['weight'] . "]";
            if($i != $size -1 ){
                $graph_data = $graph_data . ",";
            }
        }
*/
        $graph_data = "";
        foreach($athlete_history as $loop_item) {
            if (isset($loop_item['weight']) and intval($loop_item['weight']) > 0) {
                $graph_data = $graph_data . "['" . $loop_item['date'] . "'," . $loop_item['weight'] . "],";
            }
        }
        // Remove comma from the end of string
        $graph_data = rtrim($graph_data, ",");
        $dateTime = new DateTime("now", new DateTimeZone("UTC"));
	    $update_date = $dateTime->format("Y-m-d");
?>
        <script>
        $(document).ready(function() {
            $.jqplot('weight_history',[[<?php echo $graph_data; ?>]],
                {
                    title:'Evolución del peso (kg)',
                    axes:{
                        xaxis:{
                            renderer:jQuery.jqplot.DateAxisRenderer,
                            tickOptions:{formatString:'%Y/%m/%d'}
                        },
                        yaxis:{
                            tickOptions:{
                                formatString:'%.1f kg'
                            }
                        }
                    },
                    highlighter: {
                        show: true,
                        sizeAdjust: 7.5
                    },
                    series:[{lineWidth:4, markerOptions:{style:'filledCircle'}}]
                }
            );
        });
        </script>
        <a href="javascript:void(0)" onclick="displayDiv('arrow_weight','weight_info');return false;" class="prueba">
            <img id="arrow_weight" src="images/down_arrow.png">
            <span style="vertical-align:top;">Evolución del peso</span>
        </a>
        <div id="weight_info" style="margin:10px">
            <div id='weight_history' style="height:300px;width:500px;margin:10px;left:50px"></div>
            <div id='weight_changes' style="position:absolute;right:10px;top:10px;width:550px">
                <br />
                <div style="color:gray;">Últimas mediciones:</div>
                <?php
                    echo "<table border=\"1\" class=\"simple\">";
                        echo "<thead>";
                            echo "<tr>";
                                echo "<th style=\"border: 0px none;\">Fecha</th>";
                                echo "<th style=\"border: 0px none;\">Peso</th>";
                                echo "<th style=\"border: 0px none;\">Grasa</th>";
                                echo "<th style=\"border: 0px none;\">Músculo</th>";
                                echo "<th style=\"border: 0px none;\">Agua</th>";
                            echo "</tr>";
                        echo "</thead>";
                        echo "<tbody>";
                        $num_display = 5;
                        //$num_entries = count($athlete_weight);
                        $num_entries = count($athlete_history);
                        if ($num_entries < $num_display) {
                            $num_display = $num_entries;
                        }
                        //$athlete_weight_tmp = array_slice($athlete_weight, 0, $num_display);
                        //foreach ($athlete_weight_tmp as $entry) {
                        $athlete_history_tmp = array_slice($athlete_history, 0, $num_display);
                        $num_dec = 1;
                        $dec_point = ",";
                        foreach ($athlete_history_tmp as $entry) {
                            echo "<tr>"; 
                                echo "<td>" . $entry['date'] . "</td>";
                                echo "<td>" . number_format($entry['weight'], $num_dec, $dec_point,'') . " kg </td>";
                                if (isset($entry['body_fat']) and floatval($entry['body_fat']) > 0.1) {
                                    echo "<td>" . number_format($entry['body_fat'], $num_dec, $dec_point,'') . "% </td>";
                                } else {
                                    echo "<td> ND </td>";
                                }
                                if (isset($entry['body_muscle']) and floatval($entry['body_muscle']) > 0.1) {
                                    echo "<td>" . number_format($entry['body_muscle'], $num_dec, $dec_point,'') . " kg </td>";
                                } else {
                                    echo "<td> ND </td>";
                                }
                                if (isset($entry['body_water']) and floatval($entry['body_water']) > 0.1) {
                                    echo "<td>" . number_format($entry['body_water'], $num_dec, $dec_point,'') . "% </td>";
                                } else {
                                    echo "<td> ND </td>";
                                }
                                echo "<td style=\"background:transparent;padding: 2px 0px;border-width: 0px\">";
                                    echo "<a href=forms/formAthlete.php?action=remove_history&entry=" . $entry['id'] . ">";
                                        echo "<img src=\"images/close-icon_16.png\" alt=\"Borrar entrada\" title=\"Borrar entrada\"/>";
                                    echo "</a>";
                                echo "</td>";
                            echo "</tr>";
                        } 
                        echo "</tbody>";
                    echo "</table>";
                ?>
                <div id='athlete_update'>
                    <hr style="width:80%"/>
                    <form action="forms/formAthlete.php" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="personal_data">
                        <input type="hidden" name="height" value="0">
                        <input type="hidden" name="rest_beats" value="0">
                        <input type="hidden" name="max_beats" value="0">
                        <input type="hidden" name="vo2_max" value="0">
                        <input id="datepicker" name="date" type="text" size="12" value="<?php echo $update_date ?>" onblur="if(this.value=='')this.value='<?php echo $update_date ?>';" tabindex="1" />
                        <input id="weight" name="weight" type="text" placeholder="Peso (kg)" size="6" tabindex="2" />
                        <input id="body_fat" name="body_fat" type="text" placeholder="Grasa (%)" size="6" tabindex="3"/>
                        <input id="body_muscle" name="body_muscle" type="text" placeholder="Músculo (kg)" size="8" tabindex="4"/>
                        <input id="body_water" name="body_water" type="text" placeholder="Agua (%)" size="6" tabindex="5"/>
                        <br />
	                    <input type="submit" id="submit_button" value="Añadir medición" tabindex="6" style="margin-top:6px;"/>
	                </form>
                </div>
            </div> <!-- weight_changes -->
<?php
    } catch (Exception $e) {
        echo "No se ha encontrado información sobre el peso de " . $current_user->username;             
    }
?>
        </div> <!-- weight_info -->
    </div> <!-- weight_stuff -->
    <div id="bpm_stuff" style="position:relative;margin:5px 0px">
<?php
    try {
        $current_athlete = new Athlete($current_user->id);
        $sport_id = 0; //hardcoding sport to running
        $athlete_mpb = $current_athlete->collect_mpb($conn, $sport_id); 
        $log->debug("Meters per beat data retrieved for user #" . $current_user->id . ": " . count($athlete_mpb) . " days");
        $graph_mpb = "";
        foreach ($athlete_mpb as $date => $mpb) {
            $graph_mpb = $graph_mpb . "['" . $date . "'," . $mpb . "],";
        }
        //$graph_mpb = rtrim(",", $graph_mpb); //removing trailing comma to avoid issues in javascript
?>
        <script>
        $(document).ready(function() {
            var mpb_plot = $.jqplot('mpb_history',[[<?php echo $graph_mpb; ?>]],
                {
                    title:'Metros por latido (correr)',
                    axes:{
                        xaxis:{
                            renderer:jQuery.jqplot.DateAxisRenderer,
                            tickOptions:{formatString:'%Y/%m/%d'}
                        },
                        yaxis:{
                            tickOptions:{
                                formatString:'%.3f mpl'
                            }
                        }
                    },
                    highlighter: {
                        show: true,
                        sizeAdjust: 7.5
                    },
                    series:[{lineWidth:4, markerOptions:{style:'filledCircle'}}],
                    cursor:{
                        show: true,
                        zoom:true,
                        showTooltip:false
                    } 
                }
            );
            $('.button-reset').click(function() { mpb_plot.resetZoom() });
        });
        </script>
        <a href="javascript:void(0)" onclick="displayDiv('arrow_mpb','mpb_info');return false;" class="prueba">
            <img id="arrow_mpb" src="images/down_arrow.png">
            <span style="vertical-align:top;">Histórico de metros por latido</span>
        </a>
        <div id="mpb_info" style="margin:10px">
            <div id='mpb_history' style="height:300px;width:500px;margin:10px;left:50px"></div>
            <button class="button-reset">Quitar zoom</button>
<?php
    } catch (Exception $e) {
        echo "No se ha encontrado información sobre los metros por pulsación de " . $current_user->username;             
    }
?>
        </div> <!-- bpm_info -->
    </div> <!-- bpm_stuff -->
</div> <!-- main -->
</body>
</html>
