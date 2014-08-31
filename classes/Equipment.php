<?php
namespace Entrenos;
use Entrenos\User;
use Entrenos\Utils\Utils;
use \PDO;

/**
 * Equipment Class File
 *
 * This class has all actions related to equipment 
 *
 */
class Equipment {

    var $id;
    var $user_id;
    var $active;
    var $name;
    var $description;
    var $expected_life;
    var $prior_usage;
    var $used;

    function __construct($data) {
        foreach ($data as $key => $value) {
            $this->__set($key,$value);
        }
    }

    public function __set($key, $value) {
        $this->$key = $value;
    }

    public function __get($key) {
        return $this->$key;
    }

     /**
     * Saves equipment unit data into database, returns equipment id
     * 
     * @param  object conn
     * @return string 
     */
    public function saveToDB ($conn) {
        $sql_query = "INSERT INTO equipment (user_id, active, name, description, expected_life, prior_usage) VALUES (:user_id, :active, :name, :description, :expected_life, :prior_usage)"; 
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':active', $this->active);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':expected_life', $this->expected_life);
        $stmt->bindParam(':prior_usage', $this->prior_usage);
        $result = $stmt->execute();
    	if ($result) {
            $this->id = $conn->lastInsertId();          
        } else {
            throw new Exception("Error when adding new equipment: " . json_encode($stmt->errorInfo()) . " | User " . $this->user_id);
        }
    }

    function remove ($conn) {
        $sql_query = "DELETE FROM equipment WHERE user_id = :user_id and id = :equip_id"; 
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':equip_id', $this->id);
        $result = $stmt->execute();
        if (!$result) {
            throw new Exception("Error when removing equipment " . $this->id . " from user " . $this->user_id . " | Error: " . json_encode($stmt->errorInfo()));
        }
    }

     /**
     * Retrieves all equipment info found in database related to specified user
     * 
     * @param  string user_id
     *         object conn
     * @return array 
     */
    public static function getUserEquipment ($user_id, $active_filter, $conn) {
        $sql_query = "SELECT * FROM equipment WHERE user_id = :user_id";
        if ($active_filter) {
            $sql_query .= " AND equipment.active = '1'";
        }
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $user_id);
        $result = $stmt->execute();
        if ($result) {
            $equipment = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { // TODO: retrieve objects instead of mapped arrays!
                $equipment[]=$row;
		    }
            return $equipment;
        } else { 
            throw new Exception("Error when retrieving user's equipment data: " . json_encode($stmt->errorInfo()) . " | User " . $user_id);
        }     
    }

    /**
     * Retrieves all equipment data given unit's id
     * 
     * @param   object conn
     * @return current object
     */
    public function getEquipmentData ($conn) {
        $sql_query = "SELECT * FROM equipment WHERE id = :equip_id"; 
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':equip_id', $this->id);
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
            throw new Exception("Error when executing db query: " . json_encode($stmt->errorInfo()) . " | Equipment " . $this->id);
        }     
    }

    public function update_prop($property_name, $property_value, $conn) {
        $sql_query = "UPDATE equipment SET " . $property_name . " = :property_value WHERE id = :equip_id"; 
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':property_value', $property_value);
        $stmt->bindParam(':equip_id', $this->id);
        return $stmt->execute();
    }
 
    private function modifyEquipment ($changes, $conn) {
        $fields = "";
        foreach ($changes as $key => $value) {
            $fields .= $key . " = :" . $key . ", ";
        }
        $sql_query = "UPDATE equipment SET " . rtrim($fields, ",") . "' WHERE id = :equip_id AND user_id = :user_id"; 
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':equip_id', $this->id);
        $stmt->bindParam(':user_id', $this->user_id);
        foreach ($changes as $key => $value) {
            $stmt->bindParam(':' . $key, $value);
        }
        $result = $stmt->execute();
	    if ($result) {
            return TRUE;
        } else {
            throw new Exception("Error when modifying equipment props: " . json_encode($stmt->errorInfo()) . " | User " . $this->user_id . " | Equipment id: " . $this->id);
        }
    }

    function addRecord ($record_id, $conn) {
        try {
            $this->updateUsage($record_id, "+", $conn);
            $this->linkActivity($record_id, $conn);
        } catch (Exception $e) {
            throw $e;
        }
    }

    function removeRecord ($record_id, $conn) {
        try {
            $this->updateUsage($record_id, "-", $conn);
            $this->unlinkActivity($record_id, $conn);
        } catch (Exception $e) {
            throw $e;
        }
    }

    function getRecords ($conn) {
        $sql_query = "SELECT record_id FROM record_equipment WHERE equipment_id = :equip_id"; 
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':equip_id', $this->id);
        $result = $stmt->execute();
        if ($result) {
            $records = array();
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $records[] = $row[0];
		    }
            $this->used = $records;
            return $this; // ?
        } else { 
            throw new Exception("Error when retrieving records from equipment: " . json_encode($stmt->errorInfo()) . " | Unit: " . $this->id);
        }
    }

    private function updateUsage ($record_id, $operation, $conn) {
        $sql_query = "UPDATE equipment SET equipment.usage = equipment.usage " . $operation . " (SELECT distance/1000000 from records WHERE id = :record_id) WHERE id = :equip_id AND user_id = :user_id";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':record_id', $record_id);
        $stmt->bindParam(':equip_id', $this->id);
        $stmt->bindParam(':user_id', $this->user_id);
        $result = $stmt->execute();
	    if ($result) {
            return TRUE;
        } else {
            throw new Exception("Error when updating equip: " . json_encode($stmt->errorInfo()) . " | User " . $this->user_id . " | Equipment id: " . $this->id);
        }
    }

    private function linkActivity ($record_id, $conn) {
        $sql_query = "INSERT record_equipment (record_id, equipment_id) VALUES (:record_id, :equip_id)";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':record_id', $record_id);
        $stmt->bindParam(':equip_id', $this->id);
        $result = $stmt->execute();
	    if ($result) {
            return TRUE;
        } else {
            throw new Exception("Error when linking equip with activity: " . json_encode($stmt->errorInfo()) . " | Activity " . $record_id . " | Equipment id: " . $this->id);
        }
    }

    private function unlinkActivity ($record_id, $conn) {
        $sql_query = "DELETE from record_equipment WHERE record_id = :record_id AND equipment_id = :equip_id";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':record_id', $record_id);
        $stmt->bindParam(':equip_id', $this->id);
        $result = $stmt->execute();
	    if ($result) {
            return TRUE;
        } else {
            throw new Exception("Error when unlinking equip with activity: " . json_encode($stmt->errorInfo()) . " | Activity " . $record_id . " | Equipment id: " . $this->id);
        }
    }
}
?>
