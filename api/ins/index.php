<?php 
/**
 * Created by PhpStorm.
 * User: Pardeep Kumar
 * Date: 19/09/17
 * Time: 12:13 PM
 */
define('ROOT', dirname(dirname(dirname(__FILE__))));
define('DS', DIRECTORY_SEPARATOR);
      
if(!function_exists('p')) {
    function p($data)
    {
        echo '<pre>';
        print_r($data); exit;
    }
}
require ROOT.DS.'config/warmup.php';
$uri =  Config\Site::getQuery('uri');
$uricom = explode('/', $uri);
$version = current($uricom);
preg_match('/^v[0-9]*/', $version, $matches);
$v = 'v1';
if(!empty($matches))
{
    $v = current($matches);
    array_shift($uricom);
    
    
}
\Config\Site::addApiInsVersions([
    'v1',
    'v2',
    'v3',
]);
ini_set('display_error', '1');
error_reporting(E_ALL);
$uri = implode('/', $uricom);
$server = new \Rest\Server('debug');

$controllerName = 'response'; // Todo: make it dynamic for differnt version of apis.

$controller     = 'Api\\INS\\'.  strtoupper($v).'\\' . ucwords($controllerName);

$server->addClass($controller);
$server->handle($uri, ['version' => $v]);
