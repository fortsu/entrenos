<?php
namespace Entrenos;
use Entrenos\Utils\Utils;
use \DateTime;
use \DateTimeZone;
use \PDO;
use \Exception;

/**
 * Activity Class File
 *
 * This class has all actions related to an activity 
 *
 */
class Activity {

    public $id;
    public $user_id;
    public $sport_id;
    public $start_time;
    public $laps; // ToDo -> migrate where used to be include in Activity object. Index 0 means summary
    public $equip;
    public $tags;
    public $goals;
    public $distance;
    public $duration;
    public $comments;
    public $upositive;
    public $unegative;
    public $pace;
    public $max_pace;
    public $beats;
    public $max_beats;
    public $similar;
    public $visibility;
    public $path;
    public $prev_act;
    public $next_act;

    public function __set($key,$value) {
        $this->$key = $value;
    }

    public function __get($key) {
        return $this->$key;
    }

    function __construct($data) {
        foreach ($data as $key => $value) {
            $this->__set($key,$value);
        }
    }

    function delete_from_db($conn) {
        $sql_query = "DELETE FROM records WHERE id = :activity_id AND user_id = :user_id";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':activity_id', $this->id);
        $stmt->bindParam(':user_id', $this->user_id);
        $result = $stmt->execute();
    	if ($result === FALSE or $stmt->rowCount() === 0) {
            throw new Exception("Error when removing activity from DB: " . $this->id . " | User: " . $this->user_id . " | Rows: " . $conn->rowCount() . " | Error: " . json_encode($stmt->errorInfo()));
        }
    }

    /**
     * Updates property in related activities once current one no longer exists in the system
     * @param $property activity property which is going to be updated ("prev_act" or "next_act") 
     * @param $related_act_id id for related activity
     * @param $new_value new value for related activity id
     * @param $conn database connection 
     */
    function update_related ($property, $related_act_id, $new_value, $conn) {
        $result = 0;
        if ($related_act_id > 0) {
            $rel_act = new Activity(array('id' => $related_act_id));
            // Don't need to retrieve laps data
            $rel_act->getActivity($conn, false);
            $result = $rel_act->update_prop($property, $new_value, $conn);
        }
        return $result;
    }

    /**
     * Retrieves a summary from the complete Activity (may contain laps or other simple units as elements)
     * Example of element:
     *
     * [index] => 1
     * [startPoint] => Array ( 
     *     [lat] => 40.49161786 
     *     [lon] => -3.73906441 ) 
     * [endPoint] => Array ( 
     *     [lat] => 40.48418194 
     *     [lon] => -3.74537062 ) 
     * [startTime] => 2010-11-16T12:38:22+01:00 
     * [elapsedTime] => 0:05:22.75 
     * [calories] => 82 
     * [distance] => 1000.000000 
     * [summary] => Array ( 
     *     [lat] => 40.48418194 
     *     [lon] => -3.74537062 
     *     [MaximumSpeed] => 3.68730712        
     *     [AverageHeartRateBpm] => 147    
     *     [MaximumHeartRateBpm] => 162 
     *     [trigger] => distance
     *     [intensity] => active )
     *
     * @param array $arrWorkout 
     * @return array 
     */
    public function get_summary ($arrWorkout) {
        $summary = array();
        # Retrieving data from first element
        # startPoint
        $summary['startPoint'] = $arrWorkout[0]['startPoint'];
        # startTime
        $summary['start_time'] = date('Y-m-d H:i:s', strtotime($arrWorkout[0]['startTime']));

        # Initializating vars
        $summary['distance'] = 0;
        $summary['duration'] = 0;
        $summary['calories'] = 0;
        $summary['beats'] = 0;
        # MaximumSpeed comes as m/s in TCXv2 format
        $summary["max_pace"] = (float)$arrWorkout[0]["MaximumSpeed"];
        $summary["max_beats"] = (int)$arrWorkout[0]["MaximumHeartRateBpm"];

        # Retrieving acculumated data
        foreach($arrWorkout as $lapIndex => $lapValues) {
            # elapsedTime. It comes in h:mm:ss.ff?, but ff seems 1/100 s
            $msTmp = 0;
            if (strpos($lapValues["elapsedTime"],':') !== FALSE) {
                list($h, $m, $sf) = explode (":", $lapValues["elapsedTime"]);
                list($s, $f) = explode (".", $sf);
                $msTmp += (intval($h) * 3600 * 1000);
                $msTmp += (intval($m) * 60 * 1000);
                $msTmp += (intval($s) * 1000);
                $msTmp += (intval($f) * 10);
            } else { # elapsedTime in tcx files already in seconds (ss.ms)
                $msTmp = intval($lapValues["elapsedTime"] * 1000);
            }
            $summary['duration'] += $msTmp;
            
            # Calories
            $summary['calories'] += $lapValues['calories'];

            # distance. It comes in m.000000 (micras) We stored in mm. TCXv2 format is meter
            $distanceOld = $summary['distance'];
            list($meter, $micra) = explode (".", sprintf("%.03f",$lapValues['distance']));
            $mmTmp = 0;
            $mmTmp += (intval($meter) * 1000);
            $mmTmp += (floor(intval($micra)) / 1000);
            $summary['distance'] += $mmTmp;

            # Calculation needed!
            # Maximum pace comes as m/s
            if ((float)$lapValues["MaximumSpeed"] > $summary["max_pace"]) {
                $summary["max_pace"] = (float)$lapValues["MaximumSpeed"];
            }
            # MaximumHeartRateBpm
            if ((int)$lapValues["MaximumHeartRateBpm"] > $summary["max_beats"]) {
                $summary["max_beats"] = (int)$lapValues["MaximumHeartRateBpm"];
            }
    
            # AverageHeartRateBpm. Needs distance ponderation!
            if ($summary["distance"] == 0)
                $summary["beats"] = $lapValues["AverageHeartRateBpm"];
            else
                $summary["beats"] = ($summary["beats"] * $distanceOld + $lapValues["AverageHeartRateBpm"] * $mmTmp) / $summary["distance"];

        }

        # Retrieving data from last element
        # endPoint
        $summary['endPoint'] = $arrWorkout[count($arrWorkout)-1]['endPoint'];
        return $summary;    
    }
    
     /**
     * Calculates values for extended summary: speed (average), maxspeed (km/h), pace
     * 
     * @param array $summary 
     * @return array 
     */
    public function calculate_extended_summary ($summary) {
        $extSummary = $summary;
        # distance comes in mm, time in ms -> time/distance (s/m)
        # we look for min/km: 1000/60 -> (time * 50) / (distance * 3)
        $pace = ($summary['duration'] * 50) / ($summary['distance'] * 3);
        # from min/km to km/h 
        $speed = 60 / $pace;
        # maxPace in m/s!!
        $maxSpeed = $summary['max_pace'] * 3.6; # km/h
        # Although stored in decimal format to make transformations easier, pace's seconds must be displayed in base sixty!!

        $extSummary['pace'] = $pace;
        $extSummary['speed'] = $speed;
        $extSummary['max_speed'] = $maxSpeed;

        // Setting sport_id depending on pace
        $extSummary['sport_id'] = Sport::check($extSummary['pace'], $summary['distance']/1000);

        // hardcoded so far to prevent warnings
        $extSummary['upositive'] = 0; 
        $extSummary['unegative'] = 0;
        $extSummary['title'] = "";
         
        return $extSummary;
    }

    public function get_extended_summary_from_trkpts ($trkpts) {
        $num_points = count($trkpts);
        $extSummary['distance'] = Utils::distTrkpts($trkpts) * 1000 ; //retrieved in m -> mm
        if (isset($trkpts[0]['time'])) { // if time info is present
            $extSummary['start_time'] = $trkpts[0]['time']; //2009-10-17T18:37:31Z
            $extSummary['end_time'] = $trkpts[$num_points-1]['time'];
            $elapsedTimeSeconds = Utils::diffBetDatetime($extSummary['start_time'], $extSummary['end_time']);
            $extSummary['duration'] = $elapsedTimeSeconds * 1000;
            if ($extSummary['distance'] > 0) {
                $extSummary["pace"] = ($elapsedTimeSeconds/60)*1000000/$extSummary['distance'];
                // Setting sport_id depending on pace
                $extSummary['sport_id'] = Sport::check($extSummary['pace'], $summary['distance']/1000);
            } else {
                $extSummary["pace"] = 0;
                // If distance is 0 and pace is 0, most likely the activity has been wrongly recorded
            }
            $extSummary["speed"] = $extSummary['distance']*3.6/($elapsedTimeSeconds*1000);
        } else {  
            $extSummary['start_time'] = date(DATE_ISO8601); //hardcoded to now!
            $extSummary['duration'] = 0;
            $extSummary["pace"] = 0;
            $extSummary["speed"] = 0;
        }
        // no GPX support for following attributes
        $extSummary['calories'] = 0;
        $extSummary["max_pace"] = 0;
        $extSummary["max_speed"] = 0;
        $extSummary["beats"] = 0;
        $extSummary["max_beats"] = 0;
        list ($extSummary['upositive'], $extSummary['unegative']) = Utils::elevationTrkpts($trkpts);
        // hardcoded so far to prevent warnings
        $extSummary['title'] = "";

        return $extSummary;
    }

     /**
     * Saves activity data in database
     * Updates related (prev, next) activity ids
     * Stores also lap data if present
     * @param array arrWorkout lap data
     * @param object conn db connection
     */
    public function save_to_db($arrWorkout, $conn) {
        // ToDo: error handling and recovery
        try {
            $this->save_record_to_db($conn);
            $this->save_related_all($conn);
            if (!empty($arrWorkout)) {
                $lapIds = $this->saveLapsToDB($arrWorkout, $conn); // lap should be a different class
            }
        } catch (Exception $e) {
            if ($this->id > 0) { //error triggered when saving laps, record itself was recorded fine
                $this->delete_from_db($conn);
                // updating also related activities
                $this->update_related("next_act", $this->prev_act, $this->next_act, $conn);
                $this->update_related("prev_act", $this->next_act, $this->prev_act, $conn);
                throw new Exception("Removed all data related to activity " . $this->id . " | Error: " . $e->getMessage());
            }
            throw $e;
        }
    }    

     /**
     * Saves activity data in database, returns activity id
     * 
     * @param  object conn
     * @return string 
     */
    public function save_record_to_db ($conn) {
        $sql_query = "INSERT INTO records (user_id, start_time, sport_id, distance, duration, comments, beats, max_beats, speed, max_speed, pace, max_pace, calories, upositive, unegative, title) VALUES (:user_id, :start_time, :sport_id, :distance, :duration, :comments, :beats, :max_beats, :speed, :max_speed, :pace, :max_pace, :calories, :upositive, :unegative, :title)";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':start_time', $this->start_time);
        $stmt->bindParam(':sport_id', $this->sport_id);
        $stmt->bindParam(':distance', $this->distance);
        $stmt->bindParam(':duration', $this->duration);
        $stmt->bindParam(':comments', $this->comments);
        $stmt->bindParam(':beats', $this->beats);
        $stmt->bindParam(':max_beats', $this->max_beats);
        $stmt->bindParam(':speed', $this->speed);
        $stmt->bindParam(':max_speed', $this->max_speed);
        $stmt->bindParam(':pace', $this->pace);
        $stmt->bindParam(':max_pace', $this->max_pace);
        $stmt->bindParam(':calories', $this->calories);
        $stmt->bindParam(':upositive', $this->upositive);
        $stmt->bindParam(':unegative', $this->unegative);
        $stmt->bindParam(':title', $this->title);
        $result = $stmt->execute();
    	if ($result) {
            $this->id = $conn->lastInsertId();          
        } else {
            throw new Exception("Error when saving activity into DB: " . json_encode($stmt->errorInfo()) . " | User " . $this->user_id);
        }
    }

    function get_related ($conn, $rel = "prev_act") {
        $rel_act_id = 0;
        $sql_query = "SELECT id FROM records WHERE start_time < :start_time AND user_id = :user_id ORDER BY start_time DESC LIMIT 1";
        if ($rel == "next_act") {
            $sql_query = "SELECT id FROM records WHERE start_time > :start_time AND user_id = :user_id ORDER BY start_time ASC LIMIT 1";
        }
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':start_time', $this->start_time);
        $stmt->bindParam(':user_id', $this->user_id);
        $result = $stmt->execute();
        if ($result) {
            $row = $stmt->fetch(PDO::FETCH_NUM);
            $rel_act_id = $row[0];
            if (empty($rel_act_id)) {
                $rel_act_id = 0;
            }
        } else {
            throw new Exception("Error when getting related activities: " . json_encode($stmt->errorInfo()) . " | User " . $this->user_id);
        }
        return $rel_act_id;
    }

    /*
     * Save previous and next related activity_ids for current activity
     * Update information in related activities as well
     * @param $conn db_connection
     * throws Exception 
     */
    function save_related_all ($conn) {
        $this->save_related_prop($conn, "prev_act");
        $this->save_related_prop($conn, "next_act");
        $this->update_related("next_act", $this->prev_act, $this->id, $conn);
        $this->update_related("prev_act", $this->next_act, $this->id, $conn);
    }

    /*
     * Save related property for current activity
     * @param $conn db_connection
     * @param $rel_prop related property ("prev_act" or "next_act")
     * throws Exception 
     */
    function save_related_prop ($conn, $rel_prop) {
        $this->$rel_prop = $this->get_related($conn, $rel_prop);
        if (!$this->update_prop($rel_prop, $this->$rel_prop, $conn)) {
            throw new Exception("Error when updating " . $rel_prop . " for activity " . $this->id . ": " . json_encode($stmt->errorInfo()) . " | User " . $this->user_id);
        }
    }   

     /**
     * Saves lap data into database, returns laps id
     * 
     * @param  array  arrWorkout
     *         object conn
     * @return string 
     */
    public function saveLapsToDB ($arrWorkout, $conn) {
        $lapsId = array();
        foreach ($arrWorkout as $lapIndex => $lapValues) {
            # elapsedTime. It comes in h:mm:ss.ff?, but ff seems 1/100 s
            $duration = 0;
            if (strpos($lapValues["elapsedTime"], ':') !== FALSE) {
                list($h, $m, $sf) = explode (":", $lapValues["elapsedTime"]);
                list($s, $f) = explode (".", $sf);
                $duration += (intval($h) * 3600 * 1000);
                $duration += (intval($m) * 60 * 1000);
                $duration += (intval($s) * 1000);
                $duration += (intval($f) * 10);
            } else { # elapsedTime in tcx files already in seconds (ss.ms)
                $duration = intval($lapValues["elapsedTime"] * 1000);
            }
            
            # Converting distance to mm
            $distance = $lapValues['distance']*1000;
            # distance comes in mm, time in ms -> time/distance (s/m)
            # we look for min/km: 1000/60 -> (time * 50) / (distance * 3)
            $pace = ($duration * 50) / ($distance * 3);
            # from min/km to km/h -> 
            $speed = 60 / $pace;
            # saved in decimal mode to facilitate calculations!!
            $maxSpeed = 60 / $lapValues['MaximumSpeed'];

            $sql_query = "INSERT INTO laps (user_id, record_id, lap_number, start_time, start_lat, start_lon, end_lat, end_lon, distance, duration, pace, max_pace, speed, max_speed, beats, max_beats, min_beats, calories) VALUES (:user_id, :record_id, :lap_number, :start_time, :start_lat, :start_lon, :end_lat, :end_lon, :distance, :duration, :pace, :max_pace, :speed, :max_speed, :beats, :max_beats, :min_beats, :calories)"; 
            $stmt = $conn->prepare($sql_query);
            $stmt->bindParam(':user_id', $this->user_id);
            $stmt->bindParam(':record_id', $this->id);
            $stmt->bindParam(':lap_number', $lapValues['index']);
            $lap_startTime = date('Y-m-d H:i:s', strtotime($lapValues['startTime']));
            $stmt->bindParam(':start_time', $lap_startTime);
            $stmt->bindParam(':start_lat', $lapValues['startPoint']['lat']);
            $stmt->bindParam(':start_lon', $lapValues['startPoint']['lon']);
            $stmt->bindParam(':end_lat', $lapValues['endPoint']['lat']);
            $stmt->bindParam(':end_lon', $lapValues['endPoint']['lon']);
            $stmt->bindParam(':distance', $lapValues['distance']);
            $stmt->bindParam(':duration', $duration);
            $stmt->bindParam(':pace', $pace);
            $stmt->bindParam(':max_pace', $lapValues['MaximumSpeed']);
            $stmt->bindParam(':speed', $speed);
            $stmt->bindParam(':max_speed', $maxSpeed);
            $stmt->bindParam(':beats', $lapValues['AverageHeartRateBpm']);
            $stmt->bindParam(':max_beats', $lapValues['MaximumHeartRateBpm']);
            $stmt->bindParam(':min_beats', $lapValues['MinimumHeartRateBpm']);
            $stmt->bindParam(':calories', $lapValues['calories']);
            $result = $stmt->execute();
        	if ($result) {
                // Get activity's id
                $id = $conn->lastInsertId(); 
                $lapsId[] = $id;        
            } else { 
                throw new Exception("Error when storing laps from activity " . $this->id . " into DB | Error: " . json_encode($stmt->errorInfo()) . " | User: " . $this->user_id . " | Record: " . $this->id);
            }
        }
        return $lapsId;
    }

    public function getLapsActivity ($conn) {
        $sql_query = "SELECT * FROM laps WHERE record_id = :activity_id ORDER BY start_time ASC";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':activity_id', $this->id);
        $result = $stmt->execute();
        if ($result) {
            $laps_act = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $laps_act[]=$row;
		    }
            $this->laps = $laps_act;
            return TRUE;
        } else { 
            $this->laps = null;
            return FALSE;
        }     
    }

    public function getActivity ($conn, $get_laps = true) {
        $sql_query = "SELECT * FROM records WHERE id = :activity_id";
        if ($this->user_id > 0) {
            $sql_query .= " AND user_id = :user_id";
        }
        // IDs are unique -> stop looking up table when first is found
        $sql_query .= " LIMIT 1";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':activity_id', $this->id);
        if ($this->user_id > 0) {
            $stmt->bindParam(':user_id', $this->user_id);
        }
        $result = $stmt->execute();
        if ($result) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                foreach ($row as $key => $value) { // trying to migrate to real object oriented
                    $this->__set($key,$value);
                }
		    }
            if ($get_laps) {
                $this->getLapsActivity($conn);
            }
            $this->path = "users/" . $this->user_id . "/data/" . $this->id;
            return TRUE;
        } else {
            return FALSE;
        }     
    }

    public function stringSummary($isForCalendar = False) {       
        $txtSummary = sprintf("%01.2f", $this->distance/1000000) . " km @ " . Utils::formatPace($this->pace);
        if ($isForCalendar)
            $txtSummary .= "\r\n";
        $txtSummary .= " | FCmed: " . round($this->beats) . " | FCmax: " . $this->max_beats;
        return $txtSummary;
    }

    public static function getKmWeek($conn, $user_id, $week_number, $year) {
        $km = 0;
        $dates = Utils::datesFromWeekYear ($week_number, $year);
        # If only date is provided, mysql assumes time is 00h00:00
        $sql_query = "SELECT sum(distance) FROM records WHERE user_id = :user_id AND start_time BETWEEN '" . $dates[0] . "' AND '" . $dates[0] . "' + INTERVAL 1 WEEK";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $user_id);
        $result = $stmt->execute();
        if ($result) {
            $row = $stmt->fetch(\PDO::FETCH_NUM);
            # Result is in mm -> divide by 1000000
            $km = sprintf("%01.2f", $row[0]/1000000);
        } else { 
            throw new Exception("Error when retrieving km per week | Error: " . json_encode($stmt->errorInfo()));
        }
        return $km;
    }

    public static function getKmDaysWeek($conn, $user_id, $week_number, $year) {
        $km = 0;
        $dates = Utils::datesFromWeekYear ($week_number, $year);
        # If only date is provided, mysql assumes time is 00h00:00
        $sql_query = "SELECT start_time, distance FROM records WHERE user_id = :user_id AND start_time BETWEEN '" . $dates[0] . "' AND '" . $dates[0] . "' + INTERVAL 1 WEEK";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $user_id);
        $result = $stmt->execute();
        if ($result) {
            $kms = array();
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                # Result is in mm -> divide by 1000000
                $km = sprintf("%01.2f", $row[1]/1000000);
                $kms[$row[0]] = $km;
		    }
            $days_km = array();
            foreach ($dates as $index => $datetime) { //[0] => 2011-04-05
                $tmp_kms = 0;
                foreach ($kms as $key => $value) { //[2011-04-05 12:20:44] => 12.10 
                    if (Utils::startsWith($key, $datetime)) { //multiple records in one day
                        $tmp_kms += $value;
                        unset($kms[$key]);                  
                    }
                }
                $days_km[$datetime] = $tmp_kms;
            }
            return $days_km;
        } else {
            throw new Exception("Error when retrieving kms from week/year: " . $week_number . "/" . $year . " | " . json_encode($stmt->errorInfo()) . " | User: " . $user_id);
        }
    }

    public static function getKmDaysMonth($conn, $user_id, $month_number, $year) {
        $km = 0;
        $dates = Utils::datesFromMonthYear ($month_number, $year);
        # If only date is provided, mysql assumes time is 00h00:00
        $sql_query = "SELECT start_time,distance FROM records WHERE user_id = :user_id AND DATE_FORMAT(start_time,'%Y-%m') = '" . $year . "-" . $month_number . "'";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $user_id);
        $result = $stmt->execute();
        if ($result) {
            $kms = array();
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                # Result is in mm -> divide by 1000000
                $km = sprintf("%01.2f", $row[1]/1000000);
                $kms[$row[0]] = $km;
		    }
            $days_km = array();
            foreach ($dates as $index => $datetime) { //[0] => 2011-04-05
                $tmp_kms = 0;
                foreach ($kms as $key => $value) { //[2011-04-05 12:20:44] => 12.10 
                    if (Utils::startsWith($key, $datetime)) { //multiple records in one day
                        $tmp_kms += $value;
                        unset($kms[$key]);                  
                    }
                }
                $days_km[$datetime] = $tmp_kms;
            }
            return $days_km;
        } else {
            throw new Exception("Error when retrieving daily kms from month/year: " . $month_number . "/" . $year . " | " . json_encode($stmt->errorInfo()) . " | User: " . $user_id);
        }
    }

    public static function getKmMonthsYear($conn, $user_id, $year) {
        $kms = array();
        $months = array('01','02','03','04','05','06','07','08','09','10','11','12');
        foreach ($months as $index => $month_number) {
            $kms[$year."-".$month_number] = Activity::getKmMonthYear($conn, $user_id, $month_number, $year);
        }
        return $kms;
    }

    public static function getKmMonthYear($conn, $user_id, $month, $year) {
        $sql_query = "SELECT sum(distance) FROM records WHERE user_id = :user_id AND DATE_FORMAT(start_time,'%Y-%m') = '" . $year . "-" . $month . "'";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $user_id);
        $result = $stmt->execute();
        $km = 0;
        if ($result) {
            $row = $stmt->fetch(PDO::FETCH_NUM);
            if ($row) {
                $km = sprintf("%01.2f", $row[0]/1000000); //Result is in mm -> divide by 1000000
            }
        } else {
            throw new Exception("Error when retrieving monthly km from year: " . $month . "/" . $year . " | " . json_encode($stmt->errorInfo()) . " | User: " . $user_id);
        }
        return $km;
    }

    public static function exists ($conn, $user_id, $dateTime, $offset = "INTERVAL 2 SECOND") {
        $min_date = "'" . $dateTime . "' - " . $offset;
        $max_date = "'" . $dateTime . "' + " . $offset;
        $sql_query = "SELECT id FROM records WHERE user_id = :user_id AND start_time BETWEEN (" . $min_date . ") AND (" . $max_date . ")";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $user_id);
        $result = $stmt->execute();
        if ($result) {
            $row = $stmt->fetch(PDO::FETCH_NUM);
            if (empty($row)) { //not found in DB
                return FALSE;
            } else {
                $id = $row[0];
                return $id;
            }
        } else {
            throw new Exception("Error when looking for activities from user " . $user_id . " starting on " . $dateTime . " | Error: " . json_encode($stmt->errorInfo()));
        }
    }

    public static function inDB ($conn, $user_id, $act_id) {
        $sql_query = "SELECT * FROM records WHERE id = :activity_id AND user_id = :user_id";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':activity_id', $act_id);
        $result = $stmt->execute();
        if ($result) {
            if ($stmt->rowCount() == 0) { //If it fails also returns FALSE
                return FALSE;
            } else {
                return TRUE;
            }
        } else {
            throw new Exception("Unable to select: " . json_encode($stmt->errorInfo()));
        }
    }

    public function getSimilarDistance ($distance, $interval, $conn) {
        $min_distance = $distance * (1-$interval);
        $max_distance = $distance * (1+$interval);
        try {
            $this->similar = self::getBetweenDistance ($this->user_id, $this->id, $min_distance, $max_distance, $conn);
        } catch (Exception $e) {
            throw new Exception("Error when retrieving similar activities to " . $distance . " +/- " . $interval . " for user " . $this->user_id . ". Error: " . $e->getMessage());
        }
    }

    public static function getBetweenDistance ($user_id, $activity_id = FALSE, $min_distance, $max_distance, $conn) {
        $sql_query = "SELECT * FROM records WHERE user_id = :user_id AND distance BETWEEN '" . $min_distance . "' AND '" . $max_distance . "'";
        if ($activity_id) {
            $sql_query .= " AND id != :activity_id";
        }
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $user_id);
        if ($activity_id) {
            $stmt->bindParam(':activity_id', $activity_id);   
        }
        $result = $stmt->execute();
        if ($result) {
            $records = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $records[] = new Activity($row);
		    }
            return $records;
        } else { 
            throw new Exception("Error when retrieving activities with distance between " . $min_distance . " and " . $max_distance . " for user " . $user_id . " | Error: " . json_encode($stmt->errorInfo()));
        }
    }

    public static function getDistances($conn, $user_id, $sport_id) {
        $sql_query = "SELECT MAX(distance),MIN(distance) FROM records WHERE user_id = :user_id AND sport_id = :sport_id";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':sport_id', $sport_id);
        $result = $stmt->execute();
        if ($result) {
            $row = $stmt->fetch(PDO::FETCH_NUM);
            $act_distances['max'] = $row[0];
            $act_distances['min'] = $row[1];
        } else { 
            throw new Exception("Error when retrieving max and min activity distance for user " . $user_id . ". Error: " . json_encode($stmt->errorInfo()));
        }
        return $act_distances;
    }

    public static function getPaces($conn, $user_id, $sport_id) {
        $sql_query = "SELECT MAX(pace), MIN(pace) FROM records WHERE user_id  = :user_id and sport_id = :sport_id";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':sport_id', $sport_id);
        $result = $stmt->execute();
        if ($result) {
            $act_paces = array();
            $row = $stmt->fetch(PDO::FETCH_NUM);
            $act_paces['max'] = $row[0];
            $act_paces['min'] = $row[1];
        } else { 
            throw new Exception("Error when retrieving max and min activity distance for user " . $user_id . ". Error: " . json_encode($stmt->errorInfo()));
        }
        return $act_paces;
    }

    public function getTags($conn) {
        $sql_query = "SELECT * FROM tags WHERE id in (select tag_id from tag_records where record_id = :activity_id)";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':activity_id', $this->id);
        $result = $stmt->execute();
        if ($result) {
            $tag_array = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $tag_array[] = new Tag($row);
		    }
            $this->tags = $tag_array;
        } else { 
            throw new Exception("Error when retrieving tags for activity " . $this->id . " | Error: " . json_encode($stmt->errorInfo()));
        }
    }

    public function update_prop($property_name, $property_value, $conn) {
        $sql_query = "UPDATE records SET " . $property_name . " = :property_value WHERE id = :activity_id";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':activity_id', $this->id);
        $stmt->bindParam(':property_value', $property_value);
        return $stmt->execute();
    }

    public function changeVisibility($next_status, $conn) {
        return $this->update_prop("visibility", $next_status, $conn);
    }

}
?>
