<?php
use \PDO;
use Entrenos\User;
use Entrenos\Utils\Cookie;

session_start();
// Session for authenticated users ALWAYS contains 'login' and 'user_id' variables
if (!empty($_SESSION['user_id'])) {
    if ($_SESSION['user_id'] > 0) {
        $log->debug("Found session: " . json_encode($_SESSION));
        $current_user = new User(array('id'=> $_SESSION['user_id']));
        $current_user->userFromId($conn);
        $_SESSION['maps'] = $current_user->maps;
        $_SESSION['user_data_path'] = $base_path . $current_user->data_path; // BASE_PATH + /users/1/data/833
    } else {
        $log->debug("Guest found");
    }
} else if (!empty($_COOKIE['uc']) and !empty(isset($_COOKIE['ut']))) { //arriving directly
    $log->debug("Found cookie: " . json_encode($_COOKIE));
    /** 
     * - Cookie authentication logic:
     * Look up user's cookie (uc) in database and retrieve associated values:
     * 1.- Check if it did not expired yet
     * 2.- Build cookie value from token (from cookie) and salt (from DB) and compare to what is in user cookie
     * 3.- Remove cookie entry from DB
     * If authentication is successful, issue new cookies and store data in DB
     * If any failed, remove cookies from browser
     * Redirect user properly
    */
    try {
        $current_cookie = Cookie::fetch_from_db($conn, $_COOKIE['uc']);
        if ($current_cookie->is_alive()) {
            $log->debug("Current cookie expires in the future: " . $current_cookie->expiration . " (UTC)");
            if ($current_cookie->check_hash($_COOKIE['ut'])) {
                $log->info("Successful user_id " . $current_cookie->user_id . " authentication via cookies");
                // Remove cookie entry from DB
                $num_removed = $current_cookie->remove($conn);
                $log->debug("Removed (result " . $num_removed . ") cookie entry " . $current_cookie->id . " from DB");
                /**
                * Update cookie
                **/
                $new_token = Cookie::create_token($current_cookie->user_id);
                $new_cookie = $current_cookie->update($new_token);
                // Store uc and ut in browser
                // Although datetime and strtotime are both UTC, default timezone is not (TODO: based on locale)
                $expiration_dt = new DateTime($new_cookie->expiration, new DateTimeZone('UTC'));
                $cookie_result["uc"] = setcookie("uc", $new_cookie->hash, $expiration_dt->getTimestamp(), "/", $_SERVER['SERVER_NAME'], FALSE, Cookie::HTTP_ONLY);
                $cookie_result["ut"] = setcookie("ut", $new_token, $expiration_dt->getTimestamp(), "/", $_SERVER['SERVER_NAME'], FALSE, Cookie::HTTP_ONLY);
                $log->debug("Cookie set (" . json_encode($cookie_result) . ") | User " . $current_cookie->user_id . " | " . $_SERVER['SERVER_NAME']);
                // Save in DB
                $num_stored = $new_cookie->save($conn);
                $log->debug("Saved (result " . $num_stored . ") cookie entry " . $new_cookie->id . " in DB");
                // Populate session
                $current_user = new User(array('id'=> $current_cookie->user_id));
                $current_user->userFromId($conn);
                $_SESSION['user_id'] = $current_user->id;
                $_SESSION['login'] = $current_user->username;
                $_SESSION['maps'] = $current_user->maps;
                $log->debug("Session populated: " . json_encode($_SESSION));
            } else {
                $log->error("Cookie's hashes don't match for user_id " . $current_cookie->user_id . " -> paranoid mode ON | " . $_SERVER['REMOTE_ADDR'] . " | " . $_SERVER['HTTP_USER_AGENT']);
                // Remove all DB cookie entries for requested user
                $num_removed = Cookie::remove_all($conn, $current_cookie->user_id);
                $log->debug("Removed " . $num_removed . " cookie DB entries related to user_id " . $current_cookie->user_id);
                // Remove all browser related cookies
                $cookie_result = Cookie::invalidate_all();
                $log->debug("Set cookie to expire (" . json_encode($cookie_result) . ") on " . $_SERVER['SERVER_NAME']);
                // Mark user as guest
                $_SESSION['user_id'] = 0;
                // Redirect to error page -> proper error message
                $_SESSION['error'] = "Hay indicios de ataque porque la información contenida en las cookies de su navegador no concuerdan con la almacenada en el sistema. Si cree que se trata de un error, por favor póngase en contacto con ayuda@fortsu.com";
                $url = "http://" . $_SERVER['SERVER_NAME'] . "/technical_error.php";
                $log->debug('Redirecting to: ' . $url);
		        header('Location: ' . $url);
                exit();
            }
        } else {
            $log->info("Cookie for user_id " . $current_cookie->user_id . " expired on " . $current_cookie->expiration . " (UTC)");
            // Remove DB cookie entry
            $num_removed = $current_cookie->remove($conn);
            $log->debug("Removed (result " . $num_removed . ") cookie entry " . $current_cookie->id . " from DB");
            // Remove all browser related cookies
            $cookie_result = Cookie::invalidate_all();
            $log->debug("Set cookie to expire (" . json_encode($cookie_result) . ") on " . $_SERVER['SERVER_NAME']);
            // Mark user as guest
            $_SESSION['user_id'] = 0;
            // Redirect to login page -> proper error message
            // TODO: save original target url to redirect after proper authentication
            $_SESSION['error'] = "Se han encontrado cookies en su navegador pero han expirado, necesita autenticarse para acceder";
            $url = "http://" . $_SERVER['SERVER_NAME'] . "/technical_error.php";
            $log->debug('Redirecting to: ' . $url);
		    header('Location: ' . $url);
            exit();
        }
    } catch (Exception $e) {
        $error_txt = $e->getMessage();
        $log->error("Something get wrong when checking cookies " . $_SERVER['REMOTE_ADDR'] . " | " .  $_SERVER['HTTP_USER_AGENT'] . " | Error: " . $error_txt);
        // Remove all browser related cookies
        $cookie_result = Cookie::invalidate_all();
        $log->debug("Set cookie to expire (" . json_encode($cookie_result) . ") on " . $_SERVER['SERVER_NAME']);
        // Mark user as guest
        $_SESSION['user_id'] = 0;
        // Redirect to error page -> proper error message
        $_SESSION['error'] = "No se ha podido verificar el contenido encontrado en las cookies. Por favor proceda a autenticarse";
        $url = "http://" . $_SERVER['SERVER_NAME'] . "/technical_error.php";
        $log->debug('Redirecting to: ' . $url);
		header('Location: ' . $url);
        exit();
    }
} else {
    // Mark as a guest
    $_SESSION['user_id'] = 0;
    // Exclude login page to avoid infinite loop
    $login_page = "http://" . $_SERVER['SERVER_NAME'] . "/index.php";
    $activity_page = "http://" . $_SERVER['SERVER_NAME'] . "/activity.php";
    $requested_uri = "http://" . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'];
    if (($requested_uri != $login_page) and ($requested_uri != $activity_page)) {
        //Redirected guests to login page
        $log->info($_SERVER['REMOTE_ADDR'] . " | " . $_SERVER['HTTP_USER_AGENT'] . " | Guests trying to access " . $_SERVER['PHP_SELF'] . " | Redirect to " . $login_page);  
       	header('Location: ' . $login_page);
        exit();
    }
}
?> 
