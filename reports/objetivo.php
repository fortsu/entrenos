<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/global_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/database.php';
use Entrenos\Goal;

$script_name = pathinfo(__FILE__, PATHINFO_FILENAME);
if ($script_name == "objetivo") {
    error_log("Goal report generator must be customized: " . __FILE__ , 0);
    exit();
} else {
    // __DIR__ is <base_dir>/users/<user_id>/reports
    list($file_type, $object, $goal_id) = explode("_", $script_name); // report_goal_<goal_id>
    if ($goal_id) {
        $goal = new Goal(array('id' => $goal_id));
        $goal->getGoalData($conn);
        $goal->last_report = "goal_" . $goal->id . "_" . date("Y-m-d") . ".png";
        $img_path = __DIR__ . "/" . $goal->last_report;
        if (file_exists($img_path)) {
            header("Content-Type: image/png");
            readfile($img_path);
        } else {
            $log->info("Creating report for goal #" . $goal->id . " | File: " . $goal->last_report . " | User: " . $goal->user_id);
            try {
                $ttf = $base_path . "/fonts/Ubuntu/UbuntuMono-R.ttf";
                $goal->imgGoalReport($img_path, $ttf, $conn);
                header("Content-Type: image/png");
                readfile($img_path);
                // saving report filename
                $goal->update_last_report($conn);
                $log->info("Saved last report's filename in db: " . $goal->last_report);
            } catch (Exception $e) {
                $log->error($e->getMessage());
            }
        }
    } else {
        $log->error("No goal_id provided: " . __FILE__);
    }
}
?>
