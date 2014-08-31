<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/global_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/database.php';
use Entrenos\Athlete;
use Entrenos\Goal;
use Entrenos\Tag;

session_start();
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    # Filling data with what it comes from the request
    $form_data = $_REQUEST; 
    // Normal redirection will go to charts.php
    $redirection = "Location: " . $base_url . "/charts.php";
    try {
        switch($form_data['action']) {
            case "personal_data": 
                //Checking if weight decimal separator is comma and changing it if applies
                $fields2check = array('weight','body_fat','body_water','body_muscle');
                foreach ($fields2check as $loop_field) {
                    if (isset($form_data[$loop_field]) and !empty($form_data[$loop_field])) {
                        $form_data[$loop_field] = str_replace(",", ".", $form_data[$loop_field]);
                    } else {
                        $form_data[$loop_field] = 0;
                    }
                }
                $athlete = new Athlete($user_id); 
                $athlete->insert($form_data, $conn);
                $log->info("Updated athlete data for user " . $user_id . ": " . implode("|", $form_data));
                break;
            case "goal":
                if (!isset($form_data['report_enabled']))
                    $form_data['report_enabled'] = 0;
                $goal = new Goal($form_data);
                try {
                    $goal->insert($conn);
                    $log->info("Updated goal data " . $goal->id . " for user " . $user_id . ": " . implode("|", $form_data));
                    //copying report generator file to final destination
                    if ($form_data['report_enabled']) {
                        $source_goal_report_path = $base_path . "/../reports/objetivo.php";
                        $target_goal_report_path = $base_path . "/" . $goal->report_url;
                        if (!copy($source_goal_report_path, $target_goal_report_path)) {
                            throw new Exception("Error when copying goal report: " . json_encode(error_get_last()) . " | Goal: " . json_encode($goal));
                        } else {
                            $log->info("Goal reporting script copied to " . $target_goal_report_path);
                        }
                    } else {
                        $log->info("Reporting not enabled for goal #" . $goal->id);
                    }
                } catch (Exception $e) {
                    $log->error($e->getMessage());
                }
                $redirection = "Location: " . $base_url . "/goals.php";
                break;
            case "tag":
                if (!isset($form_data['report_enabled']))
                    $form_data['report_enabled'] = 0;
                $tag = new Tag($form_data);
                try {
                    $tag->insert($conn);
                    $log->info("Updated tag data " . $tag->id . " for user " . $user_id . ": " . implode("|", $form_data));
                    //ToDo: copy report generator file to final destination
                } catch (Exception $e) {
                    $log->error($e->getMessage(),0);
                }
                $redirection = "Location: " . $base_url . "/tags.php";
                break;
            case "remove_history":
                $athlete = new Athlete($user_id); 
                $athlete->remove($form_data['entry'], $conn);
                $log->info("Removing entry " . $form_data['entry'] . " from athlete " . $user_id . ": " . implode("|", $form_data));
                break;
            case "remove_tag":
                $tag = new Tag(array('user_id'=>$user_id)); //only owner can remove his/her tags 
                $tag->remove($form_data['entry'], $conn);
                $log->info("Removing tag " . $form_data['entry'] . " from athlete " . $user_id . ": " . implode("|", $form_data));
                $redirection = "Location: " . $base_url . "/tags.php";
                break;
            case "remove_goal":
                $goal = new Goal(array('id'=>$form_data['entry'],'user_id'=>$user_id)); //only owner can remove his/her goals
                $goal->remove($conn);
                $log->info("Removed goal " . $goal->id . " from DB (user #" . $goal->user_id . ")");
                $report_url = $base_path . "/users/" . $goal->user_id . "/reports/report_goal_" . $goal->id . ".php";
                if(unlink($report_url)) {
                    $log->info("Removed report from " . $report_url . " | User #" . $goal->user_id);
                } else {
                    $log->error("Not possible to remove report file. Error: " . json_encode(error_get_last()));
                }
                $redirection = "Location: " . $base_url . "/goals.php";
                break;
            case "link_tag":
                $tag = new Tag(array('id'=>$form_data['item_id'],'user_id'=>$user_id));
                $tag->linkRecord($form_data['record_id'], $conn);
                $log->info("Linking record " . $form_data['record_id'] . " with tag " . $tag->id . ": " . implode("|", $form_data));
                exit(); //no need to redirect
                break;
            case "link_goal":
                $goal = new Goal(array('id'=>$form_data['item_id'],'user_id'=>$user_id));
                $goal->linkRecord($form_data['record_id'], $conn);
                $log->info("Linking record " . $form_data['record_id'] . " with goal " . $goal->id . ": " . implode("|", $form_data));
                // removing image report if tag is goal
                $goal->getGoalData($conn);
                if ($goal->report_enabled > 0) {
                    try {
                        $goal->removeImgReport($conn);
                        $log->info("Successfully removed last report: " . $goal->last_report . " | Goal: " . json_encode($goal));
                    } catch (Exception $e) {
                        $log->error($e->getMessage());
                    }
                } else {
                    $log->info("No need to remove report for goal #" . $goal->id . ": not enabled");
                }
                exit(); //no need to redirect
                break;
            case "unlink_tag":
                $tag = new Tag(array('id'=>$form_data['item_id'],'user_id'=>$user_id));
                $tag->unlinkRecord($form_data['record_id'], $conn);
                $log->info("Unlinking record " . $form_data['record_id'] . " with tag " . $tag->id . ": " . implode("|", $form_data));
                exit(); //no need to redirect
                break;
            case "unlink_goal":
                $goal = new Goal(array('id'=>$form_data['item_id'],'user_id'=>$user_id));
                $goal->unlinkRecord($form_data['record_id'], $conn);
                $log->info("Unlinking record " . $form_data['record_id'] . " with goal " . $goal->id . ": " . implode("|", $form_data));
                // removing image report if tag is goal
                $goal->getGoalData($conn);
                if ($goal->report_enabled > 0) {
                    try {
                        $goal->removeImgReport($conn);
                        $log->info("Last report file " . $goal->last_report . " no longer exists | Goal: " . json_encode($goal));
                    } catch (Exception $e) {
                        $log->error($e->getMessage());
                    }
                } else {
                    $log->info("No need to remove report for goal #" . $goal->id . ": not enabled");
                }
                exit(); //no need to redirect
                break;
            default:
                $log->error("Action not recognized: " . implode("|", $form_data));
        }
    } catch (Exception $e) {
        $log->error($e->getMessage());
    }

    header($redirection);

} else { 
    //Redirected guests to start page
    $log->error($_SERVER['REMOTE_ADDR'] . " | " . $_SERVER['HTTP_USER_AGENT'] . " | Guests trying to access " . $_SERVER['PHP_SELF']); 
   	header("Location: " . $base_url . "/index.php"); 
} 
exit();
?>
