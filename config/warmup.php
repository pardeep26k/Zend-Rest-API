<?php
session_start();

if (!function_exists('p'))
{

    function p($data)
    {
        echo '<pre>';
        print_r($data);
        exit;
    }

}
date_default_timezone_set('Asia/Kolkata');
error_reporting(0);
defined('APPLICATION_ENV') || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'staging'));
require_once ROOT . DS . 'config/Site.php';
Config\Site::registerNameSpace();
Config\Site::includeConfigFiles();
Config\Config::init();

