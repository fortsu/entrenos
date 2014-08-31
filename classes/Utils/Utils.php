<?php
namespace Entrenos\Utils;
use \DateTime;
use \DateTimeZone;
use \DomDocument;
use \Exception;
use \XSLTProcessor;
use \ZipArchive;
/**
 * Utils Class File
 *
 */
class Utils {
    /**
     * While parsing, parse the supplied XML document.
     *
     * Sets up a SimpleXMLElement object based on success of parsing
     * the XML document file.
     *
     * @param string $doc the xml document location path
     * @return object
     */
    public static function loadFile($doc) {
        if (file_exists($doc)) {
            return simplexml_load_file($doc);
        } else {
            throw new Exception ("Unable to load the xml file " .
                                 "using: \"$doc\"", E_USER_ERROR);
        }
    }

    public static function sendEmail ($email, $subject, $message) {

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "From: FortSu <no_reply@fortsu.com>\r\n";
        $headers .= 'X-Mailer: PHP/' . phpversion() . "\r\n";
        //$headers .= 'Bcc: dgranda@gmail.com' . "\r\n";

        return mail($email, $subject, $message, $headers);
    }

    //2011-01-20 12:51:24 -    see http://dev.mysql.com/doc/refman/5.1/en/datetime.html
    public static function getDateAndTimeFromDateTime ($dateTime) {
        $tmp = explode(" ", $dateTime);
        $dateAndTime = array('date' => $tmp[0], 'time' => $tmp[1]);
        return $dateAndTime;
    }

    //1023960 -> 0:17:03.9
    // if simple -> 17'03"
    public static function formatMs ($time, $simple = false){
        $tmp_s = $time/1000; # 1023
        $ms = $time%1000; # 960
        $tmp_m = $tmp_s/60; # 17
        $seconds = $tmp_s%60; #3
        $minutes = $tmp_m%60; # 17
        $hours = intval($tmp_m/60); # 0
        $format_time = $hours . ":" . sprintf("%02s",$minutes) . ":" . sprintf("%02s",$seconds) . "." . sprintf("%01s",substr($ms,0,1));
        if ($simple) {
            $format_time = "";
            if ($hours > 0){
                $format_time .= $hours . "h";
            }
            if ($minutes > 0) {
                $format_time .= sprintf("%02s", $minutes) . "'";
            }
            if ($seconds > 0) {
                $format_time .= sprintf("%02s", $seconds) . "\"";
            }
        }
        return $format_time;        
    }

    //4.64907 -> 4:38 (truncate)
    public static function formatPace ($pace){
        $tmp = explode(".",$pace);
        $min = $tmp[0];
        if (count($tmp) < 2) {
            $tmp[1] = 0;
        }
        // Making sure we have at least 2 digits in decimal data adding trailing zeros if necessary
        $tmp1 = str_pad($tmp[1], 2, '0');
        // 2 first ones represent seconds, next 3 ms
        $tmp2 = intval($tmp1)*6/10;
        $tmp3 = explode(".",$tmp2);
        $tmp4 = sprintf("%0". strlen($tmp[1]) . "s", $tmp3[0]);
        $result = $min . ":" . sprintf("%02s",substr($tmp4,0,2));
        return $result;
    }

    //5.15 min/s -> 11.42 km/h
    // TODO: decide which kind of result gets 4.75 as input -> exception?, -1?, convert decimal into sexagesimal?
    public static function pace2speed ($pace) {
        if (strpos($pace, ".") === false) {
            $min_int = $pace;
            $min_dec = 0;
        } else {
            list($min_int, $min_dec) = explode(".", $pace);
        }
        $pace_tmp = $min_int . "." . $min_dec*10/6;
        $speed = 60/$pace_tmp;
        return $speed;
    }

    //4.64907 -> 279 (rounded)
    public static function dbpace2seconds ($pace) {
        $pace_3dec = number_format($pace, 3, '.', ''); //adds trailing zeros if needed (skipping errors/warnings in case of integers)
        $tmp = explode(".",$pace_3dec);
        $tmp2 = $tmp[1] * 6/10; # 2 first are seconds, 3 latter ms (649 * 0,6 -> 389.4)
        // only interested in 3 first numbers in this part
        $tmp3 = str_pad(intval($tmp2), 3, '0', STR_PAD_LEFT); //adding leading zeros if needed (e.g. 010 * 0,6 = 6 -> 006)
        $extra_seconds = round($tmp3/10);
        $total = $tmp[0]*60 + $extra_seconds;
        return $total;
    }

    // options are: 4'45" or 4:45 -> javascript in form to make it 4:45
    public static function formpace2seconds ($pace) {
        list($min, $sec) = explode(":",$pace);
        $pace_seconds = $min*60 + $sec;
        return $pace_seconds;
    }

    public static function seconds2pace ($value) {
        $minutes = intval($value/60);
        $seconds = $value%60;
        $pace = sprintf("%d:%02d", $minutes, $seconds);
        return $pace;
    }    

    public static function seconds2dbpace ($value) {
        $minutes = intval($value/60);
        $seconds = $value%60;
        $decimal = $seconds * 10/6;
        $dbpace = sprintf("%d.%d", $minutes, $decimal);
        return $dbpace;
    } 

    public static function checkPaceSeconds ($value) { // there is a bug in slider's js when min value is not zero
        if (strlen($value) > 3) { 
            $base = substr($value,0,3);
            $extra = substr($value,3);
            $value = $base + $extra;       
        }
        return $value;
    }

    public static function datesFromWeekYear($week_number, $year) {
        $dates = array();
        for($day=1; $day<=7; $day++) {
            $dates[] = date('Y-m-d', strtotime($year."W".$week_number.$day)); // ISO year with ISO week and day, see http://www.php.net/manual/en/datetime.formats.compound.php
        }
        return $dates;
    }

    public static function datesFromMonthYear($month_number, $year) {
        $dates = array();
        $num_days = cal_days_in_month(CAL_GREGORIAN, $month_number, $year);
        for($day=1; $day<=$num_days; $day++) {
            $dates[] = date('Y-m-d', mktime(0, 0, 0, $month_number, $day, $year));
        }
        return $dates;
    }

    public static function weekDaysFromDate($date) {
        // Assuming $date is in format YYYY-MM-DD
        list($year, $month, $day) = explode("-", $date);

        // Get the weekday of the given date
        $wkday = date('l',mktime('0','0','0', $month, $day, $year));

        switch($wkday) {
            case 'Monday': $numDaysToMon = 0; break;
            case 'Tuesday': $numDaysToMon = 1; break;
            case 'Wednesday': $numDaysToMon = 2; break;
            case 'Thursday': $numDaysToMon = 3; break;
            case 'Friday': $numDaysToMon = 4; break;
            case 'Saturday': $numDaysToMon = 5; break;
            case 'Sunday': $numDaysToMon = 6; break;   
        }

        // Timestamp of the monday for that week
        $monday = mktime('0','0','0', $month, $day-$numDaysToMon, $year);

        $seconds_in_a_day = 86400;

        // Get date for 7 days from Monday (inclusive)
        for($i=0; $i<7; $i++)
        {
            $dates[$i] = date('Y-m-d',$monday+($seconds_in_a_day*$i));
        }
        return $dates;
    }

    public static function XSLProcString ($xml_data, $my_xsl, $gpx_file = '/tmp/out.gpx') {

        $tmp_URI = 'file://' . $gpx_file;

        $xml = new DOMDocument;
        libxml_use_internal_errors(true);        
        
        if (!$xml->loadXML($xml_data)) {
            $error_txt = json_encode(libxml_get_errors());
            libxml_use_internal_errors(false); // this clear error buffer
            throw new Exception ("Fatal error when parsing xml string data: " . $error_txt);
        }

        $num_errors = count(libxml_get_errors());
        if ($num_errors > 0) {
            $error_txt = json_encode(libxml_get_errors());
            error_log("Found " . $num_errors . " errors when parsing xml string data!");
            //error_log(json_encode(libxml_get_errors()));
            libxml_use_internal_errors(false); // this clear error buffer
        }
  
        $xsl = new DOMDocument;
        if (!$xsl->load($my_xsl)) {
            throw new Exception ("Unable to load stylesheet: " . $my_xsl);
        }

        $proc = new XSLTProcessor();
        $proc->importStylesheet($xsl);

        if ($proc->transformToURI($xml, $tmp_URI)) {
            return $gpx_file;
         } else
            return false;
    }

    //http://stackoverflow.com/questions/834303/php-startswith-and-endswith-functions
    public static function startsWith($haystack, $needle) {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    public static function endsWith($haystack, $needle) {
        $length = strlen($needle);
        $start =  $length *-1; //negative
        return (substr($haystack, $start) === $needle);
    }

    public static function distTrkpts ($trkpts) {
        $distance = 0;
        for ($i=1; $i < count($trkpts); $i++) {
            $distance += self::distLatLon($trkpts[$i-1]['lat'], $trkpts[$i-1]['lon'], $trkpts[$i]['lat'], $trkpts[$i]['lon']);
        }
        return $distance;
    }

    // http://www.movable-type.co.uk/scripts/latlong.html
    public static function distLatLon($lat1, $lon1, $lat2, $lon2) {
        $radius = 6371000; // average value in m for lat around 45
        $dist = acos(sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($lon2-$lon1))) * $radius;
        return $dist;
    }

    public static function elevationTrkpts ($trkpts) {
        $upositive = 0;
        $unegative = 0;
        for ($i=1; $i < count($trkpts); $i++) {
            $elevation_diff = $trkpts[$i]['ele'] - $trkpts[$i-1]['ele'];
            if ($elevation_diff > 0) {
                $upositive += $elevation_diff; 
            } else {
                $unegative += abs($elevation_diff);
            }
        }
        return array($upositive, $unegative);
    }

    public static function calculateMinBeats ($arrWorkout, $trkpts) {
        // Looking for start and end points so trackpoints can be linked to laps
        $result = array();
        $laps_index = 0;
        $trkpts_index = 0;
        //for ($i; $i+1 < count($arrWorkout); $i++) { // busca la hora de comienzo de la vuelta siguiente        
        while ($laps_index+1 < count($arrWorkout)) {
            $hr_min_lap = 300;
            $next_lap_dateTime = new DateTime($arrWorkout[$laps_index+1]['startTime']); // 2012-01-18T08:56:40+01:00
            while($trkpts_index < count($trkpts)) { // buscamos time y hr de cada punto
                $trkpt_dateTime = new DateTime($trkpts[$trkpts_index]['time']);
                while ($trkpt_dateTime < $next_lap_dateTime) {
                    if (array_key_exists('hr', $trkpts[$trkpts_index])) { // to avoid warnings
                        if ($trkpts[$trkpts_index]['hr'] < $hr_min_lap) {
                            $hr_min_lap = $trkpts[$trkpts_index]['hr'];
                        }
                    }
                    $trkpts_index++;
                    $trkpt_dateTime = new DateTime($trkpts[$trkpts_index]['time']);                    
                }
                break; // looking for next lap
            }
            $result[$laps_index] = $hr_min_lap;
            $laps_index++;
        }
        // last lap
        $hr_min_lap = 300;
        while ($trkpts_index < count($trkpts)) {// buscamos time y hr de cada punto hasta que se acaben
            if (!empty($trkpts[$trkpts_index]['hr']) and ($trkpts[$trkpts_index]['hr'] < $hr_min_lap)) {
                $hr_min_lap = $trkpts[$trkpts_index]['hr'];
            }
            $trkpts_index++;
        }
        $result[$laps_index] = $hr_min_lap;
        return $result;
    }

    public static function diffBetDatetime ($datetime1, $datetime2) {
        $time1 = strtotime($datetime1);
        $time2 = strtotime($datetime2);
        $time_diff = abs($time2 - $time1); //seconds
        return $time_diff;
    }

    public static function daysToDate ($date1, $date2) {
        $days = floor((strtotime($date2) - strtotime($date1)) / (60 * 60 * 24));
        return $days;
    }

    public static function deleteDirectory($dir) {
        if (!file_exists($dir)) return true;
        if (!is_dir($dir)) return unlink($dir);
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') continue;
            if (!deleteDirectory($dir.DIRECTORY_SEPARATOR.$item)) return false;
        }
        return rmdir($dir);
    }

    //http://davidwalsh.name/create-zip-php
    public static function create_zip($files = array(), $destination = '', $overwrite = false) {
        try {
            if(file_exists($destination) && !$overwrite) { 
                return false; 
            }
            $valid_files = array();
            if(is_array($files)) {
                foreach($files as $file) {
                    if(file_exists($file)) {
                        $valid_files[] = $file;
                    }
                }   
            }
            if(count($valid_files)) {
                $zip = new ZipArchive();
                if($zip->open($destination,$overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
                    return false;
                }
                foreach($valid_files as $file) {
                    $zip->addFile($file, basename($file));
                }
                $zip->close();
                return file_exists($destination);
            } else {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }
    }

    public static function uncompress($filename, $destination = "") {
        if (!isset($destination) or empty($destination)) {
            $destination = pathinfo(realpath($filename), PATHINFO_DIRNAME);
        }
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        switch($ext) {
            case "zip":
                return self::unzip($filename, $destination);
                break;
            case "gz":
                // TODO test destination path: gunzip -c file.gz > /THERE/file
                return system("gunzip " . $filename);
                break; 
            default:
                return false;
        }
    }

    public static function get_valid_extension($filename) {
        $result = false;
        $finfo = new finfo();
        $file_info = $finfo->file($filename, FILEINFO_MIME);
        // GZip -> application/x-gzip; charset=binary
        // Zip -> application/zip; charset=binary
        $pieces = explode("; ", $file_info);
        $mime_type = reset($pieces);
        switch($mime_type) {
            case "application/zip":
                $result ="zip";
                break;
            case "application/x-gzip":
                $result ="gz";
                break; 
            default:
                return false;
        }
        return $result;
    }

    public static function unzip($file, $destination = '') {
        if (!isset($destination) or empty($destination)) {
            $destination = pathinfo(realpath($file), PATHINFO_DIRNAME);
        }
        try {
            $zip = new ZipArchive;
            $res = $zip->open($file);
            if ($res === true) {
                $zip->extractTo($destination);
                $zip->close();
                return true;
            } else {
                // See http://www.php.net/manual/en/zip.constants.php
                throw new Exception("Error when trying to unzip " . $file .": " . $res);
            }
        } catch (Exception $e) {
            //$log->error($e->getMessage());
            return false;
        }
    }

    /**
     * Convert content from file into lowercase. It takes care of xml keywords like DOCTYPE and SYSTEM
     * @param string filename name of the file with path if available
     * @param bool overwrite option to write conversion in same file (default) or in a new one under same path
     * @return mixed array with number of bytes written and converted filename
    **/
    public static function file_content_lowercase($filename, $overwrite = true) {
        $content = file_get_contents($filename);
        $content_lowercase = strtolower($content);
        // !DOCTYPE and SYSTEM must be uppercase in xml -> http://stackoverflow.com/questions/7020961/uppercase-or-lowercase-doctype
        $search = array("!doctype","system");
        $replace = array("!DOCTYPE","SYSTEM");
        $content_lowercase = str_replace($search, $replace, $content_lowercase);
        $result = $filename;
        if (!$overwrite) {
            $result .= "_lc";
        }
        $num_bytes = file_put_contents($result, $content_lowercase);
        return array("filename" => $result, "num_bytes" => $num_bytes);
    }

    // Non-recurive Quicksort for an array of ProductReference objects (or other field if provided)
    // Ordered by final min price (include delivery costs) of first element in prices - $pr_b->prices[0]->final_min_price
    // If fieldname is provided, order array using fieldname as key
    // adapted from http://www.algorithmist.com/index.php/Quicksort_non-recursive.php
    public static function quickSort(&$array, $fieldname = "") {
        $cur = 1;
        $stack[1]['l'] = 0;
        $stack[1]['r'] = count($array)-1;
        do {
            $l = $stack[$cur]['l'];
            $r = $stack[$cur]['r'];
            $cur--;
            do {
                $i = $l;
                $j = $r;
                $tmp = $array[(int)( ($l+$r)/2 )];
                // partion the array in two parts.
                // left from $tmp are with smaller values,
                // right from $tmp are with bigger ones
                do {
                    if (empty($fieldname)) {
                        while($array[$i]->prices[0]->final_min_price < $tmp->prices[0]->final_min_price)
                            $i++;
                        while($tmp->prices[0]->final_min_price < $array[$j]->prices[0]->final_min_price)
                             $j--;
                    } else {
                        while($array[$i][$fieldname] < $tmp[$fieldname])
                            $i++;
                        while($tmp[$fieldname] < $array[$j][$fieldname])
                             $j--;
                    }
                    // swap elements from the two sides
                    if($i <= $j) {
                        $w = $array[$i];
                        $array[$i] = $array[$j];
                        $array[$j] = $w;
                        $i++;
                        $j--;
                    }
                } while ($i <= $j);
                if($i < $r) {
                    $cur++;
                    $stack[$cur]['l'] = $i;
                    $stack[$cur]['r'] = $r;
                }
                $r = $j;

            } while ($l < $r);
        } while ($cur != 0);
    }

    // Non-recurive Quicksort for an array of ProductReference objects (or other field if provided)
    // Ordered by min final price (include delivery costs) from min_prices table
    // adapted from http://www.algorithmist.com/index.php/Quicksort_non-recursive.php
    public static function quickSort2(&$array) {
        $cur = 1;
        $stack[1]['l'] = 0;
        $stack[1]['r'] = count($array)-1;
        do {
            $l = $stack[$cur]['l'];
            $r = $stack[$cur]['r'];
            $cur--;
            do {
                $i = $l;
                $j = $r;
                $tmp = $array[(int)( ($l+$r)/2 )];
                // partion the array in two parts.
                // left from $tmp are with smaller values,
                // right from $tmp are with bigger ones
                do {
                    while($array[$i]->min_final_price->final_min_price < $tmp->min_final_price->final_min_price)
                        $i++;
                    while($tmp->min_final_price->final_min_price < $array[$j]->min_final_price->final_min_price)
                         $j--;
                    // swap elements from the two sides
                    if($i <= $j) {
                        $w = $array[$i];
                        $array[$i] = $array[$j];
                        $array[$j] = $w;
                        $i++;
                        $j--;
                    }
                } while ($i <= $j);
                if($i < $r) {
                    $cur++;
                    $stack[$cur]['l'] = $i;
                    $stack[$cur]['r'] = $r;
                }
                $r = $j;

            } while ($l < $r);
        } while ($cur != 0);
    }

    public static function feedback($msg, $log_level = "info", $only_log = FALSE) {
        global $log;
        $log->$log_level($msg);
        if (!$only_log) {
            echo $msg . "<br/>\n";
        }
    }

    public static function change_timezone($datetime_string, $source_timezone, $target_timezone, $datetime_format = "Y-m-d H:i:s"){
        $result = false;
        try {
            $tmp_datetime_obj = DateTime::createFromFormat($datetime_format, $datetime_string, new DateTimeZone($source_timezone));
            $tmp_datetime_obj->setTimezone(new DateTimeZone($target_timezone));
            $result = $tmp_datetime_obj->format($datetime_format);
        } catch (Exception $e) {
            self::feedback("Error when changing timezone: " . $e->getMessage(), "error", TRUE);
        }
        return $result;
    }

        // http://stackoverflow.com/questions/6228581/how-to-search-array-of-string-in-another-string-in-php
    // http://stackoverflow.com/questions/4547478/search-string-with-array-of-values-with-php
    public static function contains($string, array $search, $caseInsensitive = true ){
        $exp = '/'.implode('|',array_map('preg_quote',$search)).($caseInsensitive?'/i':'/');
        return preg_match($exp, $string)?true:false;
    }

    /**
    * Check if given date is in the last XX hours, dates in UTC for reference
    * @param $datetime_str string Date and time in normalized format, typically Y-m-d H:i:s
    * @param $hours_gap int Lapse of hours where provided date should be 
    * @param $timezone string Normalized timezone taken as reference
    **/
    public static function in_the_last_hours($datetime_str, $hours_gap = 24, $timezone = "UTC") { 
        $is_new = false;
        $creation_dt = new DateTime($datetime_str, new DateTimeZone($timezone));
        $diff_hours = self::get_diff_hours($creation_dt, NULL, $timezone);
        if ($diff_hours < $hours_gap) {
            $is_new = true;
        }
        return $is_new;
    }

    public static function get_diff_hours($dt1, $dt2 = NULL, $timezone = "UTC") {
        if ($dt2 === NULL) {
            $dt2 = new DateTime("now", new DateTimeZone($timezone));
        }
        $diff_dt = $dt1->diff($dt2);
        $diff_hours = $diff_dt->format('%a')*24 + $diff_dt->h;
        return $diff_hours;
    }

    public static function get_diff_mins($dt1, $dt2 = NULL, $timezone = "UTC") {
        if ($dt2 === NULL) {
            $dt2 = new DateTime("now", new DateTimeZone($timezone));
        }
        $diff_dt = $dt1->diff($dt2);
        $diff_mins = $diff_dt->format('%a')*24*60 + ($diff_dt->h)*60 + $diff_dt->i;
        return $diff_mins;
    }

    public static function check_lock($current_script) {
        $lock_path = dirname($current_script) . "/locks/";
        $lockfile = basename($current_script, '.php') . '.lock';
        if (file_exists($lock_path . $lockfile)) {
            $dtz = new DateTimeZone("UTC");
            $mod_date = date('Y-m-d H:i:s', filemtime($lockfile));
            $mod_dt = new DateTime($mod_date, $dtz);
            $diff_mins = Utils::get_diff_mins($mod_dt, NULL, "UTC");
            if ($diff_mins < 10) {
                self::feedback("Previous process did not finish yet, dropping request");
                return FALSE;
            } else {
                self::feedback("Previous process triggered more than 10 minutes ago, executing new one");
            }
        } else {
            $lock_creation = touch($lock_path . $lockfile);
            self::feedback("Lock file " . $lockfile . " created | Result: " . (int)$lock_creation,"debug",TRUE);
        }
        return TRUE;
    }
}
