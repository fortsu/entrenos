<?php
use \PDO;
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/global_config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/../config/logging.php';

    /**
    * Database credentials
    **/
    switch ($_SERVER['SERVER_NAME']) {
        case DEVELOPMENT:
            define('DB_DSN', "mysql:host=127.0.0.1;port=3306;dbname=entrenos_dev");          
            define('DB_USER', DB_USERNAME);
            define('DB_PASS', DB_PASSWORD);
            break;
        case STAGING:
            define('DB_DSN', "mysql:host=127.0.0.1;dbname=entrenos_stg");          
            define('DB_USER', DB_USERNAME);
            define('DB_PASS', DB_PASSWORD);
            break;
        default:
            define('DB_DSN', "mysql:host=127.0.0.1;dbname=entrenos");          
            define('DB_USER', DB_USERNAME);
            define('DB_PASS', DB_PASSWORD);
            break;
    }

    //Connecting to database server (will fail here is there is no DB connection)
    try {
        $conn = new PDO(DB_DSN, DB_USER, DB_PASS, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));
    } catch (PDOException $e) {
        $tmp_id = session_id();
        if(empty($tmp_id)) { 
            session_start(); // added to include error description if it appears
        }
        $error_txt = $e->getMessage();
        $log->error('Error connecting to mysql: ' . $error_txt);
        $_SESSION['error'] = $error_txt;
        // Redirecting error page.
        $url = $base_url . "/technical_error.php";
        $log->info('Redirecting to: ' . $url);
		header('Location: ' . $url);
        exit();
    }
?>
