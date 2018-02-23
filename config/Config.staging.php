<?php

namespace Config;

use ConfigCommon\Config as All;
use Zend\Db\Adapter as DbAdapter;

class Config extends All
{
    public function __construct()
    {
        parent::__construct();
    }

    public static function init()
    {
       /*  ini_set('display_errors', '1');
         error_reporting(E_ERROR);
        ini_set('max_execution_time', '-1');
        * 
        */
    }
    public static function isNotMongo(){
        return true;
    }
    public static function debug($debug){
        if($debug){
        ini_set('display_errors', '2');
        error_reporting(E_ALL);
        }
        else {
            ini_set('display_errors', 0);
        }
    }
    public static function getAdapter()
    {
        return new DbAdapter\Adapter(array(
            'driver'   => 'Pdo_Mysql',
            'host'     => DB_HOST,
            'database' => DB_NAME,
            'username' => DB_USER,
            'password' => DB_PASS,
        ));
    }

    public static function getSlaveAdapter()
    {
        return self::getAdapter();
    }
    

    public static function getCommonAdapter()
    {
        return new DbAdapter\Adapter(array(
            'driver'   => 'Pdo_Mysql',
            'host'     => DB_HOST,
            'database' => DB_NAME,
            'username' => DB_USER,
            'password' => DB_PASS,
        ));
    }
      public static function getMongoAdapter()
    {
        return  $config=[
            'host'      =>DB_HOST_IP,
            'dbname'    =>DB_USER,
            'password'  =>'',
            'username'  =>'',
            'port'      =>''
            ];
    }
    public static function getGaadiAdapter()
    {
        return self::getAdapter();
    }
    
     /**
     * Change the revision whenever you make any change to css file
     * @return int return the current version of css
     */
    public static function getCssRevision()
    {
        return '05102016';
    }
    /**
     * Change the revision whenever you make any change to js file
     * @return int return the current version of js
     */
    public static function getJsRevision()
    {
        return '05102016';
    }
    
    public static function minifyJs()
    {
        return false;
    }
    public static function minifyCss()
    {
        return false;
    }
    
    public static function enableOutputCache()
    {
        return false; 
    }
    

    public static function getUploadPath()
    {
        //return '/home/beta/web/current/origin-assets/';
        return '/home/beta/static/';
    }

    public static function getImageCDNUrl()
    {
        return 'http://static10beta.usedcarsin.in/';
    }
}
