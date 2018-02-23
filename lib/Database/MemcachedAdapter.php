<?php

/**
 * Description of MemcachedAdapter
 *
 * @author ankitvishwakarma
 */
namespace Database;

use Config\Config;
use Cache\Memcached;
abstract class MemcachedAdapter extends DealerAdapter
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
        return Memcached::getInstance();
    }
}
