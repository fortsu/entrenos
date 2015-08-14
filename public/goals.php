<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/global_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/database.php';
require_once $base_path . '/check_access.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Objetivos deportivos - FortSu</title>
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
    <script src="js/jquery/jquery.min.js"></script>
    <script src="js/jqueryui/current/js/jquery-ui.min.js"></script>

    <script type="text/javascript" src="js/entrenos.min.js?<?php echo $fv ?>"></script>
    <script type="text/javascript" src="js/jqueryui/jquery.ui.datepicker-es.js"></script>

    <!--[if lt IE 9]><script language="javascript" type="text/javascript" src="js/jqplot/excanvas.min.js"></script><![endif]-->
    <script language="javascript" type="text/javascript" src="js/jqplot/jquery.jqplot.min.js"></script>
    <script language="javascript" type="text/javascript" src="js/jqplot/plugins/jqplot.barRenderer.min.js"></script>
    <script language="javascript" type="text/javascript" src="js/jqplot/plugins/jqplot.categoryAxisRenderer.min.js"></script>
    <script language="javascript" type="text/javascript" src="js/jqplot/plugins/jqplot.pointLabels.min.js"></script>
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
                minDate: "+1D"
		    });
	    });
    </script>
    <script>
            function jqplot_barchart(goal_name, kms_goal){
              jQuery('#goal_graph').empty();
              jQuery.jqplot('goal_graph', kms_goal, {
                title: 'Kms acumulados para ' + goal_name,
                seriesDefaults:{
                  renderer:jQuery.jqplot.BarRenderer,
                  rendererOptions:{barWidth: 10},
                },
                axes: {
                  xaxis: {
                    renderer:jQuery.jqplot.DateAxisRenderer,
                    tickOptions:{formatString:'%Y/%m/%d'}
                  },
                  yaxis: {
                    min: 0,
                    padMin: 0,
                    tickOptions:{formatString:'%.1f km'}
                  }
                },
                highlighter: {
                    show: true,
                    sizeAdjust: 7.5
                },
              });
            };
    </script>
</head>
<body>
<div id="blanket" style="display:none;"></div>
<div id="main">

<?php
    include $base_path . '/user_header.php';
    include $base_path . '/navigation_bar.php';

    $user_goals = $current_user->getGoals(FALSE, $conn);
    $current_date = time("now");

    echo "<div id=\"goals_container\" style=\"position:relative\">";
    echo "<div id=\"goals_data\" style=\"margin:10px 10px\">";
    if (count($user_goals) > 0) {
        echo "<table class=\"simple\">";
            echo "<tbody>";
            foreach ($user_goals as $goal) {
                $num_km = $goal->retrieveInfo("distance", $conn);
                $log->info("Distance for goal #" . $goal->id . ": " . $num_km . " km");
                $goal_date = strtotime($goal->goal_date);
                $goal_date_past = false;
                if ($goal_date < $current_date){
                    $goal_date_past = true;
                    $log->info("Goal #" . $goal->id . " is in the past (" . $goal->goal_date . ")");
                }
                echo "<tr>";
                    echo "<td style=\"background:transparent;padding: 2px 0px;border-width: 0px\">";
                        echo "<a href=/forms/formAthlete.php?action=remove_goal&entry=" . $goal->id . " onclick=\"return confirm('¿Borrar el objetivo de " . $goal->name . "?');\">";
                            echo "<img src=\"images/close-icon_16.png\" alt=\"Borrar entrada\" title=\"Borrar objetivo\"/>";
                        echo "</a>";
                    echo "</td>";
                    echo "<td>" . $goal->name . "</td>";
                    echo "<td";
                        if ($goal_date_past) echo " id=\"invalid\"";
                    echo ">" . $goal->goal_date . "</td>";
                    echo "<td> " . $goal->goal_time . " </td>";
                    echo "<td> " . $goal->description . " </td>";
                    echo "<td>";
                        if ($num_km > 0){
                            $kms_goal = $goal->getKmDays($conn, $current_user->id);
                            $log->debug("kms for goal #" . $goal->id . ": " . json_encode($kms_goal));
                            $size = count($kms_goal);
                            $graph_data = "";
                            for ($i = 0; $i < $size; $i++) {
                                $graph_data = $graph_data . "['" . $kms_goal[$i]['date'] . "'," . $kms_goal[$i]['distance'] . "]";
                                if($i != $size -1 ){
                                    $graph_data = $graph_data . ",";
                                }
                            }
                            echo " <a href=\"javascript:void(0)\" onclick=\"jqplot_barchart('$goal->name', [[" . $graph_data . "]]);return false;\"><img src=\"images/chart_bar_bw.png\" alt=\"Ver gráfica\" title=\"Ver gráfica\"></a> ";
                        }
                    echo $num_km . " km </td>";
                    if ($goal->report_enabled){
                        $server_path = $_SERVER['SERVER_NAME'] . pathinfo($_SERVER['REQUEST_URI'],PATHINFO_DIRNAME);
                        $report = "/" . $goal->report_url;
                        echo "<td><a href=\"" . $report . "\" title=\"" . $report . "\" target=\"_blank\">Enlace al informe</a></td>";
                    }
                echo "</tr>";
            }
            echo "</tbody>";
        echo "</table>";
    } // shows no table if no entry is found
?>
        </div>
        <div id="new_goals" style="position:relative">
            <a href="javascript:void(0)" onclick="displayDiv('arrow_goals','new_goals_form');return false;" class="prueba">
                <img id="arrow_goals" src="images/right_arrow.png">
                <span style="vertical-align:top;">Añadir objetivo</span>
            </a>
            <div id='new_goals_form' style="display:none;margin:10px 0px">
                <form action="forms/formAthlete.php" method="post" enctype="multipart/form-data" onsubmit="this.report_enabled.value = this.report_enabled.checked;">
                <input type="hidden" name="action" value="goal">
                <input type="hidden" name="user_id" value="<?php echo $current_user->id; ?>">
                <input id="name" name="name" type="text" value="Nombre" onblur="if(this.value==''){this.value='Nombre';this.style.opacity='0.5';}" onfocus="if(this.value=='Nombre') this.value=''; this.style.opacity='1';" style="opacity:0.5" size="12" tabindex="1" />
                <input id="datepicker" name="goal_date" type="text" value="Fecha" onblur="if(this.value=='' || this.value=='Fecha'){this.style.opacity='0.5';this.value='Fecha';}" onfocus="this.style.opacity='1';" style="opacity:0.5" size="10" tabindex="2" />
                <input id="goal_time" name="goal_time" type="text" value="Tiempo objetivo" onblur="if(this.value==''){this.value='Tiempo objetivo';this.style.opacity='0.5';}" onfocus="if(this.value=='Tiempo objetivo') this.value=''; this.style.opacity='1';" style="opacity:0.5" size="10" tabindex="3" /><br />
                <textarea id="description" name="description" rows="2" cols="40" type="text" value="Descripción" onblur="if(this.value==''){this.value='Descripción';this.style.opacity='0.5';}" onfocus="if(this.value=='Descripción') this.value=''; this.style.opacity='1';" style="opacity:0.5" tabindex="4" />Descripción</textarea><br />
                <span style="font-size:smaller">¿Informe?</span><input id="report_enabled" name="report_enabled" value="0" type="checkbox"/></td><br />
	            <input type="submit" id="submit_button" value="Añadir objetivo" tabindex="5"/>
	        </form>
            </div>
        </div>
        <div id="goal_graph" style="position:relative"></div>
    </div>
</div>
</body>
</html>
