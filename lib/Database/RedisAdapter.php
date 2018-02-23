<?php

/**
 * Description of RedisAdapter
 *
 * @author ankitvishwakarma
 */
namespace Database;

use Config\Config;

use Cache\Redis;
abstract class RedisAdapter extends DealerAdapter
{
   use Crud, Cache {
        Cache::save insteadof Crud;
        Cache::remove insteadof Crud;
        Crud::remove as public removeFromDb;
    }
    
    public function __construct()
    {
        parent::__construct();   
    }
    
    public static function getInstance()
    {
        return Redis::getInstance();
    }
}
