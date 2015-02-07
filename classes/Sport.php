<?php
namespace Entrenos;
use \PDO;
use \Exception;
use Entrenos\Utils\Utils;

/**
 * 
 */
class Sport {

    public $id;
    public $user_id;
    public $name;
    public $abrev;
    public $extra_weight;
    public $met;

    // Defining met for Sport
    const Distance = 0;
    const Duration = 1;
    public static $met_type_label = array(self::Distance => "Kms", self::Duration => "Horas");


    // Defining sport id
    const Running = 1;
    const Cycling = 2;
    const Walking = 3;
    const Swimming = 4;

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


    // ToDo: better localization, move this out of here!
    //public static $display_es = array(0 => "Correr", 1 => "Ciclismo", 2 => "Caminar", 3 => "Natación", 4=> "Eliptica");
    //public static $met_type  = array(0 => self::Distance, 1 => self::Distance, 2 => self::Distance, 3 => self::Distance, 4=> self::Duration);



/**
 *
 *
*/

   public function insert ($conn) {
        $sql_query = "INSERT INTO sports (user_id, name, abrev, extra_weight, met) VALUES (:user_id, :name, :abrev, :extra_weight, :met)";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':abrev', $this->abrev);
        $stmt->bindParam(':extra_weight', $this->extra_weight);
        $stmt->bindParam(':met', $this->met);
        $result = $stmt->execute();
        if ($result) {
            $this->id = $conn->lastInsertId();
        } else {
            throw new Exception("Error when inserting sport data: " . json_encode($stmt->errorInfo()) . " | Query: " . $sql_query . " | Sport: " . json_encode($this));
        }
    }


    /**
     * Check which sport corresponds to provided pace
     * @param pace int e.g. 4.75 (4'45"/km)
     * @param distance int in meters
     * @result int sport id
    **/
    static public function check($pace, $distance = 0) {
        $result = self::Running;
        if ($pace < 3 or ($pace < 3.5 and $distance > 15000)) {
            $result = self::Cycling;
        } else if ($pace > 7.5) {
            $result = self::Walking;
        }
        return $result;
    }

     /**
     * Retrieves all sports info found in database related to specified user
     * 
     * @param  string user_id
     *         object conn
     * @return array 
     */
    public static function getUserSports ($user_id, $conn) {
        $sql_query = "SELECT * FROM sports WHERE user_id = :user_id";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $user_id);
        $result = $stmt->execute();
        if ($result) {
            $sports = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { // TODO: retrieve objects instead of mapped arrays!
                $sports[]=$row;
                    }
            return $sports;
        } else {
            throw new Exception("Error when retrieving user's sports data: " . json_encode($stmt->errorInfo()) . " | User " . $user_id);
        }
    }

   /**
     * Retrieves all equipment data given unit's id
     * 
     * @param   object conn
     * @return current object
     */
    public function getSportData ($conn) {
        $sql_query = "SELECT * FROM sports WHERE id = :sport_id";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':sport_id', $this->id);
        $result = $stmt->execute();
        if ($result) {
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                foreach ($row as $key => $value) {
                    $this->__set($key,$value);
                }
            }
            return $this; // ?
        } else {
            throw new Exception("Error when executing db query: " . json_encode($stmt->errorInfo()) . " | Sport " . $this->id);
        }
    }

}
?>
