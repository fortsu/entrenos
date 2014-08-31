<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/global_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/database.php';
use Entrenos\Activity;
use Entrenos\Utils\Utils;
use Entrenos\Utils\Parser\GPXPlusParser;

// Starting the session 
session_start();
if (isset($_SESSION['login'])) { 
    // Code for logged members 
    // Identifying user
    $user = $_SESSION['login'];
    $user_id = $_SESSION['user_id'];
    $user_dir = $_SESSION['user_data_path'];

    $data = $_REQUEST['data'];
    $num_act = $_REQUEST['num_act'];
    $log->info("Parsing data coming from GPS device | Activity: " . $num_act);
    $entry = json_decode($data, true);
    
    // Trying to debug high number of parsing errors
    $current_dt = new DateTime("now", new DateTimeZone("Europe/Madrid"));
    $debug_filename = $tmp_path . "/" . $user_id . "_" . $num_act . "_" . $current_dt->format('Ymd-His');
    $file_bytes = file_put_contents($debug_filename, $data);
    $log->debug("Saved " . $file_bytes . " | Filename: " . $debug_filename);

    // xmllint --format <filename>
    // xmllint --noout --schema TrainingCenterDatabasev2.xsd <tcx_file>
    //tcx files have invalid namespaces, so only loading Activities node
    $data_xml = simplexml_load_string($entry['xmlstring']);
    if ($data_xml) {
        // GPX+ formatted data saved in /tmp/gpx.out
        try {
            $tmp_data = Utils::XSLProcString ($data_xml->Activities->asXML(), $base_path . "/../transform/tcx2gpxplus.xsl");
            $parser = new GPXPlusParser();
            $log->info("Retrieving lap data...");
            $arrWorkout = $parser->getLaps($tmp_data);
            $log->info("Retrieving trackpoints data...");
            $gpx_trkpts = $parser->getPoints($tmp_data);
        } catch (Exception $e) {
            $log->error("Error when parsing new activity coming through Garmin Plugin | Error: " . $e->getMessage());
            echo json_encode(array("num_act" => $num_act, "status" => "KO"));
        }
        if($arrWorkout) {
            try {
                $log->info("Building activity");
                $tmp_activity = new Activity(array('user_id' => $user_id));
                $summary = $tmp_activity->get_summary($arrWorkout); 
                $extSummary = $tmp_activity->calculate_extended_summary($summary);
                if ($gpx_trkpts) {
                    list ($extSummary['upositive'], $extSummary['unegative']) = Utils::elevationTrkpts($gpx_trkpts);
                    $laps_minBeats = Utils::calculateMinBeats ($arrWorkout, $gpx_trkpts);
                    $log->debug("Min beats: " . json_encode($laps_minBeats));
                    for ($i=0; $i < count($arrWorkout); $i++) {
                        $arrWorkout[$i]['MinimumHeartRateBpm'] = $laps_minBeats[$i];
                    }
                } else {
                    list ($extSummary['upositive'], $extSummary['unegative']) = array(0, 0);
                }
                $log->debug("Ascent: " . $extSummary['upositive'] . " m | Descent: " . $extSummary['unegative'] . " m");
                
                // Build activity object and save it in DB
                $extSummary['user_id'] = $user_id;
                $activity = new Activity($extSummary);
                $activity->save_to_db($arrWorkout, $conn);
                $log->info("Activity " . $activity->id . " saved in database | User: " . $activity->user_id);

                // Ready to store xml file in filesystem
                $new_file = $user_dir . $activity->id;
                copy($tmp_data, $new_file);
                $log->info("Activity file " . $new_file . " | User: " . $activity->user_id);

                echo json_encode(array("num_act" => $num_act, "start_time" => $summary['start_time'], "status" => "OK"));
            } catch (Exception $e) {
                $log->error("Error when parsing new activity coming through Garmin Plugin | Error: " . $e->getMessage());
                echo json_encode(array("num_act" => $num_act, "status" => "KO"));
            }
        } else {
            $log->error("Error when parsing new activity coming through Garmin Plugin");
            echo json_encode(array("num_act" => $num_act, "status" => "KO"));
        }
    } else {
        $log->error("Data received seems not valid xml string");
        echo json_encode(array("num_act" => $num_act, "status" => "KO"));
    }
} else { 
    //Redirected guests to start page
    $log->error($_SERVER['REMOTE_ADDR'] . " | " . $_SERVER['HTTP_USER_AGENT'] . " | Guests trying to access " . $_SERVER['PHP_SELF']); 
   	header('Location: ' . $base_url); 
} 
?>
