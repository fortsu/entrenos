<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/database.php';
use Entrenos\Activity;
use Entrenos\Sport;
use Entrenos\User;

session_start();
if($_SESSION['user_id'] === "1" or $_COOKIE['user_id'] === "1"){
 
    $log->debug("Request data: " . json_encode($_GET));

    if (isset($_GET['action'])) {
        if (isset($_GET['uid'])){
            $uid = $_GET['uid'];
        }
        switch($_GET['action']) {
            case "create_reports_dir":
                $report_path = $base_path . "/users/" . $uid . "/reports";
                if (!mkdir($report_path, 0777, true)) {
                    $log->error("Error when creating " . $report_path . " directory: " . json_encode(error_get_last()));
                } else {
                    //Issues with umask reported, changing dir permissions
                    chmod ($base_path . '/users/' . $uid, 0777);
                    chmod ($report_path, 0777);
                    $log->info("Successfully created report directory " . $report_path);
                }
                break;
            case "copy_daily":
                $base_daily_report = $base_path . "/../reports/dia.php";
                $user_daily_report = $base_path . "/users/" . $uid . "/reports/daily_report.php";
                if (!copy($base_daily_report, $user_daily_report)) {
                    $log->error("Error when copying daily report for user " . $uid . ": " . json_encode(error_get_last()));
                } else {
                    $log->info("Successfully copied daily report for user " . $uid);
                }
                break;
            case "sync":
                $target_report = $_GET['filename'];
                if ($target_report == "daily_report.php") {
                    $base_report = $base_path . "/../reports/dia.php";
                } else {
                    $base_report = $base_path . "/../reports/objetivo.php";
                }
                $outdated = $base_path . "/users/" . $uid . "/reports/" . $target_report;
                if (!copy($base_report, $outdated)) {
                    $log->error("Error when syncing " . $target_report . " for user " . $uid . ": " . json_encode(error_get_last()));
                } else {
                    $log->info("Successfully synced " . $target_report . " for user " . $uid);
                }
                break;
            case "sync_all":                
                // Sync all report files
                $report_pattern = $base_path . "/users/*/reports/*.php";
                $report_files = glob($report_pattern);
                if (!empty($report_files)) {
                    $num_total = count($report_files);
                    $log->debug("Found " . $num_total . " report files to sync");
                    $current_num = 0;
                    foreach ($report_files as $target_report) {
                        $current_num += 1;
                        if (basename($target_report) == "daily_report.php") {
                            $base_report = $base_path . "/../reports/dia.php";
                        } else {
                            $base_report = $base_path . "/../reports/objetivo.php";
                        }
                        // TODO: copy overwrites by default => would make sense to skip files already synced (via filesize and/or md5_file)??
                        if (!copy($base_report, $target_report)) {
                            $log->error("Error when syncing " . $target_report . " (" . $current_num . "/"  . $num_total . ") | Error: " . json_encode(error_get_last()));
                        } else {
                            $log->info("Successfully synced " . $target_report . " (" . $current_num . "/"  . $num_total . ")");
                        }
                    }
                } else {
                    echo "No report files found, nothing to sync | Pattern: " . $report_pattern . "<br/>" . PHP_EOL;
                }
                break;
            case "delete_act":
                $ssql = "delete from " . $_GET['table'] . " where record_id = " . $_GET['record_id'];
                $result = $conn->query($ssql);
                if ($result) {
                    $log->info("Successfully removed record " . $_GET['record_id'] . " from table " . $_GET['table']);
                } else {
                    $log->info("Error when removing record " . $_GET['record_id'] . " from table " . $_GET['table'] . " Error: " . json_encode($conn->errorInfo()));
                }
                break;
            case "delete_user":
                try {
                    $target_user = new User(array('id'=>$uid));
                    $target_user->delete($conn);
                } catch (Exception $e) {
                    $log->error($e->getMessage() . " | " . $e->getTraceAsString());
                }
                $log->info("Successfully removed user " . $uid);
                break;
            case "update_sport":
                $current_act = new Activity(array('id' => $_GET['act_id']));
                // Updating DB
                $result = $current_act->update_prop("sport_id", $_GET['new_sport_id'], $conn);
                if ($result) {
                    $log->info("Successfully updated sport id to " . $_GET['new_sport_id'] . " for activity " . $_GET['act_id']);
                } else {
                    $log->error("Error when updating sport id to " . $_GET['new_sport_id'] . " for activity " . $_GET['act_id'] . " | " . json_encode($conn->errorInfo()));
                }
                break;
            default:
                $log->error("Action not recognized: " . implode("|", $_GET) . " | " . $_SERVER['REMOTE_ADDR'] . " | " . $_SERVER['HTTP_USER_AGENT']);
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head> 
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/> 
        <title>FortSu - Interfaz simple de gestión</title> 
    </head> 
     
    <body> 
    <?php
        echo "** Informes disponibles: ** <br/>\n";
        $report_path = $base_path . "/../reports/*.php";
        $report_search = glob($report_path);
        if ($report_search) {
            foreach ($report_search as $nombre_archivo) {
                echo "Encontrado $nombre_archivo (" . filesize($nombre_archivo) . " bytes)<br/>" . PHP_EOL;
            }
            echo "<a href=\"" . basename(__FILE__) . "?action=sync_all\" title=\"Sincronizar todos\">Sincronizar todos</a><br/>" . PHP_EOL;
        } else {
            echo "No se han encontrado informes | Patrón: " . $report_path . "<br/>\n";
        }

        echo "-------------------------------------<br/>\n";

        $ssql = "SELECT id,username,last_access FROM users ORDER BY id ASC";
        $result = $conn->query($ssql);
        if ($result) {
            $users = array();
            while ($entry = $result->fetch(PDO::FETCH_ASSOC)) {
                $users[] = $entry;
            }
            foreach ($users as $user) {
                echo "<br/>\n";
                $loop_user = new User($user);
                $report_path = $base_path . "/users/" . $loop_user->id . "/reports";
                $pattern = $report_path . "/*.php";
                if (file_exists($report_path)) {
                    $report_search = glob($pattern);
                    echo "- Usuario #" . $loop_user->id . " (" . $loop_user->username . "): (<a href=\"" . basename(__FILE__) . "?action=delete_user&uid=" . $loop_user->id . "\" title=\"eliminar usuario\">eliminar</a>)";
                    echo "<br/>\n";
                    echo "Último acceso: " . $loop_user->last_access;
                    echo "<br/>\n";
                    if ($report_search) {
                        foreach ($report_search as $nombre_archivo) {
                            echo "Encontrado $nombre_archivo (" . filesize($nombre_archivo) . " bytes)";
                            echo " (<a href=\"" . basename(__FILE__) . "?action=sync&uid=" . $loop_user->id . "&filename=" . pathinfo($nombre_archivo,PATHINFO_BASENAME) . "\" title=\"sincronizar con la versión base\" >sincronizar</a>)";
                            echo "<br/>\n";
                        }
                    } else {
                        echo "No se han encontrado informes | Patrón: " . $pattern;
                        echo " (<a href=\"" . basename(__FILE__) . "?action=copy_daily&uid=" . $loop_user->id . "\" title=\"copiar informe diario\" >copiar informe diario</a>)";
                        echo "<br/>\n";
                    }
                } else {
                    echo "El directortio " . $report_path . " no existe en el sistema";
                    echo " (<a href=\"" . basename(__FILE__) . "?action=create_reports_dir&uid=" . $loop_user->id . "\" title=\"crear directorio informes\" >crear directorio</a>)";
                    echo "<br/>\n";
                }
                $ssql_dupe = "select * from records where user_id = '" . $loop_user->id . "' group by start_time having count(start_time) > 1";
                $result_dupe = $conn->query($ssql_dupe);
                if ($result_dupe) {
                    echo "Actividades duplicadas en la base de datos: " . $result_dupe->rowCount();
                } else {
                    echo "Ha habido un problema con la conexión a base de datos: " . json_encode($conn->errorInfo());
                    $log->error("Unable to select: " . json_encode($conn->errorInfo()));
                }
                echo "<br/>\n";
                // checking sport_id for all user activities
                try {
                    $ord_activities = $loop_user->getAllActivities($conn, false);
                } catch (Exception $e) {
                    $error_msg = "Unable to retrieve activities from user " . $loop_user->id . ": " . $e->getMessage();
                    echo $error_msg;
                    $log->error($error_msg);
                }
                $num_act = count($ord_activities);
                $num_wrong_sport = 0;
                foreach ($ord_activities as $loop_act) {
                    if ($loop_act->sport_id != Sport::check($loop_act->pace)) {
                        echo " - Activity #" . $loop_act->id . ": distance (km): " . $loop_act->distance/1000000 . " | pace: " . $loop_act->pace . " | <span style='color: #f00;font-weight:bold'>sport id: " . $loop_act->sport_id . "</span>";
                        echo " (<a href=\"" . basename(__FILE__) . "?action=update_sport&act_id=" . $loop_act->id . "&new_sport_id=" . Sport::check($loop_act->pace) . "\" title=\"amend sport id\" >amend sport id</a>)";
                        echo "<br/>\n";
                        $num_wrong_sport += 1;
                    }
                }
                if ($num_wrong_sport < 1) {
                    echo "Found no activities out of " . $num_act . " from user #" . $loop_user->id . " with wrong sport_id <br/>\n";
                }
            }
        } else {
            echo "Ha habido un problema con la conexión a base de datos: " . json_encode($conn->errorInfo());
            $log->error("Unable to select: " . json_encode($conn->errorInfo()));
        }
        echo "<br/>\n";
        echo "##########################################<br/>\n";
        echo "<br/>\n";
        echo "** Actividades huérfanas en la base de datos: ** <br/>\n";
        echo "<br/>\n";
        $tables = array("goal_records", "tag_records", "record_equipment");
        foreach ($tables as $table) {
            echo "- Tabla " . $table . ":<br/>\n";
            $ssql = "select * from " . $table . " where record_id not in (select id from records)";
            $result = $conn->query($ssql);
            if ($result) {
                if ($result->rowCount() > 0){
                    while ($entry = $result->fetch(PDO::FETCH_ASSOC)) {
                        echo json_encode($entry);
                        echo " (<a href=\"admin.php?action=delete_act&table=" . $table . "&record_id=" . $entry['record_id'] . "\" title=\"eliminar entrada\" >eliminar entrada</a>)";
                        echo "<br/>\n";
                    }
                } else {
                    echo "No se ha encontrado ninguna entrada huérfana";
                    echo "<br/>\n";
                }
            } else {
                echo "Error";
                echo "<br/>\n";
            }
            echo "<br/>\n";
        }
    ?>
    </body> 
    </html> 
<?php
} else {
    $log->info($_SERVER['REMOTE_ADDR'] . " | " . $_SERVER['HTTP_USER_AGENT'] . " | Guests trying to access " . $_SERVER['PHP_SELF']);
    header('Location: ' . $base_url); 
    exit();
}
