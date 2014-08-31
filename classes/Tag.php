<?php
namespace Entrenos;
use \PDO;
use Entrenos\Utils\Utils;

/**
 * 
 */
class Tag {
    
    public $id;
    public $user_id;
    public $name;
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
        $sql_query = "INSERT INTO tags (user_id, name, report_enabled) VALUES (:user_id, :name, :report_enabled)";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':report_enabled', $this->report_enabled);
        $result = $stmt->execute();
	    if ($result) {
            $this->id = $conn->lastInsertId(); 
            if ($this->report_enabled) {
                $this->report_url = "users/" . $this->user_id . "/reports/report_tag_" . $this->id . ".php";
                $this->update_report_url($conn);
            }
        } else {
            throw new Exception("Error when inserting tag data: " . json_encode($stmt->errorInfo()) . " | SQL: " . $sql_query . " | Tag: " . json_encode($this));
        }
    }

    private function update_report_url ($conn) {
        $sql_query = "UPDATE tags SET report_url = :report_url WHERE id = :tag_id";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':tag_id', $this->id);
        $stmt->bindParam(':report_url', $this->report_url);
        $result = $stmt->execute();
	    if (!$result) {
            throw new Exception("Error when updating report_url: " . json_encode($stmt->errorInfo()) . " | Tag: " . json_encode($this));
        }
    }

    public function update_last_report ($conn) {
        $sql_query = "UPDATE tags SET last_report = :last_report WHERE id = :tag_id";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':tag_id', $this->id);
        $stmt->bindParam(':last_report', $this->last_report);
        $result = $stmt->execute();
	    if (!$result) {
            throw new Exception("Error when updating last_report: " . json_encode($stmt->errorInfo()) . " | Tag: " . json_encode($this));
        }
    }

    function history ($conn) {
        $sql_query = "SELECT * FROM tags WHERE user_id = :user_id";
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
            throw new Exception("Error when retrieving tags: " . json_encode($stmt->errorInfo()) . " | User: " . $this->user_id);
        }
    }

    function remove ($tag_id, $conn) {
        $sql_query = "DELETE FROM tags WHERE user_id = :user_id and id = :tag_id";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':tag_id', $tag_id);
        $result = $stmt->execute();
        if (!$result) {
            throw new Exception("Error when removing history entry from athlete's data: " . json_encode($stmt->errorInfo()) . " | User: " . $this->user_id . " | Update id: " . $tag_id);
        }
    }

    public function getRecords ($conn) {
        $sql_query = "SELECT record_id FROM tag_records WHERE tag_id = :tag_id"; 
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':tag_id', $this->id);
        $result = $stmt->execute();
        if ($result) {
            $records = array();
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $records[]=$row[0];
		    }
            $this->activities = $records;
            return $this;  // ?
        } else { 
            throw new Exception("Error when retrieving records from tag: " . json_encode($stmt->errorInfo()) . " | Tag: " . $this->id. " | User: " . $this->user_id);
        }
    }

    public function getTagData ($conn) {
        $sql_query = "SELECT * FROM tags WHERE id = :tag_id";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':tag_id', $this->id);
        $result = $stmt->execute();
        if ($result) {
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                foreach ($row as $key => $value) {
                    $this->__set($key,$value);
                }
            }
            $this->getRecords($conn);
            return $this; // ?
        } else { 
            throw new Exception("Error when executing db query: " . json_encode($stmt->errorInfo()) . " | Tag " . $this->id . "| User: " . $this->user_id);
        }     
    }

    function linkRecord ($record_id, $conn) {
        $sql_query = "INSERT tag_records (tag_id, record_id) VALUES (:tag_id, :record_id)";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':tag_id', $this->id);
        $stmt->bindParam(':record_id', $record_id);
        $result = $stmt->execute();
	    if ($result) {
            return TRUE;
        } else {
            throw new Exception("Error when linking activity with tag: " . json_encode($stmt->errorInfo()) . " | Activity " . $record_id . " | Tag id: " . $this->id);
        }
    }

    function unlinkRecord ($record_id, $conn) {
        $sql_query = "DELETE from tag_records WHERE record_id = :record_id AND tag_id = :tag_id";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':tag_id', $this->id);
        $stmt->bindParam(':record_id', $record_id);
        $result = $stmt->execute();
	    if ($result) {
            return TRUE;
        } else {
            throw new Exception("Error when unlinking activity from tag: " . json_encode($stmt->errorInfo()) . " | Activity " . $record_id . " | Tag id: " . $this->id);
        }
    }

    function retrieveInfo ($field, $conn) {
        $sql_query = "SELECT SUM(" . $field . ") FROM records WHERE id IN (SELECT record_id FROM tag_records WHERE tag_id = :tag_id)";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':tag_id', $this->id);
        $result = $stmt->execute();
        if ($result) {
            $row = $stmt->fetch(PDO::FETCH_NUM);
            if ($row[0] == null or $row[0]==0) {
                return 0;
            } else {
                return sprintf("%01.2f", $row[0]/1000000); # Result is in mm -> divide by 1000000    
            }
        } else {
            throw new Exception("Error when retrieving " . $field . " data tag " . $this->id . ": " . json_encode($stmt->errorInfo()) . " | User: " . $this->user_id);
        }
    }

    public function getKmDays($conn) {
        # If only date is provided, mysql assumes time is 00h00:00
        $sql_query = "SELECT start_time, distance FROM records WHERE id IN (SELECT record_id FROM tag_records WHERE tag_id = :tag_id) ORDER BY start_time ASC";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':tag_id', $this->id);
        $result = $stmt->execute();
        if ($result) {
            $kms_tag = array();
            $date_values = array(); // 2011-06-24 => 5, 2011-08-14 => 6 (date string and index)
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                # Result is in mm -> divide by 1000000
                $row['distance'] = sprintf("%01.2f", $row['distance']/1000000);
                $dateAndTime = Utils::getDateAndTimeFromDateTime($row['start_time']);
                $row['date'] = $dateAndTime['date'];
                if(array_key_exists($row['date'], $date_values)) { //merge different activities from same day 
                    $index = $date_values[$row['date']];
                    $old_value = $kms_tag[$index]['distance'];
                    $kms_tag[$index]['distance'] += $row['distance'];
                } else {
                    $kms_tag[] = $row;
                    $date_values[$row['date']] = count($kms_tag)-1;
                }
            }
            return $kms_tag;
        } else {
            throw new Exception("Error when retrieving kms for tag: " . $this->id . " | " . json_encode($stmt->errorInfo()) . " | User: " . $user_id);
        }
    }

    public function imgTagReport ($img_path, $ttf, $conn) {
        $kms = $this->retrieveInfo('distance', $conn);
        $summary = "#" . $this->name . ": " . $kms;
        $im = @imagecreate(400, 30);
        if ($im) {
            $white = imagecolorallocate($im, 255, 255, 255);
            $black = imagecolorallocate($im, 0, 0, 0);
            if (!imagettftext($im, 11, 0, 5, 21, $black, $ttf, $summary))
                throw new Exception("Error TTF: " . json_encode(error_get_last()) . " | Tag: " . json_encode($this));
            if (imagepng($im, $img_path)) {
                imagedestroy($im);
            } else {
                throw new Exception("Error al crear la imagen PNG en " . $img_path . " | Error: " . json_encode(error_get_last()) . " | Tag: " . json_encode($this));
            }
        } else {
            throw new Exception("No se puede inicializar un flujo de imagen GD: " . json_encode(error_get_last()) . " | Tag: " . json_encode($this));
        } 
    }

    public function removeImgReport ($conn) {
        $this->getTagData($conn);
        // TODO: pass path as a parameter!
        $img_file = $_SERVER["DOCUMENT_ROOT"] . "/users/" . $this->user_id . "/reports/" . $this->last_report;
        if (file_exists($img_file)) {
            if (!unlink($img_file)) {
                throw new Exception("Error when removing last report: " . $this->last_report . " | Error: " . json_encode(error_get_last()) . " | Tag: " . json_encode($this), 0);
            }
        }
    }
}
?>
