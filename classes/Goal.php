<?php
namespace Entrenos;
use \PDO;
use \Exception;
use Entrenos\Utils\Utils;

/**
 * 
 */
class Goal {
    
    public $id;
    public $user_id;
    public $name;
    public $goal_date;
    public $goal_time;
    public $description;
    public $report_enabled;
    public $report_url;
    public $last_report;
    public $activities;

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
  
    public function insert ($conn) {
        $sql_query = "INSERT INTO goals (user_id, name, goal_date, goal_time, description, report_enabled) VALUES (:user_id, :name, :goal_date,
:goal_time, :description, :report_enabled)";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':goal_date', $this->goal_date);
        $stmt->bindParam(':goal_time', $this->goal_time);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':report_enabled', $this->report_enabled);
        $result = $stmt->execute();
	    if ($result) {
            $this->id = $conn->lastInsertId(); 
            if ($this->report_enabled) {
                $this->report_url = "users/" . $this->user_id . "/reports/report_goal_" . $this->id . ".php";
                $this->update_report_url($conn);
            }
        } else {
            throw new Exception("Error when inserting goal data: " . json_encode($stmt->errorInfo()) . " | Query: " . $sql_query . " | Goal: " . json_encode($this));
        }
    }

    private function update_report_url ($conn) {
        $sql_query = "UPDATE goals SET report_url = :report_url WHERE id = :report_id";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':report_id', $this->id);
        $stmt->bindParam(':report_url', $this->report_url);
        $result = $stmt->execute();
	    if (!$result) {
            throw new Exception("Error when updating report_url: " . json_encode($stmt->errorInfo()) . " | Goal: " . json_encode($this));
        }
    }

    public function update_last_report ($conn) {
        $sql_query = "UPDATE goals SET last_report = :last_report WHERE id = :report_id";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':report_id', $this->id);
        $stmt->bindParam(':last_report', $this->last_report);
        $result = $stmt->execute();
	    if (!$result) {
            throw new Exception("Error when updating last_report: " . json_encode($stmt->errorInfo()) . " | Goal: " . json_encode($this));
        }
    }

    function history ($conn) {
        $history = array();
        $sql_query = "SELECT * FROM goals WHERE user_id = :user_id";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $this->user_id);
        $result = $stmt->execute();
        if ($result) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $history[] = $row;
            }
            return $history;
        } else {
            throw new Exception("Error when retrieving goals: " . json_encode($stmt->errorInfo()) . " | User: " . $this->user_id);
        }
    }

    function remove ($conn) {
        $sql_query = "DELETE FROM goals WHERE user_id = :user_id and id = :goal_id";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':goal_id', $this->id);
        $result = $stmt->execute();
        if (!$result) {
            throw new Exception("Error when removing goal " . $this->id . " from user " . $this->user_id . " | Error: " . json_encode($stmt->errorInfo()));
        }
    }

    public function getRecords ($conn) {
        $sql_query = "SELECT record_id FROM goal_records WHERE goal_id = :goal_id";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':goal_id', $this->id);
        $result = $stmt->execute();
        if ($result) {
            $records = array();
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $records[]=$row[0];
		    }
            $this->activities = $records;
            return $this;
        } else { 
            throw new Exception("Error when retrieving records from goal: " . json_encode($stmt->errorInfo()) . " | Goal: " . $this->id. " | User: " . $this->user_id);
        }
    }

    public function getGoalData ($conn) {
        $sql_query = "SELECT * FROM goals WHERE id  = :goal_id";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':goal_id', $this->id);
        $result = $stmt->execute();
        if ($result) {
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                foreach ($row as $key => $value) {
                    $this->__set($key, $value);
                }
            }
            $this->getRecords($conn);
            return $this;
        } else { 
            throw new Exception("Error when executing db query: " . json_encode($stmt->errorInfo()) . " | Goal " . $this->id . "| User: " . $this->user_id);
        }     
    }

    function linkRecord ($record_id, $conn) {
        $sql_query = "INSERT goal_records (goal_id, record_id) VALUES (:goal_id, :record_id)";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':goal_id', $this->id);
        $stmt->bindParam(':record_id', $record_id);
        $result = $stmt->execute();
	    if ($result) {
            return TRUE;
        } else {
            throw new Exception("Error when linking activity with goal: " . json_encode($stmt->errorInfo()) . " | Activity " . $record_id . " | Goal id: " . $this->id);
        }
    }

    function unlinkRecord ($record_id, $conn) {
        $sql_query = "DELETE from goal_records WHERE record_id = :record_id AND goal_id = :goal_id";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':goal_id', $this->id);
        $stmt->bindParam(':record_id', $record_id);
        $result = $stmt->execute();
	    if ($result) {
            return TRUE;
        } else {
            throw new Exception("Error when unlinking activity from goal: " . json_encode($stmt->errorInfo()) . " | Activity " . $record_id . " | Goal id: " . $this->id);
        }
    }

    function retrieveInfo ($field, $conn) {
        $sql_query = "SELECT SUM(" . $field . ") FROM records WHERE id IN (SELECT record_id FROM goal_records WHERE goal_id = :goal_id)";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':goal_id', $this->id);
        $result = $stmt->execute();
        if ($result) {
            $row = $stmt->fetch(PDO::FETCH_NUM);
            if ($row[0] == null or $row[0]==0) {
                return 0;
            } else {
                return sprintf("%01.2f", $row[0]/1000000); # Result is in mm -> divide by 1000000    
            }
        } else {
            throw new Exception("Error when retrieving " . $field . " data goal " . $this->id . ": " . json_encode($stmt->errorInfo()) . " | User: " . $this->user_id);
        }
    }

    public function getKmDays($conn) {
        # If only date is provided, mysql assumes time is 00h00:00
        $sql_query = "SELECT start_time, distance FROM records WHERE id IN (SELECT record_id FROM goal_records WHERE goal_id = :goal_id) ORDER BY start_time ASC";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':goal_id', $this->id);
        $result = $stmt->execute();
        if ($result) {
            $kms_goal = array();
            $date_values = array(); // 2011-06-24 => 5, 2011-08-14 => 6 (date string and index)
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                # Result is in mm -> divide by 1000000
                $row['distance'] = sprintf("%01.2f", $row['distance']/1000000);
                $dateAndTime = Utils::getDateAndTimeFromDateTime($row['start_time']);
                $row['date'] = $dateAndTime['date'];
                if(array_key_exists($row['date'], $date_values)) { //merge different activities from same day 
                    $index = $date_values[$row['date']];
                    $old_value = $kms_goal[$index]['distance'];
                    $kms_goal[$index]['distance'] += $row['distance'];
                } else {
                    $kms_goal[] = $row;
                    $date_values[$row['date']] = count($kms_goal)-1;
                }
            }
            return $kms_goal;
        } else {
            throw new Exception("Error when retrieving kms for goal: " . $this->id . " | " . json_encode($stmt->errorInfo()) . " | User: " . $user_id);
        }
    }

    public function imgGoalReport ($img_path, $ttf, $conn) {
        $kms = $this->retrieveInfo('distance', $conn);
        $num_days = Utils::daysToDate(date("Y-m-d"), date($this->goal_date));
        $summary = "#" . $this->name . ": " . $kms . " km (quedan " . $num_days . " días)";
        $im = @imagecreate(400, 30);
        if ($im) {
            $white = imagecolorallocate($im, 255, 255, 255);
            $black = imagecolorallocate($im, 0, 0, 0);
            if (!imagettftext($im, 11, 0, 5, 21, $black, $ttf, $summary))
                throw new Exception("Error TTF: " . json_encode(error_get_last()) . " | Goal: " . json_encode($this));
            if (imagepng($im, $img_path)) {
                imagedestroy($im);
            } else {
                throw new Exception("Error al crear la imagen PNG en " . $img_path . " | Error: " . json_encode(error_get_last()) . " | Goal: " . json_encode($this));
            }
        } else {
            throw new Exception("No se puede inicializar un flujo de imagen GD: " . json_encode(error_get_last()) . " | Goal: " . json_encode($this));
        } 
    }

    public function removeImgReport ($conn) {
        $this->getGoalData($conn);
        // TODO: pass path as a parameter!
        $img_file = $_SERVER["DOCUMENT_ROOT"] . "/users/" . $this->user_id . "/reports/" . $this->last_report;
        if (file_exists($img_file)) {
            if (!unlink($img_file)) {
                throw new Exception("Error when removing last report: " . $this->last_report . " | Error: " . json_encode(error_get_last()) . " | Goal: " . json_encode($this), 0);
            }
        }
    }
}
?>
