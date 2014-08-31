<?php
namespace Entrenos;
use Entrenos\Utils\Utils;
use \PDO;
use \DateTime;
use \DateTimeZone;
use \Exception;

/**
 * 
 */
class Token {
    
    var $id;
    var $token;
    var $user_id;
    var $expiration;
    var $remote_ip;

    public function __set($key,$value) {
        $this->$key = $value;
    }

    public function __get($key) {
        return $this->$key;
    }

    function __construct($generate, $data = null) {
        if ($generate) {
            # Calculating token itself
            $this->token = self::generate();
            # Expiration date
            $dateTime = new DateTime("now", new DateTimeZone("Europe/Madrid"));
            $dateTime->modify('+6 hours');
	        $this->expiration = $dateTime->format("Y-m-d H:i:s");
        } else {
            foreach ($data as $key => $value) {
                $this->__set($key,$value);
            }
        }           
    }

    public static function generate($prefix = FALSE, $length = 16) {
        if (!$prefix) {
            $prefix = $_SERVER['SERVER_ADDR'] . "fortsu";
        }
        $tmp = sha1(uniqid($prefix, TRUE));
        return substr($tmp, 0, $length);
    } 
  
    public function save ($conn) {
        $sql_query = "INSERT INTO tokens (token, user_id, expiration, remote_ip) VALUES (:token, :user_id, :expiration, :remote_ip)";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':token', $this->token);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':expiration', $this->expiration);
        $stmt->bindParam(':remote_ip', $this->remote_ip);
        $result = $stmt->execute();
	    if ($result) {
            $this->id = $conn->lastInsertId(); 
        } else {
            $this->id = 0;
            throw new Exception("Error when saving token " . json_encode($this) . " | Error: " . json_encode($stmt->errorInfo()));
        }
        return $this->id;
    }

    public function send_new_passwd ($email) {
        $server = $_SERVER["SERVER_NAME"];
        $message = "Hola,\r\n\r\n";
        $message .= "Este es un mensaje para regenerar la contraseña de la cuenta en FortSu asociada a la dirección de correo electrónico " . $email. "\r\n\r\n";
        $message .= "Si no has solicitado acción alguna, no es necesario que hagas nada.\r\n";
        $message .= "Si por el contrario deseas establecer una nueva contraseña para tu cuenta en FortSu, dirígete al siguiente enlace:\r\n";
        $message .= "http://" . $server . "/password.php?token=" . $this->token . "\r\n\r\n";
        $message .= "Gracias,\r\n\r\nFortSu\r\nayuda@fortsu.com";
        $result_email = Utils::sendEmail($email, "[FortSu] Olvido de contraseña", $message);
        return $result_email;
    }

    public function send_new_user ($email) {
        $server = $_SERVER["SERVER_NAME"];
        $message = "Bienvenido/a a FortSu\r\n\r\n";
        $message .= "Para validar la cuenta de correo electrónico " . $email . " y habilitar la nueva cuenta, pulsa en el siguiente enlace:\r\n";
        $message .= "http://" . $server . "/register.php?token=" . $this->token . "\r\n\r\n";
        $message .= "Gracias,\r\n\r\nFortSu\r\nayuda@fortsu.com";
        $result_email = Utils::sendEmail($email, "[FortSu] Alta de cuenta", $message);
        return $result_email;
    }

    public function exists ($conn) {
        // Default value
        $success = 0;
        // DB query
        $sql_query = "SELECT user_id, expiration FROM tokens WHERE token = :token LIMIT 1";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':token', $this->token);
        $result = $stmt->execute();
        if ($result) {
            # Only one row per user is expected
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $this->user_id = $row['user_id'];
                $this->expiration = strtotime($row['expiration']);
                $ahora = strtotime("now");
                $time_diff = $this->expiration - $ahora;
                $success = $this->user_id;
                if ($time_diff < 0) {
                    $success = -1;
                }
            }
        } else { 
            throw new Exception("Error when checking token " . $this->token . " | Error: " . json_encode($stmt->errorInfo()));
        }
        return $success;
    }

    public function delete ($conn) {
        $sql_query = "DELETE FROM tokens WHERE token = :token";
        $stmt = $conn->prepare($sql_query);
        $stmt->bindParam(':token', $this->token);
        $result = $stmt->execute();
        if (!$result) {
            throw new Exception("Error when removing token from DB: " . json_encode($stmt->errorInfo()));
        }
    }
}
?>
