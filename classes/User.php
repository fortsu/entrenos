<?php
namespace Entrenos;
use \PDO;
use \DateTime;
use \DateTimeZone;
use \Exception;
use Entrenos\Activity;
use Entrenos\Goal;
use Entrenos\Tag;
use Entrenos\Token;
use Entrenos\Utils\Utils;

/**
 * 
 */
class User {

    const HASH_ALGORITHM = 'sha256';
    
    var $id;
    var $login;
    var $username;
    var $sha1_password;
    var $hash_password;
    var $email;
    var $name;
    var $surname;
    var $data_path;
    var $report_path;
    var $remember;
    var $fb_id;
    var $fb_access_token;
    var $enabled;

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

    /**
    * Hash related functions
    * https://crackstation.net/hashing-security.htm
    * TODO: check http://php.net/manual/en/function.password-hash.php
    **/
    public static function create_hash($pass, $salt = false, $alg = false) {
        if (! $salt) 
            $salt = base64_encode(mcrypt_create_iv(24, MCRYPT_DEV_URANDOM));
        if (! $alg) 
            $alg = self::HASH_ALGORITHM;
        return $alg . ':' . $salt . ':' . hash($alg, $salt . $pass);
    }

    public static function check_hash($hash, $pass) {
        $a = explode(':', $hash);
        if (!$a) 
            return false;
        switch ($a[0]) {
            case 'sha256':
                $h = self::create_hash($pass, $a[1], $a[0]);
                break;
            default:
                $h = md5($pass);
        }
        return $hash == $h;
    } 
  
    function saveToDb ($conn) {
	    //Datetime values stored in UTC
	    $dateTime = new DateTime("now", new DateTimeZone("UTC"));
	    $timestamp = $dateTime->format("Y-m-d H:i:s");
	    $sql_query = "INSERT INTO users (username, email, password, remember, creation, enabled) VALUES (:username, :email, :password, :remember, :creation, :enabled)";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $this->hash_password);
        $stmt->bindParam(':remember', $this->remember);
        $stmt->bindParam(':creation', $timestamp);
        $stmt->bindParam(':enabled', $this->enabled);
        $result = $stmt->execute();
	    if ($result) {
            $this->id = $conn->lastInsertId();
        } else {
            throw new Exception("Error when adding normal user " . $this->email . " to DB | SQL: " . $sql_query . " | Error: " . json_encode($stmt->errorInfo()));
        }
        return $this->id;
    }

    function saveFbToDb ($conn) {
        $account_enabled = '1';
	    //Datetime values stored in UTC
	    $dateTime = new DateTime("now", new DateTimeZone("Europe/Madrid"));
	    $timestamp = $dateTime->format("Y-m-d H:i:s");
	    $sql_query = "INSERT INTO users (username, name, surname, email, fb_id, fb_access_token, creation, enabled) VALUES (:username, :name, :surname, :email, :fb_id, :fb_access_token, :creation, :enabled)";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':surname', $this->surname);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':fb_id', $this->fb_id);
        $stmt->bindParam(':fb_access_token', $this->fb_access_token);
        $stmt->bindParam(':creation', $timestamp);
        $stmt->bindParam(':enabled', $account_enabled);
        $result = $stmt->execute();
	    if ($result) {
            $this->id = $conn->lastInsertId(); 
        } else {
            throw new Exception("Error when adding FB user " . $this->email . " to DB | Error: " . json_encode($stmt->errorInfo()) . " | SQL query: " . $sql_query);
        }
        return $this->id;
    }

    private function createPath($types) {
        global $base_path;
        foreach ($types as $type) {
            $object_path =  $type . "_path";
            $this->$object_path = $base_path . "/users/" . $this->id . "/" . $type . "/";
            if (mkdir($this->$object_path, 0705, true)) {
                //Issues with umask reported, changing dir permissions
                chmod ($base_path . '/users/' . $this->id, 0705);
                chmod ($this->$object_path, 0705);
            } else {
                throw new Exception("Error when creating " . $this->$object_path . " directory");
            }
        }
    }

    function create ($conn, $remote_ip = null) {
        $success = FALSE;
        try {
            $this->saveToDb($conn);
            $this->createPath(array("data", "reports"));
            $current_token = new Token(TRUE, null); // generates new token
            $current_token->user_id = $this->id;                        
            $current_token->remote_ip = $remote_ip;
            $current_token->save($conn);
            $current_token->send_new_user($this->email);
            $success = TRUE;
        } catch (Exception $e) {
            throw new Exception($e->getMessage() . " | " . $e->getTraceAsString());
        }
        return $success;
    }

    public function delete ($conn) {
        $success = FALSE;
        try {
            $this->remove_data();
            $this->delete_from_db($conn);
            $success = TRUE;
        } catch (Exception $e) {
            throw new Exception($e->getMessage() . " | " . $e->getTraceAsString());
        }
        return $success;
    }

    public function delete_from_db ($conn) { //this will not delete entries which are outside 'users' table -> foreign keys!!
        $sql_query = "DELETE FROM users WHERE id = :user_id";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $this->id);
        $result = $stmt->execute();
        if (!$result) {
            throw new Exception("Error when removing user " . $this->id . " from DB: " . json_encode($stmt->errorInfo()));
        }
    }

    public function remove_data () {
        $user_path = "./users/" . $this->id . "/";
        $result = Utils::deleteDirectory($user_path);
        if (!$result) {
            throw new Exception("Error when removing data from user " . $this->id);
        }
    }

    // Array ( [id] => 1276567060 [name] => David García Granda [first_name] => David [last_name] => García Granda [link] => http://www.facebook.com/profile.php?id=1276567060 [email] => dgranda@gmail.com [timezone] => 1 [locale] => es_ES [verified] => 1 [updated_time] => 2010-12-28T22:58:40+0000 [access_token] => 115493161855991|646ffa3fc52e4151a8ffdbc0-1276567060|MYsnN5R2DCTnSwKF0RcBgWmEGGc ) 
    public static function createFromFb ($conn, $fb_result) {
        $user_from_fb = array ('username' => "fb_" . $fb_result['id'],
                               'name' => $fb_result['first_name'],
                               'surname' => $fb_result['last_name'],
                               'email' => $fb_result['email'],
                               'fb_id' => $fb_result['id'],
                               'fb_access_token' => $fb_result['access_token']);
        $tmp_user = new User($user_from_fb);        
        $tmp_user->saveFbToDb($conn);
        $tmp_user->createPath(array("data", "reports"));
        //$message = "Welcome to FortSu " . $tmp_user->username. "\r\n";
        //$message .= "Tu cuenta de correo ya la validaremos, ¡impaciente!\r\n";
        //$result_email = Utils::sendEmail($tmp_user->email, "Welcome to FortSu", $message);
    }

    /**
    * Fetchs user's data from DB. Throws exception if can't connect to DB
    * @params PDO connection handle $conn
    * @params string $login username or email address
    * @returns User object if found, boolean FALSE otherwise
    **/
    public static function get_from_login ($conn, $login) {
        $sql_query = "SELECT * FROM users WHERE (username = :username OR email = :email)";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':username', $login);
        $stmt->bindParam(':email', $login);
        $result = $stmt->execute();
        if ($result) {
            // If any result is retrieved credentials are valid
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $user = new User($row);
                return $user;
            } else {
                return FALSE;
            }
        } else { 
            throw new Exception("Error when checking if user " . $login . " exists. Error: " . json_encode($stmt->errorInfo()));
        }
    }

    public static function exists ($conn, $login, $sha1_password) {
        $sql_query = "SELECT * FROM users WHERE (username = :username OR email = :email) AND password = :password";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':username', $login);
        $stmt->bindParam(':email', $login);
        $stmt->bindParam(':password', $sha1_password);
        $result = $stmt->execute();
        if ($result) {
            // If any result is retrieved credentials are valid
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $user = new User($row);
                return $user;
            } else {
                return FALSE;
            }
        } else { 
            throw new Exception("Error when checking if user " . $login . " exists. Error: " . json_encode($stmt->errorInfo()));
        }
    }

    function registerAccess ($conn) {
        $dateTime = new \DateTime("now", new \DateTimeZone("Europe/Madrid"));
	    $timestamp = $dateTime->format("Y-m-d H:i:s");
        $sql_query = "UPDATE users SET last_access = :timestamp WHERE id = :user_id";
	    $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':timestamp', $timestamp);
        $stmt->bindParam(':user_id', $this->id);
        $result = $stmt->execute();
	    if (!$result) {
            throw new Exception("Error when registering last access from user " . $this->id . " to DB | Error: " . json_encode($stmt->errorInfo()) . " | SQL query: " . $sql_query);
        }
    }

    function userFromId ($conn) {
        $sql_query = "SELECT * FROM users WHERE id = :user_id";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $this->id);
        $result = $stmt->execute();
        // If something fails only FALSE is returned
        if ($result) {
            // If any result is retrieved credentials are valid
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                foreach ($row as $key => $value) {
                    $this->__set($key,$value);
                }
                $user_path = "/users/" . $this->id; 
                $this->data_path = $user_path . "/data/";
                $this->report_path = $user_path . "/reports/";
                return $this;
            } else {
                return FALSE;
            }
        } else { 
            throw new Exception("Error when retrieving user data from id " . $this->id . ". Error: " . json_encode($stmt->errorInfo()));
        }
    }

    public static function emailExists ($conn, $email) {
        $sql_query = "SELECT * FROM users WHERE email = :email";
	    $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':email', $email);
        $result = $stmt->execute();
        // If any result is retrieved entry exists
        if ($result) {
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $user = new User($row);
                return $user;
            } else {
                return FALSE;
            }
        } else { 
            throw new Exception("Error when checking if " . $email . " exists. Error: " . json_encode($stmt->errorInfo()) . " | User " . $this->id);
        }        
    }

    public static function propExists ($conn, $prop) {
        $sql_query = "SELECT * FROM users WHERE " . $prop['name'] . " = :prop_value";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':prop_value', $prop['value']);
        $result = $stmt->execute();
        if ($result) {
            return TRUE;
        } else if (json_encode($stmt->errorInfo()) == "") {
            return FALSE;
        } else { 
            throw new Exception("Error when checking  " . implode("|", $props) . " | Error: " . json_encode($stmt->errorInfo()));
        }
    }

    function addFb ($conn, $fb_result) {
	    $sql_query = "UPDATE users SET fb_id = :fb_id, fb_access_token = :fb_access_token WHERE email = :fb_email";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':fb_id', $fb_result['id']);
        $stmt->bindParam(':fb_access_token', $fb_result['access_token']);
        $stmt->bindParam(':fb_email', $fb_result['email']);
        $result = $stmt->execute();
	    if ($result) {
            trigger_error("Successfully added Facebook data for " . $this->email . " into DB", E_USER_NOTICE);
            return TRUE;
        } else {
            trigger_error("Error when adding FB data for " . $this->email . " into DB | Query: " . $sql_query . " | Error: " . json_encode($stmt->errorInfo()), E_USER_NOTICE);
            return FALSE;
        }
    }
    
    public function getAllActivities ($conn, $id_as_index = true, $get_laps = false) {
        // Retrieving data from DB
        $sql_query = "SELECT * FROM records WHERE user_id = :user_id ORDER by start_time ASC";
	    $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $this->id);
        $result = $stmt->execute();
        $workouts = array();
        if ($result) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $tmp_act = new Activity($row);
                if ($id_as_index) {
                    $workouts[$tmp_act->id]= $tmp_act;
                } else {
                    $workouts[]= $tmp_act;
                }
                if ($get_laps) {
                    $tmp_act->getLapsActivity($conn);
                }
		    }  
        } else { 
            throw new Exception("Error when retrieving all activities for user " . $this->id . " | Error: " . json_encode($stmt->errorInfo()));
        }
        return $workouts;
    }

    public function getLastDate ($conn) {
        // Retrieving data from DB
        $sql_query = "SELECT start_time FROM records WHERE user_id = :user_id ORDER by start_time DESC LIMIT 1";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $this->id);
        $result = $stmt->execute();
        $last_date = false;
        if ($result) {
            $row = $stmt->fetch(PDO::FETCH_NUM);
            list($last_date, $tmp) = explode(" ",$row[0]);
        } else { 
            throw new Exception("Error when retrieving last activity for user " . $this->id . " | Unable to select: " . json_encode($stmt->errorInfo()));
        }
        return $last_date;
    }

    function getFBToken($conn) {
        $sql_query = "SELECT fb_id,fb_access_token FROM users WHERE id  = :user_id";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $this->id);
        $result = $stmt->execute();
        $fb_token = FALSE;
        if ($result) {
            $row = $stmt->fetch(PDO::FETCH_NUM);
            $this->fb_id = $row[0];
            $fb_token = $row[1];
            $this->fb_access_token = $fb_token;
        } else { 
            throw new Exception("Error when retrieving FB token for user " . $this->id . " | Unable to select: " . json_encode($stmt->errorInfo()));
        }
        return $fb_token;
    }

    function getEquipmentIds($active_filter, $conn) {
        $sql_query = "SELECT id FROM equipment WHERE user_id  = :user_id";
        if ($active_filter) {
            $sql_query .= " AND equipment.active = '1'"; 
        }
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $this->id);
        $result = $stmt->execute();
        $equip_ids = array();
        if ($result) {
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $equip_ids[] = $row[0];
		    } 
        } else { 
            throw new Exception("Error when retrieving user's equipment list. Error: " . json_encode($stmt->errorInfo()) . " | User " . $this->id);
        }
        return $equip_ids;
    }

    function getTags($conn) {
        $sql_query = "SELECT * FROM tags WHERE user_id = :user_id";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $this->id);
        $result = $stmt->execute();
        $user_tags = array();
        if ($result) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $tag_tmp = new Tag($row);
                $user_tags[] = $tag_tmp;
		    } 
        } else { 
            throw new Exception("Error when retrieving user's tags. Error: " . json_encode($stmt->errorInfo()) . " | User " . $this->id);
        }
        return $user_tags;
    }

    /**
    * Gets goals for current user.
    *
    * @param boolean $active_filter If TRUE, filters out goals older than current date
    * @param resource $conn Database resource
    * @returns Array of Goal 
    */
    function getGoals($active_filter, $conn) {
        $sql_query = "SELECT * FROM goals WHERE user_id = :user_id";
        if ($active_filter) {
            $sql_query .= " AND goals.goal_date > now()"; 
        }
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $this->id);
        $result = $stmt->execute();
        $user_goals = array();
        if ($result) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $goal_tmp = new Goal($row);
                $user_goals[] = $goal_tmp;
		    }
        } else { 
            throw new Exception("Error when retrieving goals for user " . $this->id . ". Error: " . json_encode($stmt->errorInfo()) . " | User " . $this->id);
        }
        return $user_goals;
    }

    /**
    * Gets sports for current user.
    *
    * @param resource $conn Database resource
    * @returns Array of sport_id availables 
    */
    function getSports($conn) {
        $sql_query = "SELECT DISTINCT sport_id FROM records WHERE user_id = :user_id ORDER BY sport_id ASC";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $this->id);
        $result = $stmt->execute();
        $user_sports = array();
        if ($result and ($stmt->rowCount() > 0)) {
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $user_sports[] = $row[0];
		    }
        } else { 
            throw new Exception("Error when retrieving sports (num: " . $stmt->rowCount() . ") for user " . $this->id . ". Error: " . json_encode($stmt->errorInfo()) . " | User " . $this->id);
        }
        return $user_sports;
    }

    public function getDaySummary($date, $conn) {
        $sql_query = "SELECT * FROM records WHERE user_id  = :user_id AND start_time BETWEEN :date1 and DATE_ADD(:date2, INTERVAL 1 DAY)";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $this->id);
        $stmt->bindParam(':date1', $date);
        $stmt->bindParam(':date2', $date);
        $result = $stmt->execute();
        if ($result) {
            $summary = array();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $workouts[]=$row;
		    }
            if (count($workouts) == 1) {
                $summary = $workouts[0];
            } else {
                $summary['distance'] = 0;
                $summary['duration'] = 0;
                $summary['calories'] = 0;
                foreach($workouts as $index => $activity){
                    # Needs distance ponderation!
                    if ($summary['distance'] == 0) {
                        $summary['max_beats'] = $activity["max_beats"];
                        $summary['beats'] = $activity["beats"];
                    } else {
                        $summary["beats"] = ($summary["beats"] * $summary['distance'] + $activity["beats"] * $activity['distance']) / ($summary["distance"] + $activity['distance']);
                        if ((int)$activity["max_beats"] > $summary["max_beats"]) {
                            $summary["max_beats"] = (int)$activity["max_beats"];
                        }                    
                    }
                    $summary['distance'] += (int)$activity['distance'];
                    $summary['duration'] += (int)$activity['duration'];
                    $summary['calories'] += (int)$activity['calories'];
                }
                $summary['pace'] = ($summary['duration']* 50) /($summary['distance'] * 3);
            }

            // Displaying pace depending on distance and speed: 
            // Default: distance in km and pace in min/km
            // Pace faster than 3'/km (or faster than 3'30"/km and more than 15 km) => seems bike (km/h)
            // Pace slower than 10'/km => seems swimming, displaying elapsed time instead of speed/pace
            $speed_display = "@ " . Utils::formatPace($summary['pace']);
            if ($summary['pace'] < 3 or ($summary['pace'] < 3.5 and ($summary['distance']/1000000) > 15)) {
                $speed_display = "@ " . sprintf("%01.2f", $summary['speed']) . " km/h";
            } else if ($summary['pace'] > 10) {
                $speed_display = "en " . Utils::formatMs($summary['duration'], true);
            }
            $distance_display = sprintf("%01.2f", $summary['distance']/1000000) . " km";
            if ($summary['distance']/1000000 < 5) {
                $distance_display = sprintf("%4d", $summary['distance']/1000) . " m";
            }
            $txtSummary = $date . ": " . $distance_display . " " . $speed_display;
            if ($summary['beats'] > 0) {
                $txtSummary .= " | FCmed: " . round($summary['beats']);
            }
        } else { 
            $txtSummary = "No disponible";
            throw new Exception("Error when retrieving summary from " . $date . " for user " . $this->id . ". Error: " . json_encode($stmt->errorInfo()));
        }
        return $txtSummary;
    }

    public function imgDayReport ($date, $img_path, $ttf, $conn) {
        $font_size = 11;
        $text_angle = 0;
        $x_padding = 5;

        if ($date) {
            $summary = $this->getDaySummary($date, $conn);
        } else {
            $summary = "No disponible";
        }

        $bbox = imagettfbbox($font_size, $text_angle, $ttf, $summary);
        trigger_error("Dimensiones para el texto: " . json_encode($bbox), E_USER_NOTICE);
        $text_size = $bbox[2] - $bbox[0] + 2*$x_padding;
        trigger_error("Tamaño del texto: " . $text_size, E_USER_NOTICE);

        $im = @imagecreate($text_size, 30);
        if ($im) {
            $background_color = imagecolorallocate($im, 255, 255, 255);
            $text_color = imagecolorallocate($im, 0, 0, 0);
            if (!imagettftext($im, $font_size, $text_angle, $x_padding, 21, $text_color, $ttf, $summary))
                throw new Exception("Error TTF: " . json_encode(error_get_last()) . " | Tag: " . json_encode($this));
            if (imagepng($im, $img_path)) {
                imagedestroy($im);
            } else {
                throw new Exception("Error al crear la imagen PNG en " . $img_path . " | Error: " . json_encode(error_get_last()) . " | User: " . json_encode($this));
            }
        } else {
            throw new Exception("No se puede inicializar un flujo de imagen GD: " . json_encode(error_get_last()) . " | User: " . json_encode($this));
        }
    }

    public function getActivitiesFromDate ($date, $conn) {
        $sql_query = "SELECT id FROM records WHERE user_id  = :user_id AND DATE_FORMAT(start_time,'%Y-%m-%d') = :date";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $this->id);
        $stmt->bindParam(':date', $date);
        $result = $stmt->execute();
        if ($result) {
            $ids = array();
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $ids[] = $row[0];
		    } 
        } else { 
            throw new Exception("Error when retrieving activities from " . $date . " for user " . $this->id . ". Error: " . json_encode($stmt->errorInfo()));
        }
        return $ids;
    }

    public function update_passwd ($conn) {
	    $sql_query = "UPDATE users SET password = :hash_password where id = :user_id";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':hash_password', $this->hash_password);
        $stmt->bindParam(':user_id', $this->id);
        $result = $stmt->execute();
	    if (!$result or ($stmt->rowCount() === 0)) {
            throw new Exception("Error when updating password (changed: " . $stmt->rowCount() . ") for user " . $this->id . ". Error: " . json_encode($stmt->errorInfo()));
        }
    }

    public function remove_token ($token, $conn) {
        $sql_query = "DELETE FROM tokens WHERE token = :token and user_id = :user_id";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':user_id', $this->id);
        $result = $stmt->execute();
        if (!$result) {
            throw new Exception("Error when removing token for user " . $this->id . " from DB: " . json_encode($stmt->errorInfo()));
        }
    }

    public function enable ($conn) {
	    $sql_query = "UPDATE users SET enabled = '1' where id = :user_id";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $this->id);
        $result = $stmt->execute();
	    if (!$result) {
            throw new Exception("Error when enabling user " . $this->id . " | Error: " . json_encode($stmt->errorInfo()));
        }
    }

    public function update_maps_choice ($conn) {
	    $sql_query = "UPDATE users SET maps = :maps_choice where id = :user_id";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':maps_choice', $this->maps);
        $stmt->bindParam(':user_id', $this->id);
        $result = $stmt->execute();
	    if (!$result) {
            throw new Exception("Error when updating maps choice (" . $this->maps . ") for user " . $this->id . " | Error: " . json_encode($stmt->errorInfo()));
        }
    }

}
?>
