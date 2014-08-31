<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/global_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/database.php';
require_once $base_path . '/check_access.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Gestión de etiquetas - FortSu</title>
	<link rel="shortcut icon" href="images/favicon.ico">
    <meta name="description" content="Gestión de actividades deportivas online" />
    <meta name="keywords" content="deporte,correr,ciclismo,atletismo,maratón,carrera,pulsómetro,gps,gpx,polar,garmin,suunto,zapatillas,bicicleta,ropa deportiva" />
    <meta name="author" content="FortSu.com" />
    <meta charset="UTF-8" />
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <link rel="stylesheet" href="estilo/entrenos.min.css?<?php echo $fv ?>" type="text/css"/>
    <link rel="stylesheet" href="js/jqueryui/current/themes/base/jquery-ui.min.css" type="text/css"/>

    <script language="javascript" type="text/javascript" src="js/jquery/jquery.min.js"></script>
    <script language="javascript" type="text/javascript" src="js/entrenos.min.js?<?php echo $fv ?>"></script>
    <script language="javascript" type="text/javascript" src="js/jqueryui/current/js/jquery-ui.min.js"></script>

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
            function jqplot_barchart(tag_name, kms_tag){
              jQuery('#tag_graph').empty();
              jQuery.jqplot('tag_graph', kms_tag, { 
                title: 'Kms acumulados para ' + tag_name,
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

    $user_tags = $current_user->getTags($conn);

    echo "<div id=\"tags_container\" style=\"position:relative;\">";
    echo "<div id=\"tags_data\" style=\"margin:10px;\">";
    if (count($user_tags) > 0) {
        echo "<table class=\"simple\">";
            echo "<tbody>";
            foreach ($user_tags as $tag) {
                $num_km = $tag->retrieveInfo("distance", $conn);
                $log->info("Distance for tag #" . $tag->id . ": " . $num_km . " km");
                echo "<tr>";
                    echo "<td style=\"background:transparent;padding: 2px 0px;border-width: 0px\">";
                        echo "<a href=forms/formAthlete.php?action=remove_tag&entry=" . $tag->id . " onclick=\"return confirm('¿Borrar la etiqueta " . $tag->name . "?');\">";
                            echo "<img src=\"images/close-icon_16.png\" alt=\"Borrar entrada\" title=\"Borrar etiqueta\"/>";
                        echo "</a>";
                    echo "</td>";
                    echo "<td>" . $tag->name . "</td>";
                    echo "<td>";
                        if ($num_km > 0){ 
                            $kms_tag = $tag->getKmDays($conn, $current_user->id);
                            $log->info("kms for tag #" . $tag->id . ": " . json_encode($kms_tag));
                            $size = count($kms_tag);
                            $graph_data = "";
                            for ($i = 0; $i < $size; $i++) {
                                $graph_data = $graph_data . "['" . $kms_tag[$i]['date'] . "'," . $kms_tag[$i]['distance'] . "]";
                                if($i != $size -1 ){
                                    $graph_data = $graph_data . ",";
                                }
                            }
                            echo " <a href=\"javascript:void(0)\" onclick=\"jqplot_barchart('$tag->name', [[" . $graph_data . "]]);return false;\"><img src=\"images/chart_bar_bw.png\" alt=\"Ver gráfica\" title=\"Ver gráfica\"></a> ";
                        }               
                    echo $num_km . " km </td>";
                    if ($tag->report_enabled){
                        $report = "http://" . $base_url . "/" . $tag->report_url;
                        echo "<td><a href=\"" . $report . "\" title=\"" . $report . "\" target=\"_blank\">Enlace al informe</a></td>";
                    }
                echo "</tr>";
            }
            echo "</tbody>";
        echo "</table>";
    } // shows no table if no entry is found
?>
        </div>
        <div id="new_tags" style="margin:10px;">
            <a href="javascript:void(0)" onclick="displayDiv('arrow_tags','new_tags_form');return false;" class="prueba"><img id="arrow_tags" src="images/right_arrow.png"><span style="vertical-align:top;">Añadir etiqueta</span></a>
            <div id='new_tags_form' style="display:none;margin:10px 0px">
                <form action="forms/formAthlete.php" method="post" enctype="multipart/form-data" onsubmit="this.report_enabled.value = this.report_enabled.checked;">
                <input type="hidden" name="action" value="tag">
                <input type="hidden" name="user_id" value="<?php echo $current_user->id; ?>">
                <input id="name" name="name" type="text" value="Nombre" onblur="if(this.value==''){this.value='Nombre';this.style.opacity='0.5';}" onfocus="if(this.value=='Nombre') this.value=''; this.style.opacity='1';" style="opacity:0.5" size="12" tabindex="1" />
                <span style="font-size:smaller">¿Informe?</span><input id="report_enabled" name="report_enabled" value="0" type="checkbox"/></td>
	            <input type="submit" id="submit_button" value="Añadir etiqueta" tabindex="5"/>
	        </form>
            </div>
        </div>
    </div>
    <div id="tag_graph" style="position:relative"></div>
</div>
</body>
</html>
