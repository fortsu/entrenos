<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/global_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/database.php';

use Entrenos\User;
use Entrenos\Utils\Utils;
use Entrenos\Utils\Search;

session_start();
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    # Filling data with what it comes from the request
    $form_data = $_REQUEST;
    if ($form_data['user_id'] == $user_id) {
        try {
            $log->debug("Filtering search results for user " . $user_id . ": " . implode("|", $form_data));
            if (empty($form_data['json_search_params'])) { // changes in search results
                if (!empty($form_data['remove'])) {
                    $filter_params = explode("|",$form_data['remove']); //tags, goals, equipment -> goal_3|equip_2 - ToDo -> JSON migration!!
                    $tag_filter = array();
                    $goal_filter = array();
                    $equip_filter = array();
                    foreach ($filter_params as $index => $element) {
                        list($key, $id) = explode("_",$element);
                        switch ($key) {
                            case "tag":
                                $tag_filter[] = $id;
                                break;
                            case "goal":
                                $goal_filter[] = $id;
                                break;
                            case "equip":
                                $equip_filter[] = $id;
                                break;
                            default:
                                $log->error("Filter key " . $key . " not identified, skipping " . $element);
                        }
                    }
                } else {
                    $tag_filter = null;
                    $goal_filter = null;
                    $equip_filter = null;
                }
                //searchFilter.php?user_id=1&remove=&refine=0|9;15|16585;380|last_month
                $refine_tmp = explode("|",$form_data['refine']); // distances, paces, dates -> 9;15|15060;315 (bug in the first pace figure!)
                $refine_sport_id = $refine_tmp[0];
                $refine_dist = explode(";", $refine_tmp[1]); // distance in km
                $refine_pace = explode(";", $refine_tmp[2]);
                $refine_date = $refine_tmp[3];

                // depending on sport, pace (seconds/km) or speed (km/h)
                if ($refine_sport_id == 0) {
                    // Converting seconds to dbpace
                    $min_pace = Utils::seconds2dbpace (Utils::checkPaceSeconds($refine_pace[0]));
                    $max_pace = Utils::seconds2dbpace (Utils::checkPaceSeconds($refine_pace[1]));
                } else {
                    $min_pace = $refine_pace[0];
                    $max_pace = $refine_pace[1];
                }
                // Applying distance and pace filter from the beginning - 2010.06.22/3
                $search_filter = array('min_dist' => $refine_dist[0]*1000000,
                                       'max_dist' => $refine_dist[1]*1000000,
                                       'min_pace' => $min_pace,
                                       'max_pace' => $max_pace,
                                       'date' => $refine_date,
                                       'sport_id' => $refine_sport_id,
                                       'tags' => $tag_filter,
                                       'goals' => $goal_filter,
                                       'equip' => $equip_filter,
                                       'user_id' => $user_id);
                if ($refine_sport_id == 1 or $refine_sport_id == 3) {
                    $search_filter["min_speed"] = $search_filter["min_pace"];
                    unset($search_filter["min_pace"]);
                    $search_filter["max_speed"] = $search_filter["max_pace"];
                    unset($search_filter["max_pace"]);
                }   
            } else { // just browsing search results
                $search_filter = json_decode($form_data['json_search_params'],TRUE);
            }
            try {
                $current_user = new User(array('id'=>$user_id));
                $current_search = new Search($search_filter);
                $workouts = $current_search->filter($conn);
                if (!isset($current_search->step)) {
                    $current_search->step = 0;
                    $current_search->num_display = 15;
                    $current_search->total_results = count($workouts);
                }
                $log->info("Retrieving " . count($workouts) . " activities for user " . $current_user->id . ": " . json_encode($current_search));
            } catch (Exception $e) {
                echo "Error al recuperar las actividades del usuario";
                $log->error($e->getMessage());
            }
            if ($current_search->total_results > 0) {
                include "./display_search_results.php";
            } else {
                echo "No se han encontrado actividades. Revise los filtros de búsqueda";
            }

        } catch (Exception $e) {
            $log->error($e->getMessage());
        }
    } else { 
        echo "Hubo un error al procesar la petición";
        $log->error("User_id en sesión: " . $user_id . " | Request: " . json_encode($form_data));
    }

} else { 
    //Redirected guests to start page
    $log->error($_SERVER['REMOTE_ADDR'] . " | " . $_SERVER['HTTP_USER_AGENT'] . " | Guests trying to access " . $_SERVER['PHP_SELF']); 
   	header('Location: index.php'); 
} 
exit();
?>
