<?php
include_once dirname(__FILE__)."./../config/database.php";
date_default_timezone_set('Europe/Berlin');
$ssql = "SELECT id,start_time FROM records";
#$ssql = "SELECT id,start_time FROM laps";
$result = $conn->query($ssql) or die ("Unable to select: " . json_encode($conn->errorInfo()));
if ($result) {
    $fechas_ant = array();
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $fechas_ant[$row['id']] = $row['start_time'];
    }
    foreach ($fechas_ant as $id => $start_time){
        $mysql_datetime = date('Y-m-d H:i:s', strtotime($start_time));
        $ssql2 = "UPDATE records set start_time_dt = '" . $mysql_datetime . "' where id = '" . $id . "'";
        #$ssql2 = "UPDATE laps set start_time_dt = '" . $mysql_datetime . "' where id = '" . $id . "'";
        echo "Updating " . $mysql_datetime;
        echo "\r\n";
        echo "<br />";
        $result2 = $conn->query($ssql2) or die ("Unable to update: " . json_encode($conn->errorInfo()));
    }
}     
?>
