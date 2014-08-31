<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/global_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/database.php';
use Entrenos\User;
use Entrenos\Token;
use Entrenos\Utils\Cookie;
use \Exception;

    if (isset($_REQUEST['action'])) {
        switch($_REQUEST['action']) {
            case "check":
            // 1.- Retrieve DB data for provided email/login
            // 2.a) Display error message if user does not exist
            // 2.b) If user exists, hash provided password and compare with DB
            // 2.b.1) If hashed password is not salted (i.e. ':' not found) -> old version with sha1 (insecure!, TODO: migrate!)
            // 3.a) Display error message if credentials don't match
            // 3.b) Start session if credentials match (TODO: improve cookie security!). Migrate DB hash if still using sha1
                session_start();
                $current_user = User::get_from_login($conn, $_REQUEST['login']);
                if ($current_user !== FALSE) {
                    if (strpos($current_user->password, ":") === FALSE) {
                        // Old sha1 hash version -> migration after correct validation
                        $sha1_password = sha1($_REQUEST['input_password']);
                        if ($sha1_password === $current_user->password) {
                            $log->info("Old password schema in DB, migration ongoing");
                            // Migrate what is stored in DB: <hashing algorithm>:<salt>:<hash>
                            $current_user->hash_password = User::create_hash($_REQUEST['input_password']);
                            $current_user->update_passwd($conn);
                        } else {
                            $log->error("Password does not match. Data entered: " . $_REQUEST['login'] . "/" . $_REQUEST['input_password'] . " | " . $_SERVER['REMOTE_ADDR'] . " | " . $_SERVER['HTTP_USER_AGENT']);
                            $_SESSION['login_error'] = "Los datos introducidos no son válidos<br />Por favor, revíselos: <b>" . $_REQUEST['login'] . "</b> / <b>" . $_REQUEST['input_password'] . "</b>";
                            header ("Location: " . $base_url);
                            exit;
                        }
                    } else {
                        // Secure password schema -> https://crackstation.net/hashing-security.htm
                        if (!User::check_hash($current_user->password, $_REQUEST['input_password'])) {
                            $log->error("Password does not match. Data entered: " . $_REQUEST['login'] . "/" . $_REQUEST['input_password'] . " | " . $_SERVER['REMOTE_ADDR'] . " | " . $_SERVER['HTTP_USER_AGENT']);
                            $_SESSION['login_error'] = "Los datos introducidos no son válidos<br />Por favor, revíselos: <b>" . $_REQUEST['login'] . "</b> / <b>" . $_REQUEST['input_password'] . "</b>";
                            header ("Location: " . $base_url);
                            exit;
                        }
                    }
                    // Manage "remember me" feature -> cookie
                    if (isset($_REQUEST['remember'])) {
                        $remember = $_REQUEST['remember'];
                    } else {
                        $remember = FALSE;
                    }
                    // Check if user has been already validated
                    if ($current_user->enabled) {
                        $_SESSION['login'] = $current_user->username;
                        $_SESSION['user_id'] = $current_user->id;
                        if ($remember) {
                            //No secure, http only. Expire in 30 days
                            $new_token = Cookie::create_token($current_user->id);
                            $new_cookie = Cookie::issue_new($current_user->id, $new_token, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
                            // Store uc and ut in browser
                            // Although datetime and strtotime are both UTC, default timezone is not (TODO: based on locale)
                            $expiration_dt = new DateTime($new_cookie->expiration, new DateTimeZone('UTC'));
                            $cookie_result["uc"] = setcookie("uc", $new_cookie->hash, $expiration_dt->getTimestamp(), "/", $_SERVER['SERVER_NAME'], FALSE, Cookie::HTTP_ONLY);
                            $cookie_result["ut"] = setcookie("ut", $new_token, $expiration_dt->getTimestamp(), "/", $_SERVER['SERVER_NAME'], FALSE, Cookie::HTTP_ONLY);
                            $log->debug("Cookie set (" . json_encode($cookie_result) . ") | User " . $_SESSION['user_id'] . " | " . $_SERVER['SERVER_NAME']);
                            // Save in DB
                            $num_stored = $new_cookie->save($conn);
                            $log->debug("Saved (result " . $num_stored . ") cookie entry " . $new_cookie->id . " in DB");
                            // Populate session
                            $current_user = new User(array('id'=> $new_cookie->user_id));
                            $current_user->userFromId($conn);
                            $_SESSION['user_id'] = $current_user->id;
                            $_SESSION['login'] = $current_user->username;
                            $_SESSION['maps'] = $current_user->maps;
/*
                            $cookie_result = setcookie("user_id", $_SESSION['user_id'], time() + 3600*24*30, "/", $cookie_domain, FALSE, TRUE);
                            $log->info("Cookie set (" . (int)$cookie_result . ") for user " . $_SESSION['user_id'] . " on " . $cookie_domain);
*/
                        } else {
                            $log->info("User " . $_SESSION['user_id'] . " requested no cookie");
                        }
                        $current_user->registerAccess($conn);
                        $log->info("User " . $_SESSION['login'] . " (id #" . $_SESSION['user_id'] .") starting session");
		                // Redirecting to the logged page. 
		                header('Location: ' . $base_url . '/calendar.php');
                        exit;
                    } else {
                        $log->error("User " . $current_user->id . " exists but is currently disabled | " . $_SERVER['REMOTE_ADDR'] . " | " . $_SERVER['HTTP_USER_AGENT']);
                        $_SESSION['error'] = "El usuario " . $_REQUEST['login'] . " existe pero no está habilitado. Póngase en contacto con ayuda@fortsu.com";
                        // Redirecting error page. 
		                header('Location: ' . $base_url . '/technical_error.php');
                        exit;
                    }
                } else {
                    $log->error("User does not exist in DB. Data entered: " . $_REQUEST['login'] . "/" . $_REQUEST['input_password'] . " | " . $_SERVER['REMOTE_ADDR'] . " | " . $_SERVER['HTTP_USER_AGENT']);
                    $_SESSION['login_error'] = "Los datos introducidos no son válidos<br />Por favor, revíselos: <b>" . $_REQUEST['login'] . "</b> / <b>" . $_REQUEST['input_password'] . "</b>";
                    header ("Location: " . $base_url);
                    exit;
                }
                break;
            case "check_prop":
                try {
                    $field = $_REQUEST['field'];
                    $result = User::propExists($conn, array("name" => $field, "value" => $_REQUEST[$field]));
                    echo json_encode(array("result" => $result));
                } catch (Exception $e) {
                    $log->error($e->getMessage());
                    echo json_encode(array("result" => "error"));
                }
                break;
            case "creation":
                $log->info("User creation: " . json_encode($_REQUEST));
                // Check input fields to prevent empty and placeholder values
                $placeholder_values = array("username" => "nombre de usuario",
                                            "input_password" => "contraseña");
                foreach($placeholder_values as $field_name => $field_placeholder) {
                    if (empty($_REQUEST[$field_name]) or ($_REQUEST[$field_name] === $placeholder_values[$field_name])) {
                        $log->error("Invalid " . $field_name . " provided");
                        header('HTTP/1.1 400 Bad Request');
                        exit;
                    }
                }
                // Checking provided email
                if (empty($_REQUEST['emailaddress'])) {
                    $log->error("Not possible to send reminder without email address");
                    header('HTTP/1.1 400 Bad Request');
                    exit;
                }
                if(filter_var($_REQUEST["emailaddress"], FILTER_VALIDATE_EMAIL) === false) {
                    $log->error("Provided email address (" . $_REQUEST["emailaddress"] . ") is not a valid one");
                    header('HTTP/1.1 400 Bad Request');
                    exit;
                }

                if (isset($_REQUEST['remember'])) {
                    $remember = $_REQUEST['remember'];
                } else {
                    $remember = 0;
                }
                $user_data = array('username' => $_REQUEST['username'],
                                    'email' => $_REQUEST['emailaddress'],
                                    'hash_password' => User::create_hash($_REQUEST['input_password']),
                                    'remember' => $remember,
                                    'enabled' => 0);
                session_start();
                if (!User::emailExists($conn, $user_data['email'])) {
                    try {
                        $log->info("User does not exist yet, creating");
                        $new_user = new User($user_data);
                        if ($new_user->create($conn, $_REQUEST['remote_ip'])) {
                            # Show message indicating that email has been sent and instructions need to be followed
                            $log->info("User " . $new_user->username . " successfully created. Awaiting from account validation. Email: " . $new_user->email);
                            $_SESSION['success'] = "Se ha enviado un correo electrónico a <b>" . $new_user->email . "</b> para validar la nueva cuenta. Por favor revise su bandeja de entrada y siga las instrucciones que se detallan en dicho mensaje.";
                        } else {
                            $log->error("Error when trying to create user " . $new_user->username . " | email: " . $new_user->email);
                            $_SESSION['error'] = "Se ha producido un error al crear la cuenta de usuario. Por favor contacte con ayuda@fortsu.com";
                        }
                    } catch (Exception $e) {
                        $log->error($e->getMessage());
                        $_SESSION['error'] = "Se ha producido un error al crear la cuenta de usuario. Por favor contacte con ayuda@fortsu.com";
                    }
                } else {
                    $log->error("Email address " . $user_data['email'] . " already exists in FortSu");
                    $_SESSION['error'] = "La dirección de correo electrónico <b>" . $user_data['email'] . "</b> ya existe en FortSu. <a href=\"password_request.php\">¿Quizás olvidó la contraseña?</a>";
                }
                header ('Location: ' . $base_url . '/register.php');
                break;
            case "request_password":
                $log->info("Password forgotten: " . implode("|", $_REQUEST));
                // Checking provided data
                if (empty($_REQUEST['emailaddress'])) {
                    $log->error("Not possible to send reminder without email address");
                    header('HTTP/1.1 400 Bad Request');
                    exit;
                }
                if(filter_var($_REQUEST["emailaddress"], FILTER_VALIDATE_EMAIL) === false) {
                    $log->error("Provided email address (" . $_REQUEST["emailaddress"] . ") is not a valid one");
                    header('HTTP/1.1 400 Bad Request');
                    exit;
                }
                $email = $_REQUEST['emailaddress'];
                try {
                    # check if email exists -> user_id
                    $current_user = User::emailExists($conn, $email);
                    if ($current_user) {
                        if ($current_user->enabled) {
                            # generate token
                            $current_token = new Token(TRUE);
                            $current_token->user_id = $current_user->id;                        
                            $current_token->remote_ip = $_REQUEST['remote_ip'];
                            # store it in db
                            $current_token->save($conn);
                            # send email to user
                            $current_token->send_new_passwd($email);
                            $log->info("Email sent to " . $email . " to recover password from user " . $current_user->id . " | " . $_SERVER['REMOTE_ADDR'] . " | " . $_SERVER['HTTP_USER_AGENT']);
                            session_start();
                            $_SESSION['success'] = "<p>Se ha enviado un correo electrónico a <b>" . $email . "</b> con instrucciones para establecer una nueva contraseña. Por favor revise su bandeja de entrada y siga las instrucciones que se detallan en dicho mensaje.</p>";
                            header('Location: ' . $base_url . '/password_request.php');
                        } else {
                            session_start();
                            $log->error("User " . $current_user->id . " exists but is currently disabled | " . $_SERVER['REMOTE_ADDR'] . " | " . $_SERVER['HTTP_USER_AGENT']);
                            $_SESSION['error'] = "El usuario <b>" . $current_user->username . "</b> existe pero no está habilitado. Póngase en contacto con ayuda@fortsu.com";
                            // Redirecting error page. 
		                    header('Location: ' . $base_url . '/technical_error.php');
                        }
                    } else {
                        $log->error("No user found for provided email address " . $email);
                        session_start();
                        $_SESSION['error'] = "La dirección de correo electrónico " . $email . " no corresponde a ningún usuario";
                        // Redirecting error page. 
		                header('Location: ' . $base_url . '/technical_error.php');
                    }
                } catch (Exception $e) {
                    $log->error($e->getMessage() . " | Trace: " . $e->getTraceAsString());
                }
                break;
            case "new_password":
                try {
                    # print form data
                    $log->info("Creating new password: " . implode("|", $_REQUEST));
                    $current_user = new User(array('id'=>$_REQUEST['user_id']));
                    # save hash in db for user
                    $current_user->hash_password = User::create_hash($_REQUEST['input_password']);
                    $current_user->update_passwd($conn);
                    $log->info("Succesfully changed password for user " . $current_user->id);
                    # remove token from db
                    $current_user->remove_token($_REQUEST['token'], $conn);
                    $log->debug("Removed token " . $_REQUEST['token'] . " for user " . $current_user->id);
                    // Making sure no cookie data is stored in DB nor in browser
                    // DB
                    $num_removed = Cookie::remove_all($conn, $current_user->id);
                    $log->debug("Removed " . $num_removed . " cookie DB entries related to user_id " . $current_user->id);
                    // Browser
                    $cookie_result = Cookie::invalidate_all();
                    $log->debug("Set cookie to expire (" . json_encode($cookie_result) . ") on " . $_SERVER['SERVER_NAME']);
                    session_start();
                    $_SESSION['success'] = "<p>La contraseña ha sido cambiada satisfactoriamente</p>";
                    header('Location: ' . $base_url . '/password_request.php');
                } catch (Exception $e) {
                    $log->error($e->getMessage() . " | Trace: " . $e->getTraceAsString());
                }
                break;
            case "select_map":
                // Mode PARANOID ON: Check request user_id and csrf with session ones
                session_start();
                $result = array();
                $maps_options = array("osm" => "OpenStreetMaps", "gmaps" => "Google Maps", "bing" => "Bing Maps");
                $log->info("Request " . json_encode($_REQUEST));
                if (($_REQUEST["user_id"] === $_SESSION["user_id"]) and ($_REQUEST["csrf"] === $_SESSION["csrf"])) {
                    $user_data = array("id" => $_REQUEST["user_id"], "maps" => $_REQUEST["maps_choice"]);
                    $current_user = new User($user_data);
                    try {
                        $update_result = $current_user->update_maps_choice($conn);
                        $log->info("Updated map options to " . $current_user->maps . " for user " . $current_user->id);
                        $result["success"] = "Preferencia de mapas actualizada correctamente a " . $maps_options[$_REQUEST["maps_choice"]];
                        $_SESSION['maps'] = $current_user->maps;
                    } catch (Exception $e) {
                        $log->error($e->getMessage() . " | Trace: " . $e->getTraceAsString());
                        $result["error"] = "Se ha producido un error al intentar seleccionar " . $maps_options[$_REQUEST["maps_choice"]];
                    }
                } else {
                    $log->error("Security checks failed. Request (user_id: " . $_REQUEST["user_id"] . ", csrf: " . $_REQUEST["user_id"] . ") | Session (user_id: " . $_SESSION["user_id"] . ", csrf: " . $_SESSION["csrf"] . ") | Check possible attack");
                    header('HTTP/1.1 403 Forbidden');
                    exit;
                }
                echo json_encode($result);
                exit;
                break;
            default:
                $log->error("Action not recognized: " . implode("|", $_REQUEST));
        }
    } else {
        $log->error("Malformed form, no action provided: " . implode("|", $_REQUEST));
        header('HTTP/1.1 400 Bad Request');
        exit;
    }
?>
