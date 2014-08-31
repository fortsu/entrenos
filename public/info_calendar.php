<?php
use Entrenos\User;
use Entrenos\Activity;
use Entrenos\Utils\Utils;

require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/global_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/database.php';

if (!isset($current_user)) { // request coming via AJAX
    session_start(); 
}
if (isset($_SESSION['login'])) { 
    // Identifying user
    $current_user = new User(array('id'=> $_SESSION['user_id']));
    $current_user->userFromId($conn);
} else {
    //Redirected guests to start page
    $log->error($_SERVER['REMOTE_ADDR'] . " | " . $_SERVER['HTTP_USER_AGENT'] . " | Guests trying to access dynamic calendar page"); 
   	header('Location: index.php');
    exit();
}
    
if(isset($_REQUEST['month'])) {
    $cal_month = $_REQUEST['month'];    
}
if(isset($_REQUEST['year'])) {
    $cal_year = $_REQUEST['year'];    
}
$num_days = cal_days_in_month(CAL_GREGORIAN, $cal_month, $cal_year);
$log->info($current_user->id . "|Displaying activities from " . $cal_year . "." . $cal_month, 0);
?>
    <table cellspacing="0" class="advanced">
        <thead>
            <tr>
                <th>Lun</th>
                <th>Mar</th>
                <th>Mié</th>
                <th>Jue</th>
                <th>Vie</th>
                <th>Sáb</th>
                <th>Dom</th>
                <th>Km</th>
            </tr>
        </thead>
        <tbody class="calendar">

<?php
    $workouts = $current_user->getAllActivities($conn); //ToDo: look only for current month/year. ToDo 2: try/catch blocks!
    // id => ( date_act => 2011-01-19, time_act => 23:40:51, title => "Ejemplo")
    $workouts_cal = array();
    foreach ($workouts as $key => $activity) {
        $dateAndTime = Utils::getDateAndTimeFromDateTime ($activity->start_time);
        $workouts_cal[$key] = array('date_act' => $dateAndTime['date'], 'time_act' => $dateAndTime['time'], 'title' => $activity->title);
    }

    $log->debug($current_user->id . "|Number of days for " . $cal_year . "/" . $cal_month . ": " . $num_days);
    $loop_dates = array();
    // calculating days of the week based on first day of the month.
    for ($tmp_day = 1; $tmp_day < $num_days; $tmp_day += 7) {
        $tmp_date = date('o-W',mktime('0','0','0', $cal_month, $tmp_day, $cal_year)); //ISO-8601 year number
        list($tmp_year, $tmp_week) = explode("-",$tmp_date);
        $loop_dates[] = array("orig" => $tmp_date, "year" => $tmp_year, "week" => $tmp_week);  
    }
    $last_date = date('o-W',mktime('0','0','0', $cal_month, $num_days, $cal_year));
    if ($last_date != $loop_dates[count($loop_dates)-1]['orig']) {
        list($tmp_year, $tmp_week) = explode("-",$last_date);
        $loop_dates[] = array("orig" => $last_date, "year" => $tmp_year, "week" => $tmp_week);
    }
    $log->debug($current_user->id . "|" . json_encode($loop_dates));

    foreach($loop_dates as $key => $entry) {
        echo "<tr class=\"calendar\">\r\n";
            $dates_current = Utils::datesFromWeekYear($entry['week'], $entry['year']);
            foreach ($dates_current as $key => $date) {
                $td_id = $date;
                list ($year, $month, $day_num) = explode("-",$date);
                if ($month != $cal_month) {
                    $day_class = "other_month";
                } else {
                    $day_class = "current_month";
                }
                echo "<td id='" . $td_id . "'> <span class='" . $day_class . "'>" . $day_num;
                echo "<br />\r\n";
                foreach($workouts_cal as $key => $values) {
                    if ($td_id == $values['date_act']) {
                        $display_text = $values['time_act'];
                        if ($values['title'] !== "") {
                            $display_text = $values['title'];
                        }
                        echo "<div id=\"act_prev\" class=\"calendar_data\">\r\n";
                        echo "<a onmouseover=\"act_preview('" . $key . "','summary_" . $key . "')\" " .
                             " onmouseout=jQuery('#summary_" . $key . "').hide() " .
                             " href=\"activity.php?activity_id=" . $key . "\">" . 
                             $display_text . "<span id=\"summary_" . $key . "\"></span></a>";
                        echo "</div>\r\n";
                    }
                }
                echo "</span></td>\r\n";
            }
            echo "<td class=\"calendar_data\">";
            echo  Activity::getKmWeek($conn, $current_user->id, $entry['week'], $entry['year']);
            echo "</td>\r\n";
            //echo "<td class=\"calendar_data\">" . $entry['week'] . "</td>\r\n";
        echo "</tr>\r\n";
    }
    echo "</tbody>";
    echo "</table>";
?>
