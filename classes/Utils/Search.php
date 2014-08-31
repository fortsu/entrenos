<?php
namespace Entrenos\Utils;
use Entrenos\Activity;
use Entrenos\User;
use Entrenos\Utils\Utils;
use \PDO;

/**
 * Search Class File
 *
 * This class has all actions related to a search 
 *
 */
class Search {

    public $user_id;
    public $tags;
    public $goals;
    public $equip;
    public $min_dist;
    public $max_dist;
    public $min_pace;
    public $max_pace;
    public $min_speed;
    public $max_speed;
    public $date;
    public $sport_id;
    public $order_by;
    public $order;
    public $total_results;
    public $num_display;
    public $step;

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

    public function filter ($conn) {
        $dist_clause = "";
        if (isset($this->min_dist) AND isset($this->max_dist)) {
            $dist_clause = " AND distance BETWEEN '" . $this->min_dist . "' AND '" . $this->max_dist . "'";
        }
        $pace_clause = "";
        if (isset($this->min_pace) AND isset($this->max_pace)) {
            $pace_clause = " AND pace BETWEEN '" . $this->min_pace . "' AND '" . $this->max_pace . "'";
        }
        $speed_clause = "";
        if (isset($this->min_speed) AND isset($this->max_speed)) {
            $speed_clause = " AND speed BETWEEN '" . $this->min_speed . "' AND '" . $this->max_speed . "'";
        }

        // TODO: Migrate these to prepared statements seems not an easy one...
        $tag_clause = $this->_get_item_clause($this->tags, "tag_records", "tag_id", "notag");
        $goal_clause = $this->_get_item_clause($this->goals, "goal_records", "goal_id", "nogoal");
        $equip_clause = $this->_get_item_clause($this->equip, "record_equipment", "equipment_id", "noequip");

        // no filter if selected date is ANY
        $date_clause = "";
        switch ($this->date) {
            case "last_year":
                $date_clause = " AND start_time > DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                break;
            case "last_month":
                $date_clause = " AND start_time > DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
            case "last_week";
                $date_clause = " AND start_time > DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                break;
        }

        // sport_id is mandatory field
        $sport_clause = " AND sport_id = :sport_id";

        // older first by default
        $order_clause = " ORDER BY start_time DESC";
        if (isset($this->order) AND isset($this->order_by)) {
            $order_clause = " ORDER BY " . $this->order_by . " " . $this->order;
        }
        $limit_clause = "";
        if (isset($this->step) AND isset($this->num_display)) {
            $limit_clause = " LIMIT " . $this->step . ", " . $this->num_display;
        }
        $sql_query = "SELECT * FROM records WHERE user_id = :user_id" .
                $dist_clause .
                $pace_clause .
                $speed_clause .
                $tag_clause .
                $goal_clause .
                $equip_clause .
                $date_clause .
                $sport_clause .
                $order_clause . 
                $limit_clause;
        $stmt = $conn->prepare($sql_query);
        // Base part
        $stmt->bindParam(':user_id', $this->user_id);
        // Sport is mandatory field
        $stmt->bindParam(':sport_id', $this->sport_id);

        $result = $stmt->execute();
        if ($result) {
            $records = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $records[] = $row;
		    } 
        } else { 
            throw new Exception("Error when executing search for user " . $this->user_id . " | sql: " . $sql_query . " | " . json_encode($this) . " | Error: " . json_encode($stmt->errorInfo()));
        }
        return $records;
    }

    private function _get_item_clause ($items_array, $table_name, $item_field, $noitem_tag) {
        $item_clause = "";
        if (isset ($items_array) AND count($items_array) > 0) {
            $noitem_index = array_search($noitem_tag, $items_array);
            if ($noitem_index !== FALSE) { 
                unset($items_array[$noitem_index]);
                $item_clause = " AND id IN (SELECT record_id from " . $table_name;
                $items = "";
                if (count($items_array) > 0) {
                    $item_clause .= " where";
                    foreach($items_array as $index => $item_id) {
                        if ($items == "") {
                            $items .= " " . $item_field . " != '" . $item_id . "'";
                        } else {
                            $items .= " AND " . $item_field . " != '" . $item_id . "'";
                        }
                    }
                }
                $item_clause .= $items . ")";
            } else {
                $item_clause .= " AND id NOT IN (SELECT record_id from " . $table_name . " where";
                $items = "";
                foreach($items_array as $index => $item_id) {
                    if ($items == "") {
                        $items .= " " . $item_field . " = '" . $item_id . "'";
                    } else {
                        $items .= " OR " . $item_field . " = '" . $item_id . "'";
                    }
                }
                $item_clause .= $items . ")";
            }
        }
        return $item_clause;
    }

}
?>
