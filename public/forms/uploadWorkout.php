<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/global_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/logging.php';
use Entrenos\Activity;
use Entrenos\User;
use Entrenos\Utils\Utils;
use Entrenos\Utils\Parser\GPXPlusParser;

set_time_limit(120);
if (empty($_SESSION['user_data_path'])) {
    $current_user = new User(array('id'=> $_SESSION['user_id']));
    $current_user->userFromId($conn);
    $_SESSION['user_data_path'] = $base_path . $current_user->data_path; // BASE_PATH + /users/1/data/833
}
$uploaddir = $_SESSION['user_data_path'];

$numfiles = count($_FILES['userfiles']['name']);
// It will contain error messages from last action
$error_txt = "";
// Error messages will be stored in this array and (if not empty) passed in $_SESSION
$errors = array();
if ($numfiles > 0) {
    $log->info("Uploading " . $numfiles . " file/s to import | User: " . $_SESSION['user_id']);
} else {
    $error_txt = "No file available to import | User: " . $_SESSION['user_id'];
    $error_display = "El servidor no ha podido recibir fichero alguno: ¿demasiado grande?, ¿conexión lenta?.";
    $log->error($error_txt);
    $errors[] = $error_display;
}

for ($i=0; $i<$numfiles; $i++) {
    if($_FILES['userfiles']['error'][$i] == UPLOAD_ERR_OK) {
        try { 
            $log->info("File " . $_FILES['userfiles']['name'][$i] . " uploaded successfully | Size: " . $_FILES['userfiles']['size'][$i] . " | Type: " . $_FILES['userfiles']['type'][$i] . " | User: " . $_SESSION['user_id']);
            $tmp_file = $_FILES['userfiles']['tmp_name'][$i];
            $uploadfile = "";
                
            # Checking file extension to choose proper parser
            # ToDo: It would be better (more reliable but slower) to check xml's header
            $ext = pathinfo($_FILES['userfiles']['name'][$i], PATHINFO_EXTENSION);
            $parser = new GPXPlusParser();
            $tcx_content = array();
            $output = 0;

            if (strcasecmp($ext,"fit") == 0) {
                $log->debug("FIT file detected. Parsing source to get first TCX and then GPX+ format");
                $log->debug("Parsing from source to TCX... ");
                $log->debug("Temporary file: " . $_FILES['userfiles']['tmp_name'][$i]); 
                exec("/usr/bin/perl " . $base_path . "/../scripts/GarminFit/bin/fit2tcx ".$_FILES['userfiles']['tmp_name'][$i], $tcx_content, $output);
                $log->debug("Output: " . $output);
                //$log->debug("Returned: " . json_encode($tcx_content));
                if ($output === 0) {
                    // converting array of strings tcx_content in just 1 string
                    $tmp = simplexml_load_string(implode($tcx_content));
                    $log->debug("Parsing from TCX to GPX+... ");
                    $tmp_file = Utils::XSLProcString ($tmp->Activities->asXML(), $base_path . "/../transform/tcx2gpxplus.xsl");
                } else {
                    throw new Exception("Error when parsing " . $_FILES['userfiles']['tmp_name'][$i]);
                }
            }

            if (strcasecmp($ext,"tcx") == 0) { //tcx files have invalid namespaces, so only loading Activities node
                $log->debug("TCX file detected. Parsing source to get GPX+ format");                
                $tmp = simplexml_load_file($tmp_file);
                $tmp_file = Utils::XSLProcString ($tmp->Activities->asXML(), $base_path . "/../transform/tcx2gpxplus.xsl");
            }
            $log->info("Retrieving lap data...");
            $arrWorkout = $parser->getLaps($tmp_file);
            $log->info("Retrieving trackpoints data...");
            $gpx_trkpts = $parser->getPoints($tmp_file);
            $activity = new Activity(array("user_id"=>$_SESSION['user_id']));
            if ($arrWorkout) {
                $log->debug("File " . $_FILES['userfiles']['name'][$i] . " contains valid laps");
                $summary = $activity->get_summary($arrWorkout);
                $log->debug("Activity summary: " . json_encode($summary));
                $extSummary = $activity->calculate_extended_summary($summary);
                if ($gpx_trkpts) {
                    list ($extSummary['upositive'], $extSummary['unegative']) = Utils::elevationTrkpts($gpx_trkpts);
                    $laps_minBeats = Utils::calculateMinBeats ($arrWorkout, $gpx_trkpts);
                    $log->debug("Min beats: " . json_encode($laps_minBeats));
                    for ($j=0; $j < count($arrWorkout); $j++) {
                        $arrWorkout[$j]['MinimumHeartRateBpm'] = $laps_minBeats[$j];
                    }
                } else {
                    list ($extSummary['upositive'], $extSummary['unegative']) = array(0, 0);
                }
                $log->debug("Ascent: " . $extSummary['upositive'] . " m | Descent: " . $extSummary['unegative'] . " m");
            } else { // no laps are present, trying to retrieve data from track points (typically GPX file)
                $log->debug("File " . $_FILES['userfiles']['name'][$i] . " has no laps. Trying to retrieve information from trackpoints");
                if ($gpx_trkpts) { // calculate summary from trackpoints
                    $extSummary = $activity->get_extended_summary_from_trkpts($gpx_trkpts);
                } else {
                    $error_txt = "Fichero <b>" . $_FILES['userfiles']['name'][$i] . "</b> no tiene un formato xml (GPX+, TCX) válido";
                    $log->error($error_txt);
                    throw new Exception($error_txt);
                }
            }
            // With extSummary from activity, check if user has another one saved for same start time
            $extSummary['user_id'] = $_SESSION['user_id'];
            $log->debug("Activity extended summary: " . json_encode($extSummary));
            $activity = new Activity($extSummary);
            $already_exists = Activity::exists($conn, $activity->user_id, $extSummary['start_time']); //ToDo: get rid of summary!!
            if ($already_exists === FALSE) {
                $log->info("Marked activity from " . $extSummary['start_time'] . " to import | User: " . $activity->user_id);       
                $activity->save_to_db($arrWorkout, $conn);
                $log->info("Saved activity contained in " . $_FILES['userfiles']['name'][$i] . " as record " . $activity->id . " in DB | User: " . $activity->user_id);
            } else {
                $log->info("Activity from " . $extSummary['start_time'] . " already exists. Skipping | User: " . $activity->user_id);
                $error_txt = "La actividad <b>" . $extSummary['start_time'] . "</b> contenida en <b>" . $_FILES['userfiles']['name'][$i] . "</b> ya está registrada en FortSu";
                throw new Exception($error_txt);
            }
            // Activity saved in database, copying gpx
            $uploadfile = $uploaddir . $activity->id;
            // Really careful with directory permission, 777 is needed if no fine tuning present!!
            // Not using move_upload_file because in case of any transformation, PHP complains it is not a valid upload file. Using copy instead.
            if (!copy($tmp_file, $uploadfile)) {
                $error_msg = json_encode(error_get_last());
                $log->error("Error when copying " . $tmp_file . " to " . $uploadfile . " | error: " . $error_msg . " | User: " . $_SESSION['user_id']);
                try {
                    $activity->delete_from_db($conn);
                    $log->info("Removed all data related to activity " . $activity->id . " | Start time: " . $activity->start_time);
                    // Updating previous and next info for related activities
                    if ($activity->update_related("next_act", $activity->prev_act, $activity->next_act, $conn)) {
                        $log->info("Successfully updated next_act to " . $activity->next_act . " for activity " . $activity->prev_act);
                    } else {
                        $log->error("Error when updating next_act to " . $activity->next_act . " for activity " . $activity->prev_act . " | " . json_encode(error_get_last()));
                    }
                    if ($activity->update_related("prev_act", $activity->next_act, $activity->prev_act, $conn)) {
                        $log->info("Successfully updated prev_act to " . $activity->prev_act . " for activity " . $activity->next_act);
                    } else {
                        $log->error("Error when updating prev_act to " . $activity->prev_act . " for activity " . $activity->next_act . " | " . json_encode(error_get_last()));
                    }

                } catch (Exception $e) {
                    $log->error($e->getMessage());
                }      
                $error_txt = "No se ha podido importar la actividad " . $extSummary['start_time'] . " contenida en el fichero " . $_FILES['userfiles']['name'][$i];
                throw new Exception($error_txt);
            } else {
                $log->info("Succesfully copied " . $_FILES['userfiles']['name'][$i] . " to " . $uploadfile);
            }

        } catch (Exception $e) {
            $error_txt = $e->getMessage();
            $log->error($error_txt);
            $errors[] = $error_txt;
        }
    } else { // Check http://www.php.net/manual/en/features.file-upload.errors.php for error codes
        $error_txt = "Error when handling file " . $_FILES['userfiles']['name'][$i] . " | Error " . $_FILES['userfiles']['error'][$i];
        $error_display = "Se ha producido un error (código " . $_FILES['userfiles']['error'][$i] . ") al manipular el fichero " . $_FILES['userfiles']['name'][$i];
        $log->error($error_txt);
        $errors[] = $error_display;  
    }
// ToDo -> provide feedback to user about progress and results
}
if (count($errors) > 0) {
    $_SESSION['errors'] = $errors;
}
?>
