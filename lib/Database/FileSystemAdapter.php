<?php

/**
 * Description of FileSystemAdapter
 *
 * @author ankitvishwakarma
 */
namespace Database;

use Cache\FileSystem;
abstract class FileSystemAdapter extends DealerAdapter
{
    use Crud, Cache {
        Cache::save insteadof Crud;
        Cache::remove insteadof Crud;
        Crud::save as public saveDb;
        Crud::remove as public removeFromDb;
    }
    
    public function __construct()
    {
        parent::__construct();        
    }
    
    public static function getInstance()
    {
        return FileSystem::getInstance();
    }
    
}
