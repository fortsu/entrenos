<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/global_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/database.php';
use Entrenos\Activity;
use Entrenos\User;
use Entrenos\Utils\Facebook;

	if ($_REQUEST['code']) {
        //Getting data from request
	    $fb_code = $_REQUEST['code'];
        $FB_REDIRECT = $base_url . "/oauth/fb_login.php";

        // Requesting access token
        $result_tmp = Facebook::requestFacebook('https://graph.facebook.com/oauth/access_token', array(
                                'client_id' => Facebook::FB_APP_ID, 
                                'redirect_uri' => $FB_REDIRECT,
                                'client_secret' => Facebook::FB_SECRET,
                                'code' => $fb_code),0);
        parse_str($result_tmp, $fb_access);

        //Retrieving email and id from Facebook
        $result = Facebook::requestFacebook('https://graph.facebook.com/me', array('access_token' => $fb_access['access_token']), 0);
        $log->debug("Lo que devuelve facebook: " . $result);
        $fb_result = json_decode($result, true);
        session_start();
        if (array_key_exists('error', $fb_result)) {
            $log->error($fb_result['error']['message']);
            $_SESSION['error'] = "Se ha producido un error en la autenticación a través de Facebook. Inténtelo de nuevo más tarde";
            // Redirecting to error page. 
		    header('Location: ' . $base_url . '/technical_error.php');
        } else {
            //Adding FB access token to have everything in an array
            $fb_result['access_token'] = $fb_access['access_token'];

            $user = User::emailExists($conn, $fb_result['email']);
            if ($user) { 
                // linking accounts
                $log->info("Linking already existing account " . $fb_result['email'] . " with FB one " . $fb_result['id']);
                if ($user->addFb ($conn, $fb_result)) { //ToDo: guardar configuración de sesiones persistentes en BD
                    $_SESSION['login'] = $user->username;
                    $_SESSION['user_id'] = $user->id;
                    $user->registerAccess($conn);
                    $log->info("User " . $_SESSION['login'] . " (id #" . $_SESSION['user_id'] .") starting session using FB credentials");
		            // Redirecting to the logged page. 
		            header('Location: ' . $base_url . '/calendar.php'); 
                } else {
                    $log->error("Failed to add FB data from user " . $fb_result['email'] . " in DB");
                    header('Location: ' . $base_url . '/calendar.php');
                }
            } else {
                //create user in DB
                try {
                    if (User::createFromFb($conn,$fb_result)) {
                        $_SESSION['login'] = $user->username;
                        $_SESSION['user_id'] = $user->id;
                        $log->info("User " . $_SESSION['login'] . " (id #" . $_SESSION['user_id'] .") starting session");
                        // Redirecting to the logged page.
                        header ('Location: ' . $base_url . '/calendar.php');
                    } else {
                        $log->error("Not able to create user " . $fb_result['email'] . " (FB id #" . $fb_result['id'] .")", 0);
                        // Redirecting to the logged page.
                        header ('Location: ' . $base_url . '/index.php');
                    }
                } catch (Exception $e) {
                    $log->error($e->getMessage());
                    $_SESSION['error'] = "No se ha podido autenticar la cuenta de correo electrónico " . $fb_result['email'] . " en Facebook. Inténtelo de nuevo más tarde";
                    // Redirecting to error page. 
		            header('Location: ' . $base_url . '/technical_error.php');
                }
            }
        }
    } else  { 
        echo "No request received";
    }
?>
