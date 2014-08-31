<?php 
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/global_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/database.php';
use Entrenos\Activity;

// Starting the session 
session_start();
if (isset($_SESSION['login'])) { 
    // Code for logged members 
    // Identifying user
    $user = $_SESSION['login'];
    $user_id = $_SESSION['user_id'];

    // [{"id": "2011-02-21T11:40:30Z", "dateTime": "2011-02-21 12:40:30"}, {"id": "2011-02-20T11:53:51Z", "dateTime": "2011-02-20 12:53:51"}]
    // Removing extra characters from string to be able to extract json strings for proper decoding
    $data = $_REQUEST['data'];
    $log->debug("Data received: " . $data);
    $tmp = str_replace(array("[","]"),"",$data);
    $tmp2 = str_replace("},{", "}|{",$tmp);
    $tmp3 = explode("|",$tmp2);

    $activities = array();
    $new_activities = array();

    foreach ($tmp3 as $json_string) {
        $entry = json_decode($json_string, true);
        $log->debug("Parsing entry from (UTC date) " . $entry["id"]);
        $activities[] = $entry;
    }
    $log->info("Found " . count($activities) . " entries in GPS device");

    foreach ($activities as $activity) {
        $already_exists = Activity::exists($conn, $user_id, $activity["dateTime"]);
        if ($already_exists === FALSE) {
            $log->info("Marked activity from " . $activity["dateTime"] . " to import | User: " . $user_id);
            $new_activities[] = $activity['id']; // ["2011-02-21T11:40:30Z","2011-02-20T11:53:51Z"]
        } else {
            $log->debug("Activity from " . $activity["dateTime"] . " already exists. Skipping | User: " . $user_id);
        }
    }
    
    $log->info("Activities to import: " . count($new_activities));
    if (count($new_activities) > 0) {
        // build json string with IDs from new activities -> javascript array
        $new_json = json_encode($new_activities);
        echo $new_json;
    } else {
        echo "";
    }

} else { 
    //Redirected guests to start page
    $log->error($_SERVER['REMOTE_ADDR'] . " | " . $_SERVER['HTTP_USER_AGENT'] . " | Guests trying to access " . $_SERVER['PHP_SELF']); 
   	header('Location: ' . $base_url); 
} 
?>
