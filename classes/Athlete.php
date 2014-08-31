<?php
namespace Entrenos;
use \PDO;
use Entrenos\Utils\Utils;

/**
 * 
 */
class Athlete {
    
    var $id;
    var $user_id;
    var $weight;
    var $height;
    var $body_fat;
    var $body_muscle;
    var $body_water;
    var $rest_beats;
    var $max_beats;
    var $vo2_max;
    var $date;

    public function __set($key,$value) {
        $this->$key = $value;
    }

    public function __get($key) {
        return $this->$key;
    }

    function __construct($user_id) {
        $this->__set('user_id', $user_id);
    }  
  
    function insert ($athlete_data, $conn) {
        $sql_query = "INSERT INTO athlete_data (user_id, weight, height, body_fat, body_muscle, body_water, rest_beats, max_beats, vo2_max, date) VALUES (:user_id, :weight, :height, :body_fat, :body_muscle, :body_water, :rest_beats, :max_beats, :vo2_max, :entry_date)";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':weight', $athlete_data["weight"]);
        $stmt->bindParam(':height', $athlete_data["height"]);
        $stmt->bindParam(':body_fat', $athlete_data["body_fat"]);
        $stmt->bindParam(':body_muscle', $athlete_data["body_muscle"]);
        $stmt->bindParam(':body_water', $athlete_data["body_water"]);
        $stmt->bindParam(':rest_beats', $athlete_data["rest_beats"]);
        $stmt->bindParam(':max_beats', $athlete_data["max_beats"]);
        $stmt->bindParam(':vo2_max', $athlete_data["vo2_max"]);
        $stmt->bindParam(':entry_date', $athlete_data["date"]);
        $result = $stmt->execute();
	    if ($result) {
            $this->id = $conn->lastInsertId();
        } else {
            throw new Exception("Error when inserting athlete data: " . json_encode($stmt->errorInfo()) . " | Data: " . json_encode($athlete_data) . " | User: " . $this->user_id);
        }
    }

    function history ($conn, $order = array("field" => "date", "direction" => "DESC")) {
        $sql_query = "SELECT * FROM athlete_data WHERE user_id = :user_id ORDER BY " . $order["field"] . " " . $order["direction"];
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $this->user_id);
        $result = $stmt->execute();
        if ($result) {
            $history = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $history[] = $row;
            }
            return $history;
        } else {
            throw new Exception("Error when retrieving history athlete data: " . json_encode($stmt->errorInfo()) . " | User: " . $this->user_id);
        }
    }

    function remove ($update_id, $conn) {
        $sql_query = "DELETE FROM athlete_data WHERE user_id = :user_id and id = :update_id";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':update_id', $update_id);
        $result = $stmt->execute();
        if (!$result) {
            throw new Exception("Error when removing history entry from athlete's data: " . json_encode($stmt->errorInfo()) . " | User: " . $this->user_id . " | Update id: " . $update_id);
        }
    }

    function getProp ($conn, $prop) {
        $sql_query = "SELECT id, " . $prop . ", date FROM athlete_data WHERE user_id = :user_id ORDER BY date DESC";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $this->user_id);
        $result = $stmt->execute();
        if ($result) {
            $prop_data = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $prop_data[] = $row;
            }
            return $prop_data;
        } else {
            throw new Exception("Error when retrieving " . $prop . " from athlete's data: " . json_encode($stmt->errorInfo()) . " | User: " . $this->user_id);
        }
    }

    function collect_mpb ($conn, $sport_id = 0) {
        $sql_query = "SELECT start_time, distance, duration, beats FROM records where user_id = :user_id and sport_id = :sport_id ORDER by start_time ASC";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':sport_id', $sport_id);
        $result = $stmt->execute();
        if ($result) {
            $mpb_data = array(); // date (YYYY-MM-DD) used as key
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($row['beats'] > 0) {
                    // Calculate date from start_time (2012-01-14 11:28:47)
                    list($act_date, $act_time) = explode(" ", $row['start_time']);
                    // Checks if another activity of the same day exists.
                    // If yes, it ponderates values. Adds a new (array) element otherwise
                    if (!array_key_exists($act_date, $mpb_data)) {
                        $row['mpb'] = $row['distance']*60/($row['beats']*$row['duration']); //distance in mm, duration in ms
                        $mpb_data[$act_date] = $row;
                    } else {
                        // Adding distance and duration
                        $mpb_data[$act_date]['distance'] += $row['distance'];
                        $day_old_duration = $mpb_data[$act_date]['duration'];
                        $mpb_data[$act_date]['duration'] += $row['duration']; 
                        // Beats must be ponderated
                        $mpb_data[$act_date]['beats'] = (($mpb_data[$act_date]['beats']*$day_old_duration) + ($row['beats']*$row['duration']))/$mpb_data[$act_date]['duration'];
                        $mpb_data[$act_date]['mpb'] = $mpb_data[$act_date]['distance']*60/($mpb_data[$act_date]['beats']*$mpb_data[$act_date]['duration']); //distance in mm, duration in ms
                    }
                }
            }
            $mpb = array();
            foreach ($mpb_data as $key => $value) {
                $mpb[$key] = $value['mpb'];
            }
            return $mpb; //array with date as key and mbp as value
        } else {
            throw new Exception("Error when collecting mpb from athlete's data: " . json_encode($stmt->errorInfo()) . " | User: " . $this->user_id);
        }
    }
}
?>
