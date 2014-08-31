<?php

use Entrenos\Utils\Utils;

            // Calculating first, previous, next and last steps
            $step_start = $current_search->step*$current_search->num_display + 1;
            $step_end = $step_start + $current_search->num_display - 1;
            $first_step = 0;
            $last_step = floor(($current_search->total_results - 1)/$current_search->num_display);

            if ($current_search->step == 0) {
                $first_link = "";
                $prev_link = "";
                if ($last_step > 0) { // num results bigger than num display
                    $next_search = clone $current_search;
                    $next_search->step += 1;
                    $last_search = clone $current_search;
                    $last_search->step = $last_step;
                    $next_link = " <a href=\"javascript:void(0)\" onclick='updateSearch2(" . $current_user->id . "," . json_encode($next_search) . ")'>></a>";
                    $last_link = " <a href=\"javascript:void(0)\" onclick='updateSearch2(" . $current_user->id . "," . json_encode($last_search) . ")'>>></a>";
                } else {
                    $next_link = "";
                    $last_link = "";
                    $step_end = $current_search->total_results;
                }
            } else if ($current_search->step == $last_step) { // last one
                $first_search = clone $current_search;
                $first_search->step = 0;
                $prev_search = clone $current_search;
                $prev_search->step -= 1;
                $first_link = " <a href=\"javascript:void(0)\" onclick='updateSearch2(" . $current_user->id . "," . json_encode($first_search) . ")'><<</a>";
                $prev_link = " <a href=\"javascript:void(0)\" onclick='updateSearch2(" . $current_user->id . "," . json_encode($prev_search) . ")'><</a>";
                $next_link = "";
                $last_link = "";
                if ($step_end > $current_search->total_results) {
                    $step_end = $current_search->total_results;
                }
            } else {
                $first_search = clone $current_search;
                $first_search->step = 0;
                $prev_search = clone $current_search;
                $prev_search->step -= 1;
                $next_search = clone $current_search;
                $next_search->step += 1;
                $last_search = clone $current_search;
                $last_search->step = $last_step;
                $first_link = " <a href=\"javascript:void(0)\" onclick='updateSearch2(" . $current_user->id . "," . json_encode($first_search) . ")'><<</a>";
                $prev_link = " <a href=\"javascript:void(0)\" onclick='updateSearch2(" . $current_user->id . "," . json_encode($prev_search) . ")'><</a>";
                $next_link = "<a href=\"javascript:void(0)\" onclick='updateSearch2(" . $current_user->id . "," . json_encode($next_search) . ")'>></a>";
                $last_link = "<a href=\"javascript:void(0)\" onclick='updateSearch2(" . $current_user->id . "," . json_encode($last_search) . ")'>>></a>";
            }
            echo $first_link . " " . $prev_link . " Mostrando resultados " . $step_start . " al " . $step_end . " de " . $current_search->total_results . " " . " " . $next_link . " " . $last_link;
            echo "<table class=\"simple\">";
                echo "<thead>";
                    echo "<tr>";
                        echo "<th>Comienzo</th>";
                        echo "<th>Distancia</th>";
                        echo "<th>Duración</th>";
                        if ($current_search->sport_id == 0) {
                            echo "<th>Ritmo</th>";
                        } else {
                            echo "<th>Veloc</th>";
                        }
                        echo "<th>FCmed</th>";
                    echo "</tr>";
                echo "</thead>";
                $num_act = count($workouts);
                if ($num_act < $current_search->num_display) {
                    $current_search->num_display = $num_act;
                }
                $log->info("Displaying " . $current_search->num_display . " activities out of " . $current_search->total_results . " for user " . $current_user->id, 0);
                $workouts_tmp = array_slice($workouts, 0, $current_search->num_display);
                foreach ($workouts_tmp as $index => $element) {                   
                    echo "<tr>";                        
                        $dateAndTime = Utils::getDateAndTimeFromDateTime ($element["start_time"]);
                        echo "<td><a href=\"activity.php?activity_id=" . $element['id'] . "\">" . $dateAndTime['date'] . " " . $dateAndTime['time'] . "</a></td>";
                        echo "<td>" . sprintf("%01.3f",round($element["distance"]/1000)/1000) . "</td>";
                        echo "<td>" . Utils::formatMs($element["duration"]) . "</td>";
                        if ($current_search->sport_id == 0) {
                            echo "<td>" . Utils::formatPace($element["pace"]) . "</td>";
                        } else {
                            echo "<td>" . number_format($element["speed"],2) . "</td>";
                        }
                        echo "<td>" . round($element["beats"]) . "</td>";
                    echo "</tr>";
                }
            echo "</table>";
            echo "<a href=\"search.php\">Limpiar búsqueda</a>";
?>
