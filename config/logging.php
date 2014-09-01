<?php

require $_SERVER['DOCUMENT_ROOT'] . '/../config/global_config.php';

switch ($_SERVER['SERVER_NAME']) {
    case DEVELOPMENT:
        $env = "dev";
        break;
    case STAGING:
        $env = "stg";
        break;
    default:
        $env = "prd";
        break;
}

Logger::configure(dirname(__FILE__) . "/log4php_" . $env . ".xml");
$log = Logger::getLogger($env);
if ($log === false) {
    error_log("Problems when starting logger: " . json_encode(error_get_last()),0);
    error_log("Problems when starting logger: " . json_encode(error_get_last()), 1, "admin@fortsu.com");
} 

?>
