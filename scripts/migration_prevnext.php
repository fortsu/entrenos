<?php
/**
* Script to set previous and next activity for each one from all users
* 1.- Select all users
* 2.- Loop through them
* 3.- Select all activities ordered by start_time
* 4.- Set previous and next ones (if exists)
* @depends: db_releases/20121006.sql
**/

include_once dirname(__FILE__)."./../config/database.php";
include_once dirname(__FILE__) . "/../classes/User.php";
include_once dirname(__FILE__) . "/../classes/Activity.php";
date_default_timezone_set('Europe/Berlin');

// Starting the session 
session_start();

if($_SERVER['REMOTE_ADDR'] == "127.0.0.1" or $_COOKIE['user_id'] === "1" or $_SESSION['user_id'] === "1"){

    $ssql = "SELECT id FROM users ORDER BY id ASC";
    $result = $conn->query($ssql);
    if ($result) {
        $users = array();
        while ($entry = $result->fetch(PDO::FETCH_ASSOC)) {
            $users[] = $entry;
        }
        foreach ($users as $user) {
            $loop_user = new User($user);
            echo "Inspecting user #" . $loop_user->id . "...<br/>\n";
            try {
                $ord_activities = $loop_user->getAllActivities($conn, false);
            } catch (Exception $e) {
                $error_msg = "Unable to retrieve activities from user " . $loop_user->id . ": " . $e->getMessage();
                echo $error_msg;
                $log->error($error_msg);
            }
            $num_act = count($ord_activities);
            echo "Found " . $num_act . " activities from user #" . $loop_user->id . "...<br/>\n";
            $loop_prev_act = 0;
            $loop_next_act = 0;
            $index_last_act = $num_act-1;
            foreach ($ord_activities as $index => $loop_act) {
                if ($index < $index_last_act) {
                    $tmp_next_act = $ord_activities[$index+1];
                    $loop_next_act = $tmp_next_act->id;         
                } else {
                    $loop_next_act = 0;
                }
                echo " - Activity " . $loop_act->id . " from " . $loop_act->start_time;
                // Updating DB
                $result_prev = $loop_act->update_prop("prev_act", $loop_prev_act, $conn);
                echo " -> prev: " . $loop_prev_act . " (" . $result_prev . ")";
                $result_next = $loop_act->update_prop("next_act", $loop_next_act, $conn);
                echo " | next: " . $loop_next_act . " (" . $result_next . ")";
                echo "<br/>\n";
                $loop_prev_act = $loop_act->id;
            }
            echo "##########################################<br/>\n";
        }
    } else {
        echo "Ha habido un problema con la conexión a base de datos: " . json_encode($conn->errorInfo());
        $log->error("Unable to select: " . json_encode($conn->errorInfo()));
    }           
} else {
    $log->info($_SERVER['REMOTE_ADDR'] . " | " . $_SERVER['HTTP_USER_AGENT'] . " | Guests trying to access " . $_SERVER['PHP_SELF']);
    header('Location: ./../index.php'); 
    exit();
}

?>
