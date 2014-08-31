<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/global_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/database.php';
use Entrenos\Activity;
use Entrenos\Sport;
use Entrenos\Utils\Utils;

session_start();
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    # Filling data with what it comes from the request
    $form_data = $_REQUEST; 
    // Normal redirection
    $redirect = $base_url . "/calendar.php";
    try {
        switch($form_data['action']) {
            case "upload":
                require_once $base_path . "/forms/uploadWorkout.php";
                $redirect =  $base_url . "/calendar.php";
                break;
            case "manual":
                // TODO: validate user input, never trust data!
                $log->info($user_id . "|Building extended summary");
                $log->info($user_id . "|Received: " . json_encode($_REQUEST));
                # Filling data with what it comes from the request
                $extSummary = $_REQUEST;
                // Either distance or duration provided must be a positive value
                $initial_check = $extSummary['distance'] + $extSummary['duration'];
                if ($initial_check > 0) {
                    # Building start time 2010-08-02 17:35:50 (mysql datetime format)
                    if ($extSummary['time'] === "Hora (hh:mm)" || empty($extSummary['time'])) {
                        $log->info($user_id . "|No proper time provided, set to 12:15");
                        $extSummary['time'] = "12:15:00";   
                    }
                    $tmp_start_time = $extSummary['date'] . " " . $extSummary['time'];
                    $extSummary['start_time'] = date('Y-m-d H:i:s', strtotime($tmp_start_time));
                    # Calculate pace, and transform speed and max_speed
                    list($hours, $mins, $secs) = explode(":", $extSummary['duration']);
                    $extSummary['duration'] = 1000*($secs + 60*$mins + 3600*$hours); // db figures in ms
                    // ToDo: save pace in seconds/km -> need migration script!
                    $extSummary['pace'] = $extSummary['duration']/(60*$extSummary['distance']); // elapsedTime in ms, distance in m -> X.YZ
                    $extSummary['speed'] = 60/$extSummary['pace']; // pace in seconds/km
                    // max_pace comes in format mm:ss -> transform first to db format
                    // html 5 accepts placeholder but not all browsers. Until then, double check max_pace to avoid division by zero
                    if (!empty($extSummary['max_pace'])) {
                        list($mins_max, $secs_max) = explode(":",$extSummary['max_pace']);
                        $maxPace_secs = $mins_max*60 + $secs_max;
                        $extSummary['max_pace'] = Utils::seconds2dbpace($maxPace_secs);
                        if ($extSummary['max_pace'] > 0) {
                            $extSummary['max_speed'] = 60/$extSummary['max_pace'];
                        } else {
                            $extSummary['max_speed'] = 0;
                        }
                    }
                    $extSummary['distance'] = $extSummary['distance'] * 1000; // database figures in mm
                    $extSummary['user_id'] = $user_id;
                    // Setting sport_id depending on pace
                    // Slow paces can be walking (2, default for slow pace) or swimming (3)
                    if (intval($extSummary['sport_id']) < Sport::check($extSummary['pace'])) {
                        $log->info("Received sport_id " . $extSummary['sport_id'] . " but pace (" . $extSummary['pace'] . ") does not match. Forcing sport_id to " . Sport::check($extSummary['pace']));
                        $extSummary['sport_id'] = Sport::check($extSummary['pace']);
                    }
                    if ($extSummary["comments"] === "Comentarios") {
                        $extSummary["comments"] = "";
                    }
                    $log->info($user_id . "|Saving into DB: " . json_encode($extSummary));
                    $current_act = new Activity($extSummary);
                    try { 
                        $current_act->save_to_db(false, $conn);
                        $redirect = $base_url . "/activity.php?activity_id=" . $current_act->id;
                    } catch (Exception $e) {
                        $log->error($e->getMessage());
                        // lanzar excepción
                    }
                } else {
                    $log->error("At least distance (" . $extSummary['distance'] . ") or duration (" . $extSummary['duration'] . ") must be valid values");
                    $_SESSION['errors'][] = "Los datos introducidos en la entrada manual no son válidos, revíselos por favor";
                    header ("Location: " . $base_url . "/calendar.php");
                    exit;
                }
                break;
            case "delete":
                try {
                    $act = new Activity(array("id" => $_REQUEST['id'], "user_id" => $user_id, "start_time" => $_REQUEST['start_time']));
                    $act->getActivity($conn, false);                
                    $act->delete_from_db($conn);
                    $log->info($act->user_id . "|Deleted activity from DB - " . implode("|", get_object_vars($act)));
                    // Updating previous and next info for related activities
                    if ($act->update_related("next_act", $act->prev_act, $act->next_act, $conn)) {
                        $log->info($act->user_id . "|Successfully updated next_act to " . $act->next_act . " for activity " . $act->prev_act);
                    } else {
                        $log->error($act->user_id . "|Error when updating next_act to " . $act->next_act . " for activity " . $act->prev_act . " | " . json_encode(error_get_last()));
                    }
                    if ($act->update_related("prev_act", $act->next_act, $act->prev_act, $conn)) {
                        $log->info($act->user_id . "|Successfully updated prev_act to " . $act->prev_act . " for activity " . $act->next_act);
                    } else {
                        $log->error($act->user_id . "|Error when updating prev_act to " . $act->prev_act . " for activity " . $act->next_act . " | " . json_encode(error_get_last()));
                    }       

                    // removing file from user's space
                    $file_path = $base_path . "/users/" . $act->user_id . "/data/" . $act->id;
                    if (file_exists($file_path)) {
                        if (unlink($file_path)) {
                            $log->info($act->user_id . "|Succesfully removed file: " . $file_path);
                            $msg_txt = "Se ha eliminado la entrada de " . $act->start_time;
                            $_SESSION['msg'] = $msg_txt;
                            $redirect = $base_url . "/calendar.php";
                        } else {
                            $log->error($act->user_id . "|Error when removing file: " . implode("|",error_get_last()));
                        }
                    } else {
                        $log->error("File " . $file_path . " does not exist, nothing to delete");
                    }
                } catch (Exception $e) {
                    $log->error($act->user_id . "|Error when deleting activity " . $act->id . " | " . $e->getMessage());
                    // lanzar excepción
                }

                break;
            case "preview":
                $act = new Activity(array("id"=>$_REQUEST['act_id'], "user_id"=>$user_id));                
                $act->getActivity($conn);
                $act_summary = $act->stringSummary(TRUE); // adds new line in the middle to fit box
                echo $act_summary;
                $log->debug($act->user_id ."|Preview activity " . $act->id . ": " . json_encode($act_summary));
                exit();
                break;
            case "change_visibility":
                $result = array();
                $act = new Activity(array("id" => $_REQUEST['act_id'], "user_id" => $user_id));                
                $act->getActivity($conn);
                if ($user_id === $act->user_id) {
                    if ($act->changeVisibility($_REQUEST['next_status'], $conn)) {
                        $log->info($act->user_id ."|Visibility for activity " . $act->id. " successfully changed to " . $_REQUEST['next_status']);
                        $result["success"] = "Visibilidad de la actividad cambiada correctamente";
                    } else {
                        $log->error($act->user_id . "|Failed when changing activity " . $act->id. " visibility. Error: " . json_encode($conn->errorInfo()));
                        $result["error"] = "Se produjo un error al cambiar la visibilidad de la propiedad. Inténtelo de nuevo más tarde";
                    }    
                } else {
                    $log->error("User " . $_REQUEST['user_id'] . " trying to change visibility for activity " . $act->id. " from user " . $act->user_id);
                    $result["error"] = "Solamente el propietario puede cambiar la visibilidad de la actividad";
                }
                echo json_encode($result);
                exit();
                break;
            case "update_field":
                $act = new Activity(array("id"=>$_REQUEST['act_id'], "user_id"=>$user_id));
                if ($act->update_prop($_REQUEST['act_field'], $_REQUEST['new_value'], $conn)){
                    // when updating distance and duration fields we also save old values in proper fields
                    if ($_REQUEST['act_field'] == "duration" or $_REQUEST['act_field'] == "distance") {
                        $act_field = $_REQUEST['act_field'] . "_old";
                        if ($act->update_prop($act_field, $_REQUEST['old_value'], $conn)){
                            $log->info($act->user_id . "|Successfully updated " . $act_field . ": " . $_REQUEST['old_value']);
                        } else {
                            $log->error($act->user_id . "|Failed when updating activity " . $act->id. "'s " . $act_field . " | Error: " . json_encode($conn->errorInfo()));
                        }
                    }
                    echo $_REQUEST['new_value'];
                    $log->info($act->user_id . "|Successfully updated " . $_REQUEST['act_field'] . ": " . json_encode($_REQUEST) . " | User: " . $user_id);
                } else {
                    $log->error($act->user_id . "|Failed when changing activity " . $act->id. "'s " . $_REQUEST['act_field'] . " | User: " . $user_id . " | Error: " . json_encode($conn->errorInfo()));
                    header('HTTP/1.1 500 Internal Server Error');
                }
                exit();
                break;
            case "update_avgs":
                // either distance or duration changed, so we need to update speed and pace
                $act = new Activity(array("id"=>$_REQUEST['act_id'], "user_id"=>$user_id));
                // retrieve current values withput laps data
                $act->getActivity($conn, false);
                // calculate new average values
                // we look for min/km: 1000/60 -> (time * 50) / (distance * 3)
                if ($act->distance > 0) {
                    $new_pace = ($act->duration * 50) / ($act->distance * 3);
                    // from min/km to km/h -> 
                    $new_speed = 60 / $new_pace;
                } else {
                    $new_pace = 0;
                    $new_speed = 0;
                }
                // update values stored in database
                if ($act->update_prop("pace", $new_pace, $conn)){
                    $log->info($act->user_id . "|Successfully updated pace: " . $new_pace);
                } else {
                    $log->error($act->user_id . "|Failed when updating activity " . $act->id. "'s pace | " . json_encode($conn->errorInfo()));
                }
                if ($act->update_prop("speed", $new_speed, $conn)){
                    $log->info($act->user_id . "|Successfully updated speed: " . $new_speed);
                } else {
                    $log->error($act->user_id . "|Failed when updating activity " . $act->id. "'s speed | " . json_encode($conn->errorInfo()));
                }
                // initially intended to display updated pace in activity page
                echo json_encode(array("pace" => $new_pace, "speed" => $new_speed));
                exit();
                break;
            default:
                $log->error($act->user_id . "|Action not recognized: " . implode("|", $form_data));
        }
    } catch (Exception $e) {
        $log->error($e->getMessage());
    }
    header ("Location: " . $redirect);
} else {
    //Redirected guests to start page
    $log->error($_SERVER['REMOTE_ADDR'] . " | " . $_SERVER['HTTP_USER_AGENT'] . " | Guests trying to access " . $_SERVER['PHP_SELF']); 
   	header('Location: ' . $base_url);   
}

exit();
?>
