<?php
namespace Config;
if(php_sapi_name() !== 'cli') {
    include ROOT.'/constants.php';
}
require_once ROOT . DS . 'lib' . DS .'Zend/Loader/StandardAutoloader.php';
use Zend\Http\PhpEnvironment\Request as Request;
use Zend\Http\PhpEnvironment\RemoteAddress as Remote;
class Site
{

    private static $request;
    private static $remote;
    private static $dealerTemplateMapping = [
        'axis'      => 117,
        'carnation' => 84,
        'tata'      => 105,
        'chevrolet' => 97,
        'hdfc'      => 102,
    ];

    private function __construct()
    {
        
    }

    public static function isLocal()
    {
        return 'local' === APPLICATION_ENV;
    }
    
    public static function isStaging()
    {
        return 'staging' === APPLICATION_ENV;
    }
    
    public static function isProduction()
    {
        return 'production' === APPLICATION_ENV;
    }
    
    public static function includeConfigFiles()
    {
        include_once "Config.all.php";
        require_once "db_details.php";
        include_once "Config." . APPLICATION_ENV . ".php";
        define('REMOTE_IP', self::getRemoteIP());
    }
    
    public static function getEnv()
    {
        return APPLICATION_ENV;
    }

    public static function getRequest()
    {
        if (self::$request == null) {
            self::$request = new Request();
        }
        return self::$request;
    }

    public static function getRemote()
    {
        if (self::$remote == null) {
            self::$remote = new Remote();
        }
        return self::$remote;
    }
    
    public static function getDocumentRoot()
    {
        return static::getRequest()->getServer()->get('DOCUMENT_ROOT');
    }
    
    public static function getScheme()
    {
        return self::getRequest()->getServer()->get('REQUEST_SCHEME');
    }

    public static function getHost()
    {
        return self::getRequest()->getServer()->get('HTTP_HOST');
    }

    public static function getScriptName()
    {
        return self::getRequest()->getServer()->get('SCRIPT_NAME');
    }

    public static function getRequestUri()
    {
        return self::getRequest()->getRequestUri();
    }

    public static function getQuery($param)
    {
        return self::getRequest()->getQuery($param);
    }
    public static function getAllRequestParams(){
         $params=\Config\Site::getRequest()->getPost()->toArray();
        if($params['chk']){
            $params= unserialize(urldecode($params['chk']));
        }
        elseif($params['requestdata']){
            $params= (array) json_decode($params['requestdata']);
        }
        return $params;
    }
    public static function getAllRequestGetParams(){
        $params=self::getAllParams();
        if($params['chk']){
            $params= unserialize(urldecode($params['chk']));
        }
         elseif($params['requestdata']){
            $params= (array) json_decode($params['requestdata']);
        }
        return $params;
    }
    public static function getAllParams()
    {
        $uri = \Config\Site::getRequestUri();
        
        $p = [];
        if(!empty($uri))
        {
            $url  = parse_url($uri);
            if(array_key_exists('query', $url))
            {
                $params = explode('&', $url['query']);
                foreach($params as $param)
                {
                    $comp = explode('=', $param);
                    $p[$comp[0]] = $comp[1];
                }
            }
        }
        return array_filter($p);
    }
    
    public static function getParam($param, $default = null, $filter = FILTER_DEFAULT)
    {
        return isset($_GET[$param]) ? filter_input(INPUT_GET, $param, $filter) : (!is_null($default) ? $default : '');
    }
    
    public static function getPost($param, $filter = FILTER_DEFAULT)
    {
        return filter_input(INPUT_POST, $param, $filter);

    }
    
    public static function getRemoteIP()
    {
        return self::getRemote()->getIpAddress();
    }

    public static function getAdminDomains()
    {
        return [
            'chevroletcertified.co.in',
            'dealercentral.gaadi.com'
            ];
    }
    
    public static function getTemplateDealerOwnerMapping()
    {
        return self::$dealerTemplateMapping;
    }
    
    public static function registerNameSpace()
    {
        $loader = new \Zend\Loader\StandardAutoloader(array('autoregister_zf' => true));
        $loader->registerNamespace('Helper', ROOT . DS . 'lib' . DS . '../helper');
        $loader->registerNamespace('Config', ROOT . DS . 'lib' . DS . '../config');
        $loader->registerNamespace('Ui\Site', ROOT . DS . 'lib' . DS . '../ui/site');
        $loader->registerNamespace('Cache', ROOT . DS . 'lib' . DS . 'Cache');
        $loader->registerNamespace('Database', ROOT . DS . 'lib' . DS. 'Database');
        $loader->registerNamespace('Storage\Dealer', ROOT . DS . 'lib' . DS . '../storage/dealer');
        $loader->registerNamespace('Storage\Insurance', ROOT . DS . 'lib' . DS . '../storage/insurance');
        $loader->registerNamespace('Storage\Stock', ROOT . DS . 'lib' . DS . '../storage/stock');
        $loader->registerNamespace('Storage\Gaadi', ROOT . DS . 'lib' . DS . '../storage/gaadi');
        $loader->registerNamespace('Storage\App', ROOT . DS . 'lib' . DS . '../storage/app');
        $loader->registerNamespace('Storage\Centraldb', ROOT . DS . 'lib' . DS . '../storage/centraldb');
        $loader->registerNamespace('Storage\Api', ROOT . DS . 'lib' . DS . '../storage/api');
        $loader->registerNamespace('Widgets', ROOT . DS . 'lib' . DS . '../widgets');
        $loader->registerNamespace('League\Plates', ROOT . DS . 'lib' . DS . 'Template/league/plates');
        $loader->registerNamespace('Seo', ROOT . DS . 'lib' . DS . 'Seo');
        $loader->registerNamespace('Template', ROOT . DS . 'lib' . DS . 'Template');
        $loader->registerNamespace('Ui\Site\Pages',  ROOT . DS  . 'ui/site/pages');
        $loader->registerNamespace('Dealer', ROOT . DS . 'lib' . DS . 'Dealer');
        $loader->registerNamespace('UsedCar', ROOT . DS . 'lib' . DS . 'UsedCar');
        $loader->registerNamespace('Mobile', ROOT . DS . 'lib' . DS . 'Mobile');
        $loader->registerNamespace('Inventory', ROOT . DS . 'lib' . DS . 'Inventory');
        $loader->registerNamespace('Minifier', ROOT . DS . 'lib' . DS . 'Minifier');
        $loader->registerNamespace('Ui\Site', ROOT . DS . 'lib' . DS . '../ui/site');
        $loader->registerNamespace('SmsManager', ROOT . DS . 'lib' . DS . 'SmsManager');
        $loader->registerNamespace('CommonMailSender', ROOT . DS . 'lib' . DS . 'CommonMailSender');
        $loader->registerNamespace('InventoryManager', ROOT . DS . 'lib' . DS . 'InventoryManager');
        $loader->registerNamespace('CRM', ROOT . DS . 'lib' . DS . 'CRM');
        $loader->registerNamespace('Utility', ROOT . DS . 'lib' . DS . 'Utility');
        $loader->registerNamespace('Gaadi', ROOT . DS . 'lib' . DS . 'Gaadi');
        $loader->registerNamespace('Notification', ROOT . DS . 'lib' . DS . 'Notification');
        $loader->registerNamespace('Rest', ROOT . DS . 'lib' . DS . 'Rest');
        $loader->registerNamespace('Api\Dc\Object', ROOT . DS . 'api' . DS . 'dc' . DS . 'object');
        $loader->registerNamespace('User\Pages', ROOT . DS . 'user/pages');
        $loader->registerNamespace('Lib', ROOT . DS . 'lib');
        $loader->registerNamespace('User\Pages\Ajax', ROOT . DS . 'user/pages/ajax');
        $loader->registerNamespace('Storage\Flexi', ROOT . DS . 'storage/flexi');
        $loader->registerNamespace('Module', ROOT . DS . 'user/modules');
        $loader->registerNamespace('Ui\Site\Themes',  ROOT . DS  . 'ui/site/themes');
        $loader->registerNamespace('ConsoleKit', ROOT . DS . 'lib/ConsoleKit');
        $loader->registerNamespace('console\commands', ROOT . DS . 'console/commands');
        $loader->registerNamespace('Payment\Gateway\WorldLine', ROOT . DS . 'lib/Payment/Gateway/WorldLine');
        $loader->registerNamespace('Payment', ROOT . DS . 'lib' . DS . 'Payment');
        $loader->register();

    }
    
    public static function increamentOPCVersion($dealerId)
    {
        $revision = \Cache\FileSystem::get('opc-'. $dealerId);
        
        if(isset($revision)) {
            $revision = sprintf('%07d', (int) ($revision+1));
            \Cache\FileSystem::set('opc-'. $dealerId, $revision, \Helper\TimeHelper::YEAR);
        }
    }
    
    public static function getOPCVersion($dealerId)
    {
        
        $revision = \Cache\FileSystem::get('opc-'. $dealerId);
        
        if(!isset($revision)) {
            $revision = sprintf('%07d', 1);
            \Cache\FileSystem::set('opc-'. $dealerId, $revision, \Helper\TimeHelper::YEAR);
        }
        return $revision;
    }
  
    public static function env($key)
    {
        if ($key == 'HTTPS') {
            if (!empty($_SERVER)) {
                if (isset($_SERVER['HTTP_X_HTTPS']) && stripos($_SERVER['HTTP_X_HTTPS'], 'on') !== false) {
                    return true;
                }
                if (isset($_SERVER['HTTPS']) && in_array($_SERVER['HTTPS'], array('on', 'ON'))) {
                    return true;
                }
            }
            return (strpos(self::env('SCRIPT_URI'), 'https://') === 0);
        }

        if ($key == 'SCRIPT_NAME') {
            if (self::env('CGI_MODE') && isset($_ENV['SCRIPT_URL'])) {
                $key = 'SCRIPT_URL';
            }
        }

        $val = null;
        if (isset($_SERVER[$key])) {
            $val = $_SERVER[$key];
        }
        elseif (isset($_ENV[$key])) {
            $val = $_ENV[$key];
        }
        elseif (getenv($key) !== false) {
            $val = getenv($key);
        }

        if ($key == 'REMOTE_ADDR' && $val == self::env('SERVER_ADDR')) {
            $addr = self::env('HTTP_PC_REMOTE_ADDR');
            if ($addr != null) {
                $val = $addr;
            }
        }

        if ($val !== null) {
            return $val;
        }

        switch ($key) {
        case 'SCRIPT_FILENAME':
            if (defined('SERVER_IIS') && SERVER_IIS === true) {
                return str_replace('\\\\', '\\', self::env('PATH_TRANSLATED'));
            }
            break;
        case 'DOCUMENT_ROOT':
            $offset = 0;
            if (!strpos(self::env('SCRIPT_NAME'), '.php')) {
                $offset = 4;
            }
            return substr(self::env('SCRIPT_FILENAME'), 0, strlen(self::env('SCRIPT_FILENAME')) - (strlen(self::env('SCRIPT_NAME')) + $offset));
          break;
        case 'PHP_SELF':
            return str_replace(self::env('DOCUMENT_ROOT'), '', self::env('SCRIPT_FILENAME'));
          break;
        case 'CGI_MODE':
            return (PHP_SAPI == 'cgi');
          break;
        case 'HTTP_BASE':
            $host = self::env('HTTP_HOST');
            if (substr_count($host, '.') != 1) {
                return preg_replace('/^([^.])*/i', null, self::env('HTTP_HOST'));
            }
            return '.' . $host;
          break;
        }
        return null;
    }

    public static  function isAjax()
    {
        return self::env('HTTP_X_REQUESTED_WITH') === "XMLHttpRequest";
    }

    public static  function isHTTPS()
    {
        return self::env('HTTPS');
    }
    
    public static function addModules($modules)
    {
        $loader = new \Zend\Loader\StandardAutoloader(array('autoregister_zf' => true));
        foreach($modules as $module)
        {
            $loader->registerNamespace('Module\\' . \Helper\TextHelper::pascalCase($module), ROOT . DS . 'user' . DS . 'modules' . DS . $module);
            $loader->registerNamespace('Module\\' . \Helper\TextHelper::pascalCase($module) . '\\Ajax', ROOT . DS . 'user' . DS . 'modules' . DS . $module . DS . 'ajax');
        }
        $loader->register();
    }
    
    public static function addApiVersions($versions)
    {
        $loader = new \Zend\Loader\StandardAutoloader(array('autoregister_zf' => true));
        
        foreach($versions as $version)
        {
            $loader->registerNamespace('Api\\DC\\'. strtoupper($version) , ROOT . DS . 'api' . DS . 'dc' . DS . $version);
        }
        $loader->register();
    }
    public static function addApiInsVersions($versions)
    {
        $loader = new \Zend\Loader\StandardAutoloader(array('autoregister_zf' => true));
        
        foreach($versions as $version)
        {
            $loader->registerNamespace('Api\\INS\\'. strtoupper($version) , ROOT . DS . 'api' . DS . 'ins' . DS . $version);
        }
        $loader->register();
    }
    public static function addApiStockVersions($versions)
    {
        $loader = new \Zend\Loader\StandardAutoloader(array('autoregister_zf' => true));
        
        foreach($versions as $version)
        {
            $loader->registerNamespace('Api\\STOCK\\'. strtoupper($version) , ROOT . DS . 'api' . DS . 'stock' . DS . $version);
        }
        $loader->register();
    }
    public static function runMysqlQuery($sql,$type)
        {
            global $db;
            switch ($type){
                case 'object':
                                    return $db->fetchObjects($sql,'slave');
                   break;
                case 'singleArray':
                                    return $db->fetchRow($sql,'slave');
                    break;
                case 'multipleArray':
                                    return $db->fetchRows($sql,'slave');
                    break;
               case 'singleArrayAssoc':
                                    return $db->fetchAssoc($sql,'slave');
                    break;
               case 'multipleArrayAssocs':
                                    return $db->fetchAssocs($sql,'slave');
                    break;

            }
        }
}
