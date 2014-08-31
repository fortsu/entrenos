<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/global_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/database.php';
require_once $base_path . '/check_access.php';

use Entrenos\Equipment;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Gestión de equipamiento - FortSu</title>
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

    try {
        $equipment = Equipment::getUserEquipment($current_user->id, FALSE, $conn);
        if ($equipment) {
            //print_r($equipment);
            // Only showing: name, description, kms usage, expected_life, prior_usage, active,kms registered
            $fields = array('name', 'description', 'total_usage', 'expected_life', 'prior_usage', 'usage', 'active');
            $adv_fields = array('expected_life','prior_usage', 'usage');
            echo "<table style=\"margin: 10px 0px\" class=\"simple\">";
            echo "<thead>";
            echo "<tr>";
            echo "<th> &nbsp; </th> <th> Nombre </th><th> Descripción </th><th> Total kms </th><th class=\"adv_equip_data\"> Vida útil </th><th class=\"adv_equip_data\"> Uso anterior </th><th class=\"adv_equip_data\"> Registro kms </th> <th> ¿En uso? </th> <th style=\"border:0px;background:none;margin-left:5px;\"> <a id=\"more_equip_info\"href=\"javascript:void(0)\" onclick=\"adv_equip()\">más info &gt;</a> </th>";
            echo "</tr>";
            echo "</thead>";
            foreach ($equipment as $unit) {
                echo "<tr>";
                    echo "<td style=\"background:transparent;padding: 2px 0px;border-width: 0px\">";
                        echo "<a href=forms/formEquip.php?action=remove_equip&equip_id=" . $unit['id'] . " onclick=\"return confirm('¿Eliminar " . $unit['description'] . " " . $unit['name'] . "?\\nEsta acción no se puede deshacer...');\">";
                            echo "<img src=\"images/close-icon_16.png\" alt=\"[x]\" title=\"Borrar equipamiento\"/>";
                        echo "</a>";
                    echo "</td>";
                    foreach ($fields as $index => $key) {
                        $advanced_data = "";
                        if (in_array($key, $adv_fields)) {
                            $advanced_data = "class=\"adv_equip_data\"";
                        }
                        if ($key != 'active') {
                            if ($key != 'usage') { // No changes on what is supposed being tracked
                                if ($key != 'total_usage') {
                                    echo "<td " . $advanced_data . "><span contenteditable=\"true\" title=\"" . $unit[$key] . "\" onblur=\"edit_equip('" . $unit['id'] . "','" . $key . "');this.style.opacity='1';\" onclick=\"this.style.opacity='0.7';\" id=\"equip_" . $unit['id'] . "_" . $key . "\" style=\"opacity:1;\">" . $unit[$key] . "</span></td>";
                                } else {
                                    $total_usage = $unit['prior_usage'] + $unit['usage'];
                                    echo "<td>" . number_format($total_usage, 2, ',', '') . "</td>";
                                }
                            } else {
                                echo "<td " . $advanced_data . ">" . $unit[$key] . "</td>";
                            }
                        } else {
                            ($unit['active']) ? $checked = "checked" : $checked = "";
                            echo "<td style=\"border:0px;background:none;\"> <input type=\"checkbox\" id=\"active_" . $unit['id'] . "\" " . $checked . " onclick=\"enable_equip(" . $unit['id'] . ")\" /> </td>";
                        }
                    }
                echo "</tr>";
            }
            echo "</table>";
        } else { 
            echo "No hay registrado ningún equipamiento para " . $current_user->username;
        }

    } catch (Exception $e) {
        $log->error($e->getMessage());
        echo "Se produjo un error al recuperar el equipamiento de " . $user . " Inténtelo de nuevo más tarde.";
    }

?>
    <!--<a onclick="showHideLayer('new_equipment');return false" href="javascript:void(0)">Añadir material</a>-->
    <a href="javascript:void(0)" onclick="displayDiv('arrow_equip','new_equipment');return false;" class="prueba"><img id="arrow_equip" src="images/right_arrow.png"><span style="vertical-align:top;"> Añadir material</span></a>
    <div id='new_equipment'>
        <form action="forms/formEquip.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="user_id" value="<?php echo $current_user->id; ?>">
            <input type="hidden" name="action" value="addition">
            <input id="name" name="name" type="text" placeholder="Nombre"/>
            <input id="description" name="description" type="text" placeholder="Descripción"/><br />
            <input id="datepicker" name="equip_date" type="text" style="opacity: 0.5;" onfocus="this.style.opacity='1';" onblur="if(this.value=='' || this.value=='Fecha de compra'){this.style.opacity='0.5';this.value='Fecha de compra';}" tabindex="4" value="Fecha de compra">
            <input id="expected_life" name="expected_life" type="text" placeholder="Vida útil"/><br />
            <input id="prior_usage" name="prior_usage" type="text" placeholder="Uso anterior"/>
            <input id="active" name="active" type="checkbox" value="1" checked="true"/>¿En uso?<br />
			<input type="submit" id="submit_button" value="Añadir"/>
	    </form>
    </div>
</div>
</body>
</html>
