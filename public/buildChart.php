<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/global_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/database.php';
//require_once $base_path . '/check_access.php';

use Entrenos\Activity;
use Entrenos\Charts;

session_start();

if (isset($_SESSION['user_id'])) { 
    $user_id = $_SESSION['user_id'];
    if (!empty($_GET)) {
        if (isset($_GET['action']) && isset($_GET['week']) && isset($_GET['month']) && isset($_GET['year'])) { 
            $week = $_GET['week'];
            $month = $_GET['month'];
            $year = $_GET['year'];
            $action = $_GET['action'];
            try {
                $data = array();
                switch ($action) {
                    case "W":
                        $data = Activity::getKmDaysWeek($conn, $user_id, $week, $year);
                        $log->info("Building activity chart: week #" . $week . " of " . $year . " for user " . $user_id . " | " . json_encode($data));
                        break;
                    case "M":
                        $data = Activity::getKmDaysMonth($conn, $user_id, $month, $year);
                        $log->info("Building activity chart: month #" . $month . " of " . $year . " for user " . $user_id . " | " . json_encode($data));
                        break;
                    case "Y":
                        $data = Activity::getKmMonthsYear($conn, $user_id, $year);
                        $log->info("Building activity chart: year " . $year . " for user " . $user_id . " | " . json_encode($data));
                        break;
                    default:
                        $log->error("Action not defined: " . $action, 0);
                        break;
                }
                Charts::barChart2($data, $action);
            } catch (Exception $e) {
                $log->error($e->getMessage());
                if (!empty($equip_unit)) {
                    $equip_unit->removeRecord($user_id, $record_id, $conn);
                }
            }
        } else {
            $log->error("Missing parameters in GET request: " . implode("|", $_GET));
            header("Location: " . $base_url . "/calendar.php");
            exit();
        }
    } else {
        $log->error("GET request is empty");
        header("Location: " . $base_url . "/calendar.php");
        exit();
    }
} else {
    $log->error("No user_id in session. Redirecting to homepage");
    header("Location: " . $base_url);
    exit();
}
?>
