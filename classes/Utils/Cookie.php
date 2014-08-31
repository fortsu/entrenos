<?php
namespace Entrenos\Utils;
use \DateTime;
use \DateTimeZone;
use \PDO;
use \Exception;
use Entrenos\Token;

/**
 * Each autenticated user is provided with:
 * user cookie (stored hash in both cookie and DB)
 * user token (stored in cookie)
 * salt (stored in DB)
 * - Authentication logic:
 * Look up user's cookie (uc) in database and retrieve associated values:
 * 1.- Check it has not expired yet
 * 2.- Build cookie value from token (from cookie) and salt (from DB) and compare to what is in user cookie
 * 3.- Remove cookie entry from DB
 * If authentication is successful, issue new cookies and store data in DB
 * If any failed, remove cookies from browser
 * Redirect user properly
 * 
 * http://stackoverflow.com/questions/9890766/how-to-implement-remember-me-feature
 * https://github.com/gallir/Meneame/blob/master/branches/version5/www/libs/login.php
 */
class Cookie {

    const HASH_ALGORITHM = 'sha256';
    const HTTP_ONLY = TRUE;
    const DURATION_DAYS = 30;
    
    var $id;
    var $user_id;
    var $hash;
    var $expiration;
    var $salt;
    var $remote_ip;
    var $user_agent;

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

    public static function create_token($user_id) {
        $prefix = uniqid($user_id, TRUE);
        return Token::generate($prefix, 64);
    }

    public static function create_salt() {
        //return md5(mt_rand());
        return base64_encode(mcrypt_create_iv(24, MCRYPT_DEV_URANDOM));
    }

    public static function create_cookie_hash ($token, $salt) {
        return hash_hmac(self::HASH_ALGORITHM, $token, $salt);
    }

    // Dates always in UTC
    public function is_alive() {
        $expiration_dt = new DateTime($this->expiration, new DateTimeZone('UTC'));
        $now_dt = new DateTime('now', new DateTimeZone('UTC'));
        if ($expiration_dt > $now_dt) {
            return TRUE;
        }
        return FALSE;
    }

    // TODO: check http://php.net/manual/en/function.password-verify.php
    public function check_hash($token_from_cookie) {
        if ($this->hash == self::create_cookie_hash($token_from_cookie, $this->salt)) {
            return TRUE;
        }
        return FALSE;
    }

    public static function issue_new($user_id, $token, $remote_ip, $user_agent){
        $expiration_dt = new DateTime("now", new DateTimeZone('UTC'));
        $expiration_dt->add(new \DateInterval('P' . self::DURATION_DAYS . 'D')); 
        $salt = self::create_salt();
        $cookie_data = array("user_id" => $user_id,
                                "hash" => self::create_cookie_hash($token, $salt),
                                "expiration" => $expiration_dt->format("Y-m-d H:i:s"),
                                "salt" => $salt,
                                "remote_ip" => $remote_ip,
                                "user_agent" => $user_agent);
        return new Cookie($cookie_data);
    }

    public function update($new_token){
        $new_salt = self::create_salt();
        $new_cookie_data = array("user_id" => $this->user_id,
                                "hash" => self::create_cookie_hash($new_token, $new_salt),
                                "expiration" => $this->expiration,
                                "salt" => $new_salt,
                                "remote_ip" => $_SERVER['REMOTE_ADDR'],
                                "user_agent" => $_SERVER['HTTP_USER_AGENT']);
        return new Cookie($new_cookie_data);
    }

    public static function invalidate_all() {
        $cookie_result = array();
        $cookie_result["uc"] = setcookie("uc", "", time() - 3600, "/", $_SERVER['SERVER_NAME']);
        $cookie_result["ut"] = setcookie("ut", "", time() - 3600, "/", $_SERVER['SERVER_NAME']);
        return $cookie_result;
    }

    public static function fetch_from_db($conn, $cookie_hash) {
        $sql_query = "SELECT * FROM pcookies WHERE hash = :cookie_hash LIMIT 1";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':cookie_hash', $cookie_hash);
        $result = $stmt->execute();
	    if ($result and ($stmt->rowCount() > 0)) {
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return new Cookie($row);
            } else {
                throw new Exception("Error when fetching cookie data (" . $cookie_hash . ") from DB | Error: " . json_encode($stmt->errorInfo()));
            }
        } else {
            if ($result === false) {
                throw new Exception("Error when retrieving cookie data (" . $cookie_hash . ") from DB | SQL: " . $sql_query . " | Error: " . json_encode($stmt->errorInfo()));
            } else {
                throw new Exception("Cookie (" . $cookie_hash . ") not found in DB. Wrong domain?");
            }
        }
    }

    public function save($conn) { 
        $sql_query = "INSERT INTO pcookies (user_id, hash, expiration, salt, remote_ip, user_agent) VALUES (:user_id, :hash, :expiration, :salt, :remote_ip, :user_agent)";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':hash', $this->hash);
        $stmt->bindParam(':expiration', $this->expiration);
        $stmt->bindParam(':salt', $this->salt);
        $stmt->bindParam(':remote_ip', $this->remote_ip);
        $stmt->bindParam(':user_agent', $this->user_agent);
        $result = $stmt->execute();
	    if ($result) {
            $this->id = $conn->lastInsertId();
            return $stmt->rowCount();
        } else {
            throw new Exception("Error when adding cookie to DB | SQL: " . $sql_query . " | Error: " . json_encode($stmt->errorInfo()));
        }
    }

    public function remove($conn) {
        $sql_query = "DELETE FROM pcookies WHERE id = :cookie_id";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':cookie_id', $this->id);
        $result = $stmt->execute();
	    if ($result) {
            return $stmt->rowCount();
        } else {
            throw new Exception("Error when removing cookie " . $this->id . " from DB | SQL: " . $sql_query . " | Error: " . json_encode($stmt->errorInfo()));
        }
    }

    public static function remove_all($conn, $user_id) {
        $sql_query = "DELETE FROM pcookies WHERE user_id = :user_id";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':user_id', $user_id);
        $result = $stmt->execute();
	    if ($result) {
            return $stmt->rowCount();
        } else {
            throw new Exception("Error when removing cookie entries for user " . $user_id . " from DB | SQL: " . $sql_query . " | Error: " . json_encode($stmt->errorInfo()));
        }
    }

}
?>
