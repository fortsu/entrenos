<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/global_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/database.php';
use Entrenos\Charts;
use Entrenos\User;

$script_name = pathinfo(__FILE__, PATHINFO_FILENAME);
if ($script_name == "dia") {
    error_log("Daily report generator must be customized: " . __FILE__ ,0);
    exit();
} else {

    // <base_dir>/users/<user_id>/reports
    $pieces = explode("/", dirname(__FILE__));
    $user_id = $pieces[count($pieces)-2];

    // Checking if report plus from last activity for current user exists
    $current_user = new User(array('id'=> $user_id));
    $last_date = $current_user->getLastDate($conn);
    $report_plus_path = "report_plus_" . $last_date . ".png";
    if (file_exists($report_plus_path)) {
        header("Content-Type: image/png");
        readfile($report_plus_path);
    } else {
        $log->info("Creating report plus from " . $last_date . " | User: " . $current_user->id);
        // Creating image with last day's report
        $report_img_path = "report_" . $last_date . ".png";
        if (!file_exists($report_img_path)) {
            $ttf = $base_path . "/fonts/Ubuntu/Ubuntu-R.ttf";
            $current_user->imgDayReport ($last_date, $report_img_path, $ttf, $conn);
            $log->info("Built report from " . $last_date . " | User: " . $current_user->id);
        } else {
            $log->info("Retrieving already existing report from " . $last_date . " | User: " . $current_user->id);
        }

        // Building altitude profile for selected date
        // Retrieving activity ids for date
        $activity_ids = $current_user->getActivitiesFromDate ($last_date, $conn);
        // ToDo: concatenate profiles from each activity
        $source_file = $base_path . "/users/" . $user_id . "/data/". end($activity_ids);
        if (!file_exists($source_file)) { // No source file -> no height profile
            $log->info("No height profile as no source file was found for activity " . end($activity_ids) . " | User: " . $current_user->id);
            header("Content-Type: image/png");
            readfile($report_img_path);   
        } else {
            $altitude_profile = "alt_profile_" . end($activity_ids) . ".png"; //Only last one taken, need improvement!
            if (!file_exists($altitude_profile)) {
                // Creating image
                Charts::altitudeChart ($current_user->id, end($activity_ids), $conn);
                $log->info($current_user->id . "|Created altitude profile from activity " . end($activity_ids));
            } else {
                $log->info($current_user->id . "|Retrieving already existing altitude profile from " . end($activity_ids));
            }

            //Resizing altitude profile (original size is 800x300)
            $thumb_width = 80;
            $thumb_height = 30;
            $altitude_thumb = @imagecreate($thumb_width, $thumb_height)
                or die("Cannot Initialize new GD image stream");
            list($ancho, $alto) = getimagesize($altitude_profile);
            $original = imagecreatefrompng($altitude_profile);
            imagecopyresampled($altitude_thumb,$original,0,0,0,0,$thumb_width,$thumb_height,$ancho,$alto);

            // Combining report and altitude profile in one image
            $report = imagecreatefrompng($report_img_path);
            list($report_width,$report_height) = getimagesize($report_img_path);
            $log->debug($current_user->id . "|Size of " . $report_img_path . ": " . $report_width ."x" . $report_height); 

            $newWidth = $report_width + $thumb_width;
            $log->debug($current_user->id . "|Width of resampled image:" . $newWidth);
            $newHeight = $report_height;
            $newImage = @imagecreate($newWidth, $newHeight)
                or die("Cannot Initialize new GD image stream");

            imagecopyresampled($newImage, $report, 0, 0, 0, 0, $report_width, $report_height, $report_width, $report_height);
            imagecopyresampled($newImage, $altitude_thumb, $report_width, 0, 0, 0, $thumb_width, $thumb_height, $thumb_width, $thumb_height);
            imagepng($newImage, $report_plus_path);

            imagedestroy($altitude_thumb);
            imagedestroy($newImage);
            header("Content-Type: image/png");
            readfile($report_plus_path);
        }
    }
}
?>
