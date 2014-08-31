<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/global_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/database.php';
use Entrenos\Utils\Utils;
use \Exception;

// TODO: add mechanisms to prevent abuse (CSRF protection, register source IP, etc.)

$result_msg = array("success" => "Mensaje enviado satisfactoriamente.<br />Gracias por su interés.",
                     "error" => "No se ha podido enviar su mensaje.<br />Por favor inténtelo más tarde.");

session_start();
if (empty($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 0;
}
if ($_SESSION['user_id'] > 0 and ($_SESSION['user_id'] !== $_REQUEST["user_id"])) {
    $log->error("Session's user_id " . $_SESSION['user_id'] . " and request's user_id " . $_REQUEST["user_id"] . " don't match, aborting | " . json_encode($_REQUEST));
    $log->error($_SERVER['REMOTE_ADDR'] . " | " . $_SERVER['HTTP_USER_AGENT'] . " | User ID from request doesn't match session's one");
    $msg = "<img src=\"" . $base_url . "/images/check_ko_24.png\"><br />" . $result_msg["error"];
    echo json_encode(array("result" => false, "response" => $msg));
    exit();
}
try {
    if (!empty($_REQUEST["email"]) and (filter_var($_REQUEST["email"], FILTER_VALIDATE_EMAIL) !== false)){
        if (!empty($_REQUEST["subject"])) {
            if (!empty($_REQUEST["comments"])) {
                $recipients = "feedback@fortsu.com";
                $user_data = "User ID: " . $_SESSION['user_id'] . "\r\n";
                if ($_SESSION['user_id'] > 0) {
                    $user_data .= "PHP script: " . $_REQUEST["php_script"] . "\r\n";
                }
                $user_data .= "User Agent: " . $_REQUEST["user_agent"] . "\r\n";
                $user_data .= "Remote IP address: " . $_REQUEST["remote_ip"] . "\r\n";
                $user_data .= "Server name: " . $_SERVER['SERVER_NAME'] . "\r\n";
                $user_data .= "Request URI: " . $_REQUEST["request_uri"] . "\r\n";
                $user_data .= "Email address: " . $_REQUEST["email"] . "\r\n";
                $message = $user_data . "\r\n" . $_REQUEST["comments"];
                $subject = "Feedback from web: " . $_REQUEST["subject"];
                $log->info("Sending feedback: " . $user_data);
                $result_email = Utils::sendEmail($recipients, $subject, $message);
                if ($result_email) {
                    $log->info("Successfully accepted for delivery email sent to " . $recipients);
                    $msg = "<img src=\"" . $base_url . "/images/check_ok_24.png\"><br />" . $result_msg["success"];
                    echo json_encode(array("result" => true, "response" => $msg));
                } else {
                    $log->error("Error when sending email to " . $recipients);
                    $msg = "<img src=\"" . $base_url . "/images/check_ko_24.png\"><br />" . $result_msg["error"];
                    echo json_encode(array("result" => false, "response" => $msg));
                }
            } else {
                $error_msg = "No comments provided";
                throw new Exception($error_msg);
            }
        } else {
            $error_msg = "No subject provided";
            throw new Exception($error_msg);
        }
    } else {
        $error_msg = "No valid email provided";
        throw new Exception($error_msg);
    }
} catch (Exception $e) {
    $log->error($e->getMessage() . " | " . json_encode($_REQUEST) . " | " . $_SERVER['REMOTE_ADDR'] . " | " . $_SERVER['HTTP_USER_AGENT']);
    header('HTTP/1.1 400 Bad Request', true, 400);
}
exit();
?>
