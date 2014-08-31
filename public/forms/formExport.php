<?php
// disable the PHP timelimit
ini_set('max_execution_time', 0);
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/global_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/database.php';
use Entrenos\Activity;
use Entrenos\User;
use Entrenos\Utils\Utils;

function transform_act($act_id, $user_id, $my_xsl, $dest) {
    global $log;
    global $base_path;
    $xml_file = $base_path . "/users/" . $user_id . "/data/" . $act_id;
    if (file_exists($xml_file)){
        $simplexml_tmp = simplexml_load_file($xml_file);
        // GPX+ formatted data saved in /tmp/gpx.out
        $tmp_gpx = Utils::XSLProcString ($simplexml_tmp->asXML(), $my_xsl);
        if (copy($tmp_gpx, $dest)) {
            $log->info($user_id . "|Successfully exported activity " . $act_id . " to file " . $dest);
        } else {
            throw new Exception ("Unable to copy " . $tmp_gpx . " to " . $dest . " | Error: " . json_encode(error_get_last()));
        }
    } else {
        throw new Exception ("File " . $xml_file . " not found, skipped");
    }
}

session_start();
if($_SESSION['user_id']) {
    $user_id = $_SESSION['user_id'];
    # Filling data with what it comes from the request
    $act = $_REQUEST;
    if(isset($_SERVER['HTTP_REFERER'])){
        $redirect = basename($_SERVER['HTTP_REFERER']);
        $log->info($user_id . " | Redirect set to " . $redirect);
    } else {
        $log->info($user_id . "HTTP_REFERER not present: " . $_SERVER['REMOTE_ADDR'] . " | " . $_SERVER['HTTP_USER_AGENT']); 
    }
    try {
        switch($act['id']) {
            case "all":
                if (!$redirect) {
                    $redirect = $base_url . "/user_settings.php";
                }
                $log->info($user_id . " | Request to export all activities in gpx format: " . implode("|", $act));
                if (class_exists("ZipArchive")) {
                    // retrieve from db all activities (id, start_time) from user
                    $current_user = new User(array("id"=>$user_id));
                    $current_user->userFromId($conn);
                    //array of activities; index is activitiy id and laps are retrieved when present
                    $user_act = $current_user->getAllActivities($conn, true, true);

                    // ToDo: retrieve ALL user data (equipment, goals, etc.)
                    // write activities data in text file
                    $user_act_db_file = $base_path . "/users/" . $current_user->id . "/data/act_db_" . date("Ymd_His") . ".txt";
                    $result_user_act_db = file_put_contents($user_act_db_file, json_encode($user_act));
                    if ($result_user_act_db === false) {
                        $log->error($current_user->id . "|Error when writing activities data to file " . $user_act_db_file);
                    } else {
                        $log->debug($current_user->id . "|Writing " . $result_user_act_db . " to file " . $user_act_db_file);
                    }

                    // exporting activities one by one
                    $exported_files = array();
                    foreach ($user_act as $index => $tmp_act) {
                        $log->info($current_user->id ."|Exporting activity " . $tmp_act->id . " in gpx format.");
                        $dateAndTime = Utils::getDateAndTimeFromDateTime($tmp_act->start_time);
                        $act_filename = $dateAndTime['date'] . "_" . str_replace(":","",$dateAndTime['time']);
                        $export_file = $base_path . "/users/" . $current_user->id . "/data/" . $act_filename . ".gpx";
                        try {
                            transform_act($tmp_act->id, $current_user->id, $base_path . "/../transform/gpxplus2gpx.xsl", $export_file);
                            $exported_files[] = $export_file;
                        } catch (Exception $e) {
                            $log->error($current_user->id . "|" . $e->getMessage());
                        }
                    }
                    // building compressed file
                    $exported_files[] = $user_act_db_file;
                    $export_file = $base_path . "/users/" . $current_user->id . "/data/" . $current_user->username . "_" . date("Ymd_His") . ".zip";
                    $log->info($current_user->id . "|Building compressed file " . $export_file);
                    if (Utils::create_zip($exported_files, $export_file)) {
                        header("Content-Type: application/zip");
                        header("Content-Disposition: attachment; filename=" . basename($export_file));
                    } else {
                        throw new Exception ("Error when creating compressed " . $export_file . " file");
                    }
                } else {
                    throw new Exception ("Class ZipArchive does not exist. Skipping export of files for user " . $user_id);
                }
                break;
            default:
                if (!$redirect) {
                    $redirect = $base_url . "/activity.php?activity_id=" . $act['id'];
                }
                $log->info("User " . $user_id . " requests to export activity " . $act['id'] . " in gpx format. Request: " . implode("|", $act));
                $export_file = $base_path . "/users/" . $user_id . "/data/" . $act['filename'] . ".gpx";
                transform_act($act['id'], $user_id, $base_path . "/../transform/gpxplus2gpx.xsl", $export_file);
                //http://stackoverflow.com/questions/1465573/forcing-to-download-a-file-using-php
                header("Content-Type: text/xml");
                header("Content-Disposition: attachment; filename=" . $act['filename'] . ".gpx");
        }
        header("Pragma: no-cache");
        readfile($export_file);
    } catch (Exception $e) {
        $log->error($e->getMessage());
        $_SESSION['errors'] = "Se ha producido un error al exportar las actividades. Inténtelo de nuevo más tarde";
        header("Location: " . $redirect);
    }
} else {
    //Redirected guests to start page
    $log->error($_SERVER['REMOTE_ADDR'] . " | " . $_SERVER['HTTP_USER_AGENT'] . " | Guests trying to access " . $_SERVER['PHP_SELF']); 
   	header('Location: ' . $base_url .  ' /index.php');    
}
exit();
?>
