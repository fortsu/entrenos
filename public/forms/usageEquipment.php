<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/global_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/database.php';
use Entrenos\Equipment;

session_start();
if (isset($_SESSION['user_id'])) { 
    $user_id = $_SESSION['user_id'];
    if (!empty($_GET)) {
        if (isset($_GET['equip_id']) && isset($_GET['record_id']) && isset($_GET['action'])) { 
            $equip_id = $_GET['equip_id'];
            $record_id = $_GET['record_id'];
            $action = $_GET['action'];

            $equip_unit = new Equipment(array ('id' => $equip_id, "user_id" => $user_id));
            try {
                switch ($action) {
                    case "add":
                        $equip_unit->addRecord($record_id, $conn);
                        $log->info("Adding record " . $record_id . " to equipment unit " . json_encode($equip_unit));
                        break;
                    case "remove":
                        $equip_unit->removeRecord($record_id, $conn);
                        $log->info("Removing record " . $record_id . " from equipment unit " . json_encode($equip_unit));
                        break;
                    default:
                        $log->error("Action not defined: " . $action);
                        break;
                }
                exit();
            } catch (Exception $e) {
                $log->error($e->getMessage());
                $equip_unit->removeRecord($user_id, $record_id, $conn);
            }
        } else {
            $log->error("Missing parameters in GET request: " . implode("|", $_GET));
            header("Location: " . $base_url . "/activity.php");
            exit();
        }
    } else {
        $log->error("GET request is empty");
        header("Location: " . $base_url . "/activity.php");
        exit();
    }
} else {
    $log->error("No user_id found in session. Redirecting to homepage");
    header("Location: " . $base_url);
    exit();
}
?>
