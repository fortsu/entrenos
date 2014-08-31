<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/global_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/database.php';
use Entrenos\Activity;
use Entrenos\Utils\Facebook;

$privacy = $_GET['privacy'];
$act_id = $_GET['act_id'];
session_start();
$user_id = $_SESSION['user_id'];

$current_act = new Activity(array("id" => $act_id, "user_id" => $user_id));
$current_act->getActivity($conn);
$log->debug("Activity: " . json_encode($current_act));
$msg = $current_act->stringSummary(FALSE); // summary in just one line
$log->debug("FB post: msg: " . $msg . " | privacy: " . $privacy . " | activity: " . $act_id . " | user: " . $user_id);
if ($msg != "No disponible") {
    $json_result = Facebook::postFBWall($conn, $user_id, $msg, $privacy);
    // If everything goes fine, it returns post id: {"id":"1276567060_1863497508624"}
    // If something fails: {"error":"blablabla"}
    $array_result = json_decode($json_result, true);
    if (array_key_exists('id',$array_result)) {
        $log->info("Succesfully posted on FB | Post id: " . $array_result['id']);
        $result = "OK |";
    } else {
        $log->error("Error when posting on FB | Error: " . $json_result);
        $result = "ERROR";
    }
} else {
    $log->error("No data summary available for activity" . $act_id);
    $result = "ERROR";
}
echo $result;
?>
