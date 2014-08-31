<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/global_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/database.php';
use Entrenos\Equipment;

session_start();
if($_SESSION['user_id']) {
    # Filling data with what it comes from the request
    $form_data = $_REQUEST;
    $form_data["user_id"] = $_SESSION['user_id'];
    $redirection = $base_url . "/equipment.php";
    try {
        switch($form_data['action']) {
            case "update_equip":
                $equip = new Equipment(array("id"=>$_REQUEST['equip_id'], "user_id"=>$form_data["user_id"]));
                if ($equip->update_prop($_REQUEST['equip_field'], $_REQUEST['new_value'], $conn)){
                    echo $_REQUEST['new_value'];
                    $log->info("Successfully updated " . $_REQUEST['equip_field'] . ": " . json_encode($_REQUEST) . " | User: " . $equip->user_id);
                } else {
                    $log->error("Failed when changing activity ".$equip->id."'s " . $_REQUEST['equip_field'] . " | User: " . $equip->user_id . " | Error: " . json_encode($conn->errorInfo()));
                    header('HTTP/1.1 500 Internal Server Error');
                }
                exit(); // Avoids redirection (ajax based)
                break;
            case "remove_equip":
                $equip = new Equipment(array("id"=>$_REQUEST['equip_id'], "user_id"=>$form_data["user_id"]));
                try {
                    $equip->remove($conn);
                    $log->info("Successfully removed equipment " . $equip->id . " from user " . $equip->user_id);
                } catch (Exception $e) {
                    $log->error($e->getMessage());
                    $redirection = "error.php";
                }
                $redirection = $base_url . "/equipment.php";
                break;
            case "addition":
                $equip = new Equipment($form_data);
                try {
                    $equip->saveToDB($conn);
                    $log->info("Added new equipment for user " . $equip->user_id . ": " . implode("|", $form_data));
                } catch (Exception $e) {
                    $log->error($e->getMessage());
                    $redirection = $base_url . "/error.php";
                }                    
                $redirection = $base_url . "/equipment.php";
                break;
            default:
                $log->error("Action not recognized: " . implode("|", $form_data));
        }
    } catch (Exception $e) {
        $log->error($e->getMessage());
    }
    header("Location: " . $redirection);
} else {
    //Redirected guests to start page
    $log->error($_SERVER['REMOTE_ADDR'] . " | " . $_SERVER['HTTP_USER_AGENT'] . " | Guests trying to access " . $_SERVER['PHP_SELF']); 
   	header('Location: ' . $base_url);    
}
exit();
?>
