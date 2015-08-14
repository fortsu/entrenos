<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/global_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/database.php';

use Entrenos\Activity;
use Entrenos\Utils\Utils;
use Entrenos\User;
use Entrenos\Equipment;
use Entrenos\Tag;
use Entrenos\Goal;
use Entrenos\Charts;
use Entrenos\Sport;

$record_id = $_GET['id'];
$has_gpx = $_GET['has_gpx'];
$log->debug("Request: " . json_encode($_REQUEST));
session_start();
if (isset ($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    $user_id = 0;
}

// Retrieving data from DB
$current_act = new Activity(array('id' => $record_id));
$current_act->getActivity($conn);
if ($has_gpx) {
    $report_name = $base_path . "/users/" . $current_act->user_id . "/reports/alt_profile_" . $current_act->id . ".png";
    if (!file_exists($report_name)) {
        $log->info($current_act->user_id . "|Creating altitude graph: " . $report_name);
        Charts::altitudeChart ($current_act->user_id, $current_act->id, $conn);
    }
} else {
    $log->info($current_act->user_id . "|Activity " . $current_act->id . " has no GPX related file, skipping altitude chart");
}
$current_user = new User(array('id'=>$user_id));
$act_user = new User(array('id'=>$current_act->user_id));
if ($act_user->id === $current_user->id) {
    $current_user->getFBToken($conn);
} else {
    $log->info($current_act->user_id . "|Rendering activity " . $current_act->id . " from user " . $act_user->id . " to user " . $current_user->id . " | " . $_SERVER['REMOTE_ADDR'] . " | " . $_SERVER['HTTP_USER_AGENT']);
}

if ($current_act) {
    $num_laps = count($current_act->laps);
    //retrieving only active units
    $user_equip = $act_user->getEquipmentIds(TRUE, $conn);
    $num_equip = count($user_equip);
    // Only goals with target date older to current activity will not be displayed, retrieveing all for current user
    $user_goals = $act_user->getGoals(FALSE, $conn);
    $num_goals = count($user_goals);
    $user_tags = $act_user->getTags($conn); //retrieving only tags
    $num_tags = count($user_tags);

    if ($has_gpx) {
        echo "<a href=\"javascript:void(0)\" onclick=\"showHideLayer('elevation_chart');return false\">Gráfico altitud</a> | ";
    }
    if ($act_user->id === $current_user->id) {
        echo "<a id=\"similar_link\" href=\"javascript:void(0)\" title=\"Actividades con distancia similar (&plusmn; 5%)\"onclick=\"moveContent('similar_activ','extra_data','map_canvas'," . $has_gpx . ");return false\">Actividades similares</a> | ";
    }
    if ($num_laps > 0) {
        echo "<a href=\"javascript:void(0)\" id=\"laps_link\" title=\"Mostrar vueltas\" onclick='moveContent2(\"info_" . $current_act->id . "\",\"laps_data\")'>";
        echo "Mostrar vueltas</a>";
    }
    $dateAndTime = Utils::getDateAndTimeFromDateTime($current_act->start_time);
    if ($act_user->id === $current_user->id) {
/*
        if ($current_user->fb_access_token) {
            echo "<div id='share' style=\"display:inline;\">";
                // privacy hardcoded to SELF for testing purposes (SELF vs ALL_FRIENDS)
                echo " | <a href=\"javascript:void(0)\" onclick='post2FB(\"" . $current_act->id . "\", \"SELF\", \"share\");return false'>";
                echo "<img src='images/connect_favicon.png' alt='Facebook' title='Envía a tu muro de FB'>";
                echo "</a>";
            echo "</div>";
        }
*/
        echo "<br />";
        if ($has_gpx) {
            $export_filename = $dateAndTime['date'] . "_" . str_replace(":","",$dateAndTime['time']); //2011-01-20 12:51:24 -> 2011-01-20_125124
            echo "<a href=\"/forms/formExport.php?id=" . $current_act->id . "&filename=" . $export_filename . "\" title=\"Exportar actividad\">Exportar como GPX</a> ";
        }
        if ($current_act->visibility) {
            $act_visib = "<img id='lock' src='/images/unlock_16x16.png' alt='pública' style=\"vertical-align: text-top;\">";
            $visib_title = "Restringir la privacidad";
            $act_visib_txt = "Actividad pública";
        } else {
            $act_visib = "<img id='lock' src='/images/lock_16x16.png' alt='privada' style=\"vertical-align: text-top;\">";
            $visib_title = "Permitir acceso público y habilitar para compartir";
            $act_visib_txt = "Actividad privada";
        }
        echo " | " . $act_visib;
        echo " <a id=\"visible\" href=\"javascript:void(0)\" title=\"" . $visib_title . "\" onclick=\"changeVisibility('" . $current_act->id . "'); return false;\">" . $act_visib_txt . "</a> ";
        echo " | <a href=\"javascript:void(0)\" title=\"Eliminar la actividad\" onclick=\"deleteActivity('" . $current_act->start_time . "');return false\">Borrar</a> ";

        // Share
        $share_msg = $current_act->stringSummary(FALSE); // summary in just one line
        // Build links
        $current_url = $base_url . "/actividad/" . $current_act->id;
        $url_encoded = urlencode($current_url);
        $twitter_data = array("url" => $current_url,
                            "text" => $share_msg,
                            "via" => "fortsu");
        $share_twitter = "http://twitter.com/share?" . http_build_query($twitter_data, '', '&amp;');
        // FB will try to fetch content if only url parameter is passed
        $share_fb = "http://www.facebook.com/sharer.php?u=" . $url_encoded;
        $share_gplus = "https://plus.google.com/share?url=" . $url_encoded;

        //Manage visibility
        $share_div_style = "style='opacity:0.3'";
        $onclick_link = "onclick=\"alert('Haz pública la actividad para poder compartirla');\"";
        $meta_link = "data-href";
        if ($current_act->visibility) {
            $share_div_style = "";
            $onclick_link = "";
            $meta_link = "href";
        }
?>
        <div class="act-share" <?php echo $share_div_style; ?>>
            Compartir:
            <a class="link-share" id="link_share_fb" <?php echo $meta_link; ?>="<?php echo $share_fb; ?>" <?php echo $onclick_link; ?> target="_blank">
                <img src="/images/connect_favicon.png" width="14" height="14" alt="Facebook logo" title="Envía a tu muro de Facebook">
            </a>
             |
            <a class="link-share" id="link_share_tw" <?php echo $meta_link; ?>="<?php echo $share_twitter; ?>" <?php echo $onclick_link; ?> target="_blank">
                <img src="/images/twitter-14x14.png" width="14" height="14" alt="Twitter logo" title="Comparte en tu perfil de Twitter">
            </a>
             |
            <a class="link-share" id="link_share_gp" <?php echo $meta_link; ?>="<?php echo $share_gplus; ?>" <?php echo $onclick_link; ?> target="_blank">
                <img src="/images/gplus-14x14.png" width="14" height="14" alt="Google Plus logo" title="Comparte en tu perfil de Google Plus">
            </a>
        </div>
<?php
        // Previous and next activities
        echo "<div style=\"margin: 5px auto auto;width: 200px;\">";
            if ($current_act->prev_act > 0) {
                $prev_act = new Activity(array("id"=>$current_act->prev_act, "user_id"=>$current_act->user_id));
                $prev_act->getActivity($conn);
                $act_info = sprintf("%01.1f",round($prev_act->distance/1000)/1000) . " km @ " . Utils::formatPace($prev_act->pace);
                if (empty($prev_act->title)) {
                    $act_info = $prev_act->start_time . " - " . $act_info;
                } else {
                    $act_info = $prev_act->title . " - " . $act_info;
                }
                echo "<a href=\"/activity.php?activity_id=" . $current_act->prev_act . "\" title=\"" . $act_info . "\">< Anterior</a>";
            } else {
                echo "<a href=\"/search.php\" title=\"Buscar actividades\">¿Buscas algo?</a>";
            }
            echo " | ";
            if ($current_act->next_act > 0) {
                $next_act = new Activity(array("id"=>$current_act->next_act, "user_id"=>$current_act->user_id));
                $next_act->getActivity($conn);

                $act_info = sprintf("%01.1f",round($next_act->distance/1000)/1000) . " km @ " . Utils::formatPace($next_act->pace);
                if (empty($next_act->title)) {
                    $act_info = $next_act->start_time . " - " . $act_info;
                } else {
                    $act_info = $next_act->title . " - " . $act_info;
                }
                echo "<a href=\"/activity.php?activity_id=" . $current_act->next_act . "\" title=\"" . $act_info . "\">Siguiente ></a>";
            } else {
                echo "<a href=\"/search.php\" title=\"Buscar actividades\">¿Buscas algo?</a>";
            }
        echo "</div>";
    }
    echo "<div id='laps' style=\"margin-top:5px;\">";
    echo "\r\n";
        echo "<table class=\"simple\">";
            echo "<tr>";
                echo "<th style=\"text-align:left;\">Comienzo</th>";
                echo "<td>";
                echo $dateAndTime['date'] . " " . $dateAndTime['time'];
                echo "</td>";
            echo "</tr>";
            echo "<tr>";
                echo "<th style=\"text-align:left;\">Título</span></th>";
                if ($act_user->id === $current_user->id) {
                    $display_text = $current_act->title;
                    if ($display_text == "") {
                        $display_text = "<span style=\"opacity:0.3;\">Añadir título</span>";
                    }
                    echo "<td><div id=\"title_act_" . $current_act->id . "\" contentEditable=\"true\" onclick=\"if(this.textContent=='Añadir título')this.innerHTML='';this.style.opacity='1';\" onblur='edit_act_field(\"" . $current_act->id . "\",\"title\",\"" . $current_act->title . "\",this.textContent);return false;' title=\"Título (pulsa para editar)\">" . $display_text . "</div></td>";
                } else {
                   echo "<td><div id=\"title_act_" . $current_act->id . "\">" . $current_act->title . "</div></td>";
                }
            echo "</tr>";
            echo "<tr>";
                echo "<th style=\"text-align:left;\">Deporte</th>";
                // (0 => "Correr", 1 => "Ciclismo", 2 => "Caminar", 3 => "Natación")
                echo "<td>";
                // Only owner can change activity's sport
                if ($act_user->id === $current_user->id) {
                    echo "<select id=\"sport_act_" . $current_act->id . "\" onchange=\"change_sport(this.id, " . $current_act->sport_id . ");\" title=\"Pulsa para cambiar el deporte asignado\" style=\"font-size:14px;font-family:inherit;background:transparent;padding:0px;border:0;border-radius:0;\">";
                    foreach (Sport::$display_es as $sport_id_loop => $sport_name_loc) {
                        $display_selected = "";
                        if (intval($sport_id_loop) == intval($current_act->sport_id)) {
                            $display_selected = " selected";
                        }
                        echo "<option value=\"" . $sport_id_loop . "\"" . $display_selected . ">" . $sport_name_loc . "</option>";
                    }
                    echo "</select>";
                } else {
                   echo Sport::$display_es[$current_act->sport_id];
                }
                echo "</td>";
            echo "</tr>";
            echo "<tr>";
                echo "<th style=\"text-align:left;\">Distancia <span style=\"font-size:small;\">(km)</span></th>";

                $display_dist = sprintf("%01.3f",round($current_act->distance/1000)/1000);
                // Check if distance field can be empty -> 0?
                if ($display_dist == "") {
                    $display_dist = "0.00";
                }
                if ($act_user->id === $current_user->id) {
                    echo "<td><div id=\"distance_act_" . $current_act->id . "\" contentEditable=\"true\" onclick=\"if(this.textContent=='Añadir distancia')this.innerHTML='';this.style.opacity='1';\" onblur='edit_act_field(\"" . $current_act->id . "\",\"distance\",\"" . $current_act->distance . "\",this.textContent);return false;' title=\"Distancia (pulsa para editar)\">" . $display_dist . "</div></td>";
                } else {
                   echo "<td><div id=\"distance_act_" . $current_act->id . "\">" . $display_dist . "</div></td>";
                }

            echo "</tr>";
            echo "<tr>";
                echo "<th style=\"text-align:left;\">Duración <span style=\"font-size:small;\">(h:mm:ss.F)</span></th>";
                echo "<td>" . Utils::formatMs($current_act->duration) . "</td>";
            echo "</tr>";
            echo "<tr>";
                // Display pace (speed) in km/h when sport is cycling
                if (intval($current_act->sport_id) == 1) {
                    echo "<th style=\"text-align:left;\">Velocidad <span style=\"font-size:small;\">(km/h)</span></th>";
                    echo "<td><span id=\"speed_act_" . $current_act->id . "\">" . number_format($current_act->speed,2) . "</span>";
                    if (floatval($current_act->max_speed) > 0) {
                        echo " [<span style=\"color:rgb(25,25,112);\">" . number_format($current_act->max_speed,2) . "</span>]</td>";
                    }
                } else {
                    echo "<th style=\"text-align:left;\">Ritmo <span style=\"font-size:small;\">(min/km)</span></th>";
                    #max_pace stored in db as m/s
                    if ($current_act->max_pace > 0) {
                        $max_pace_dec = 50/(3*$current_act->max_pace);
                    } else {
                        $max_pace_dec = 0;
                    }
                    echo "<td><span id=\"pace_act_" . $current_act->id . "\">" . Utils::formatPace($current_act->pace) . "</span>";
                    if (floatval($max_pace_dec) > 0) {
                        echo " [<span style=\"color:rgb(25,25,112);\">".Utils::formatPace($max_pace_dec) . "</span>]</td>";
                    }
                }
            echo "</tr>";
            echo "<tr>";
                echo "<th style=\"text-align:left;\">Pulso <span style=\"font-size:small;\">(ppm)</span></th>";
                echo "<td>" . round($current_act->beats);
                if (intval($current_act->max_beats)>0) {
                    echo " [<span style=\"color:rgb(139,0,0);\">" . $current_act->max_beats . "</span>] ";
                }
                echo "</td>";
            echo "</tr>";
            echo "<tr>";
                echo "<th style=\"text-align:left;\">Desnivel <span style=\"font-size:small;\">(m)</span></th>";
                echo "<td> &uarr; " . $current_act->upositive . " | " . $current_act->unegative . " &darr; </td>";
            echo "</tr>";
            echo "<tr>";
                echo "<th style=\"text-align:left;\">Calorías <span style=\"font-size:small;\">(kcal)</span></th>";
                echo "<td>" . $current_act->calories . "</td>";
            echo "</tr>";
            echo "</table>";

            if ($act_user->id === $current_user->id) {
                $display_text = $current_act->comments;
                if ($display_text == "") {
                    $display_text = "<span style=\"opacity:0.3;\">Añadir comentarios</span>";
                }
                echo "<div id=\"comments_act_" . $current_act->id . "\" class=\"activity_comments\" contentEditable=\"true\" onclick=\"if(this.textContent=='Añadir comentarios')this.innerHTML='';this.style.opacity='1';\" onblur='edit_act_field(\"" . $current_act->id . "\",\"comments\",\"" . $current_act->comments . "\",this.textContent);return false;' title=\"Comentarios\">" . $display_text . "</div>";
            } else {
                if ($current_act->comments != "") {
                    echo "<div id=\"comments_act_" . $current_act->id . "\" class=\"activity_comments\">" . $current_act->comments . "</div>";
                }
            }

            // Activity tags
            echo "<div id=\"tag_info\" style=\"margin-top:10px;margin-left:10px;\">";
                if ($act_user->id === $current_user->id) {
                    echo "<select id=\"equip\" onchange=\"update_tags(this.id, " . $record_id . ");\">";
                        echo "<option selected> Material: </option>";
                        if ($num_equip > 0) {
                            foreach ($user_equip as $key => $value) {   //$value is equipment's id
                                $equip_tmp = new Equipment(array('id' => $value));
                                $equip_tmp->getEquipmentData($conn);
                                $tmp_string = "";
                                if (in_array($record_id, $equip_tmp->used)) {
                                    $tmp_string = "disabled ";
                                }
                                echo "<option " . $tmp_string . " id='equip_" . $equip_tmp->id . "' value='equip_" . $equip_tmp->id . "'> " . $equip_tmp->name . " </option>";
                            }
                        }
                        echo "<option disabled> ** Añadir nuevo ** </option>";
                    echo "</select>";
                }
                    echo "<div id='user_equip' class=\"tag_style\">";
                        try {
                            if ($num_equip > 0) {
                                $tmp_delim = "";
                                foreach ($user_equip as $key => $equip_id) {   //$value is equipment's id
                                    $equip_tmp = new Equipment(array('id' => $equip_id));
                                    $equip_tmp->getEquipmentData($conn);
                                    if (in_array($record_id, $equip_tmp->used)) {
                                        echo "<div id=\"div_equip_" . $equip_id . "\" class=\"div_tag\">";
                                        // Only activity owners are entitled to delete tags
                                        $equip_delete_link = "";
                                        if ($act_user->id === $current_user->id) {
                                            $equip_delete_link = " <a href=\"javascript:void(0)\" onclick='removeItem(" . $equip_id . "," . $record_id . ",\"equip\");return false'><img src=\"/images/close-icon_16.png\" alt=\"Quitar\" title=\"Quitar\" align=\"absmiddle\"/></a>";
                                        }
                                        echo $equip_tmp->name . $equip_delete_link;
                                        echo "</div>";
                                    }
                                }
                            }
                        } catch (Exception $e) {
                            $log->error($e->getMessage());
                        }

                    echo "</div>";

                    if ($act_user->id === $current_user->id) {
                        echo "<select id=\"goals\" onchange=\"update_tags(this.id, " . $record_id . ");\">";
                        echo "<option selected> Objetivos: </option>";
                        try {
                            if ($num_goals > 0) {
                                foreach ($user_goals as $key => $goal) {
                                    $tmp_string = "";
                                    // Not displaying it if goal's date is older than current activity's date
                                    if ($goal->goal_date < $current_act->start_time) {
                                        $log->info($current_act->user_id . "|Not displaying goal " . $goal->id . ", older than activity " . $current_act->id);
                                    } else {
                                        $log->info($current_act->user_id . "|Goal " . $goal->id . " is newer than activity " . $current_act->id);
                                        // Retrieving records for goal to check if already registered
                                        $goal->getRecords($conn);
                                        if (in_array($record_id, $goal->activities)) {
                                            $log->info("Activity " . $current_act->id . " found in goal " . $goal->id . " list");
                                            $tmp_string = "disabled ";
                                        }
                                        $goal_name = $goal->name . " " . $goal->goal_date;
                                        echo "<option " . $tmp_string . " id='goal_" . $goal->id . "' value='goal_" . $goal->id . "'> " . $goal_name . " </option>";
                                    }
                                }
                            }
                        } catch (Exception $e) {
                            $log->error($current_act->user_id . "|" . $e->getMessage());
                        }
                        echo "<option disabled> ** Añadir nuevo ** </option>";
                    echo "</select>";
                    }
                    echo "<div id='user_goals' class=\"tag_style\">";
                        try {
                            if ($num_goals > 0) {
                                $tmp_delim = "";
                                foreach ($user_goals as $key => $goal) {
                                    // Retrieving records for goal to check if already registered
                                    $goal->getRecords($conn);
                                    if (in_array($record_id, $goal->activities)) {
                                        $goal_name = $goal->name . " " . $goal->goal_date;
                                        echo "<div id=\"div_goal_".$goal->id."\" class=\"div_tag\">";

                                        // Only activity owners are entitled to delete tags
                                        $goal_delete_link = "";
                                        if ($act_user->id === $current_user->id) {
                                            $goal_delete_link = " <a href=\"javascript:void(0)\" onclick='removeItem(".$goal->id.",".$record_id.",\"goal\");return false'><img src=\"/images/close-icon_16.png\" alt=\"Quitar\" title=\"Quitar\" align=\"absmiddle\"/></a>";
                                        }
                                        echo $goal_name . $goal_delete_link;
                                        echo "</div>";
                                    }
                                }
                            }
                        } catch (Exception $e) {
                            $log->error($e->getMessage());
                        }

                    echo "</div>";
                    if ($act_user->id === $current_user->id) {
                        echo "<select id=\"tags\" onchange=\"update_tags(this.id, " . $record_id . ");\">";
                            echo "<option selected> Etiquetas: </option>";
                            try {
                                if ($num_tags > 0) {
                                    foreach ($user_tags as $key => $tag) {
                                        $tmp_string = "";
                                        // Retrieving records for tag to check if already registered
                                        $tag->getRecords($conn);
                                        if (in_array($record_id, $tag->activities)) {
                                            $tmp_string = "disabled ";
                                            $log->info("Activity " . $current_act->id . " found in tag " . $tag->id . " list");
                                        }
                                        echo "<option " . $tmp_string . " id='tag_" . $tag->id . "' value='tag_" . $tag->id . "'> " . $tag->name . " </option>";
                                    }
                                }
                            } catch (Exception $e) {
                                $log->error($current_act->user_id . "|" . $e->getMessage());
                            }
                            echo "<option disabled> ** Añadir nueva ** </option>";
                        echo "</select>";
                    }
                    echo "<div id='user_tags' class=\"tag_style\">";
                        try {
                            if ($num_tags > 0) {
                                $tmp_delim = "";
                                foreach ($user_tags as $key => $tag) {
                                    // Retrieving records for tag to check if already registered
                                    $tag->getRecords($conn);
                                    if (in_array($record_id, $tag->activities)) {
                                        echo "<div id=\"div_tag_" . $tag->id . "\" class=\"div_tag\">";
                                        $tag_delete_link = "";
                                        if ($act_user->id === $current_user->id) {
                                            $tag_delete_link = " <a href=\"javascript:void(0)\" onclick='removeItem(".$tag->id.",".$record_id.",\"tag\");return false'><img src=\"/images/close-icon_16.png\" alt=\"Quitar\" title=\"Quitar\" align=\"absmiddle\"/></a>";
                                        }
                                        echo $tag->name . $tag_delete_link;
                                        echo "</div>";
                                    }
                                }
                            }
                        } catch (Exception $e) {
                            $log->error($current_act->user_id . "|" . $e->getMessage());
                        }

                    echo "</div>";
                echo "</div>";
        if ($num_laps > 0) {
            echo "<div id='info_" . $current_act->id . "' class='oculto'>";
            echo "<table class=\"simple\">";
            echo "<thead>";
                echo "<tr>";
                    echo "<th>Nº vuelta</th>";
                    echo "<th>Comienzo</th>";
                    echo "<th>Distancia</th>";
                    echo "<th>Duración</th>";
                    echo "<th>Ritmo</th>";
                    echo "<th>Pico ritmo</th>";
                    echo "<th>FCmin</th>";
                    echo "<th>FCmed</th>";
                    echo "<th>FCmáx</th>";
                    echo "<th>Calorías</th>";
                echo "</tr>";
            echo "</thead>";
            echo "<tbody>";
            for ($i=0; $i < $num_laps; $i++) {
                echo "<tr>";
                    $dateAndTime = Utils::getDateAndTimeFromDateTime ($current_act->laps[$i]["start_time"]);
                    $lap_number = $i+1;
                    echo "<td> #" . $lap_number . "</td>";
                    echo "<td>" . $dateAndTime['time'] . "</td>";
                    echo "<td>" . round($current_act->laps[$i]["distance"]) . "</td>";
                    echo "<td>" . Utils::formatMs($current_act->laps[$i]["duration"]) . "</td>";
                    echo "<td>" . Utils::formatPace($current_act->laps[$i]["pace"]) . "</td>";
                    // Lap's max_pace stored in DB in min/s format!!
                    $max_speed_kmh = $current_act->laps[$i]["max_pace"]*3.6; //now in km/h
                    $max_pace_dec = 60/$max_speed_kmh;
                    echo "<td>" . Utils::formatPace($max_pace_dec) . "</td>";
                    echo "<td>" . $current_act->laps[$i]["min_beats"] . "</td>";
                    echo "<td>" . round($current_act->laps[$i]["beats"]) . "</td>";
                    echo "<td>" . $current_act->laps[$i]["max_beats"] . "</td>";
                    echo "<td>" . $current_act->laps[$i]["calories"] . "</td>";
                echo "</tr>";
            }
            echo "</tbody>";
            echo "</table>";
            echo "</div>";
        }
?>
        <form action="/forms/formWorkout.php" id="delete_activity" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?php echo $current_act->id; ?>">
            <input type="hidden" name="start_time" value="<?php echo $current_act->start_time; ?>">
	    </form>
<?php
    echo "</div>";
    if ($act_user->id === $current_user->id) {
        $log->debug($current_act->user_id . "|Retrieving similar (" . $current_act->distance/1000 . "m +- 5%) activities to " . $current_act->id);
        $current_act->getSimilarDistance($current_act->distance, "0.05", $conn);
        echo "<div id='similar_activ' style=\"display:none;\">";
            if (count($current_act->similar) > 0) {
                echo "Actividades similares (en distancia):";
                echo "<table class=\"simple\">";
                    echo "<tr>";
                        echo "<th>Comienzo</th>";
                        echo "<th>Distancia</th>";
                        echo "<th>Duración</th>";
                        echo "<th>Ritmo</th>";
                        echo "<th>FCmed</th>";
                        echo "<th>Etiquetas</th>";
                    echo "</tr>";
                    foreach ($current_act->similar as $key => $sim_act) {
                        try {
                            $sim_act->getTags($conn);
                            $log->debug($current_act->user_id . "|Tags for similar activities " . $sim_act->id . ": " . json_encode($sim_act->tags));
                        } catch (Exception $e) {
                            $log->error($current_act->user_id . "|" . $e->getMessage());
                        }
                        echo "<tr>";
                            $dateAndTime = Utils::getDateAndTimeFromDateTime ($sim_act->start_time);
                            echo "<td><a href=\"activity.php?activity_id=" . $sim_act->id . "\" title=\"ver detalles\">" . $dateAndTime['date'] . " " . $dateAndTime['time'] . "</a></td>";
                            echo "<td>" . sprintf("%01.3f",round($sim_act->distance/1000)/1000) . "</td>";
                            echo "<td>" . Utils::formatMs($sim_act->duration) . "</td>";
                            echo "<td>" . Utils::formatPace($sim_act->pace) . "</td>";
                            echo "<td>" . round($sim_act->beats) . "</td>";
                            if (count($sim_act->tags)> 0) {
                                $act_tags_txt = "";
                                foreach ($sim_act->tags as $key => $act_tag) {
                                    if ($act_tags_txt === "") {
                                        $act_tags_txt .= $act_tag->name;
                                    } else {
                                        $act_tags_txt .= ", " . $act_tag->name;
                                    }
                                }
                                echo "<td style=\"border:0px;background-color:transparent\">" . $act_tags_txt . "</td>";
                            } else {
                                echo "<td style=\"border:0px;background-color:transparent\">No se han encontrado <a href=\"tags.php\">etiquetas</a></td>";
                            }
                        echo "</tr>";
                    }
                echo "</table>";
            } else {
                echo "No se han encontrado actividades similares para " . $_SESSION['login'];
            }
        echo "</div>";
    }
} else {
    echo "No hay más información para la entrada seleccionada";
}
?>
