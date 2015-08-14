<?php

    // TODO: think about Singleton when loading configuration (bootstrap file)
    // http://stackoverflow.com/questions/3313950/php-bootstrapping-basics
    // http://codereview.stackexchange.com/questions/7867/bootstrap-file-safe-and-correct
    // http://codereview.stackexchange.com/questions/52543/converting-bootstrap-file-from-procedural-code-to-oop
    if (defined("CONFIG_AVAILABLE")) {
        return true;
    }

    // Load stuff defined in Composer
    require_once $_SERVER['DOCUMENT_ROOT'] . '/../vendor/autoload.php';

    /**
    * Manually changed properties
    **/
    // Force browsers to load latest version of css and js files
    $fv = "20150812";
    // Overall language
    $fortsu_lang = "es";
    // TODO: base timezone on locale
    date_default_timezone_set('Europe/Madrid');

    /**
    * Automatically loaded ones
    **/
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] === 443) ? "https://" : "http://";
    // $_SERVER['SERVER_NAME'] is defined in server config, $_SERVER['HTTP_HOST'] comes from HTTP header (can be manipulated)
    $base_url = $protocol . $_SERVER['SERVER_NAME'];
    $domain_name = str_replace("www.", "", $_SERVER['SERVER_NAME']);
    $fortsu_domain = $base_url;
    // $_SERVER['DOCUMENT_ROOT'] -> /var/www/html/entrenos (DEV)
    $base_path = $_SERVER['DOCUMENT_ROOT'];
    $tmp_path = $base_path . "/tmp";
    $project_name = basename($_SERVER['DOCUMENT_ROOT']);
    // Legacy
    $cookie_domain = $_SERVER['SERVER_NAME'];

    /**
    * Environment related constants
    * Edit /etc/hosts if not in DNS
    **/
    define('DEVELOPMENT', 'dev.entrenos.fortsu.com');
    define('STAGING', 'stg.entrenos.fortsu.com');
    define('PRODUCTION', 'entrenos.fortsu.com');

    // Prevent loading already present configuration
    define('CONFIG_AVAILABLE', true);

?>
