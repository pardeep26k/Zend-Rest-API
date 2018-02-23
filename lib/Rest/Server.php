<?php

namespace Rest;

use \Exception;
use \Rest\Exception as RestException;
use Config\Site;
use Storage\Api\DCTraffic;

/**
 * Description of Server
 *
 * @author Pardeep Kumar
 */
class Server
{

    //@todo add type hint
    public $url;
    public $method;
    public $params;
    public $format;
    public $cacheDir        = __DIR__;
    public $realm;
    public $mode;
    public $root;
    protected $map          = array();
    protected $errorClasses = array();
    protected $cached;
    
    protected $httpCode = 200;
    
    protected $urlWithVersion = null;
    private $codes = array(
        '100' => 'Continue',
        '200' => 'OK',
        '201' => 'Created',
        '202' => 'Accepted',
        '203' => 'Non-Authoritative Information',
        '204' => 'No Content',
        '205' => 'Reset Content',
        '206' => 'Partial Content',
        '300' => 'Multiple Choices',
        '301' => 'Moved Permanently',
        '302' => 'Found',
        '303' => 'See Other',
        '304' => 'Not Modified',
        '305' => 'Use Proxy',
        '307' => 'Temporary Redirect',
        '400' => 'Bad Request',
        '401' => 'Unauthorized',
        '402' => 'Payment Required',
        '403' => 'Forbidden',
        '404' => 'Not Found',
        '405' => 'Method Not Allowed',
        '406' => 'Not Acceptable',
        '409' => 'Conflict',
        '410' => 'Gone',
        '411' => 'Length Required',
        '412' => 'Precondition Failed',
        '413' => 'Request Entity Too Large',
        '414' => 'Request-URI Too Long',
        '415' => 'Unsupported Media Type',
        '416' => 'Requested Range Not Satisfiable',
        '417' => 'Expectation Failed',
        '500' => 'Internal Server Error',
        '501' => 'Not Implemented',
        '503' => 'Service Unavailable'
    );

    /**
     * The constructor.
     * 
     * @param string $mode The mode, either debug or production
     */
    public function __construct($mode = 'debug', $realm = 'Gaadi.com')
    {
        $this->mode  = $mode;
        $this->realm = $realm;
        //$this->traffic = new DCTraffic();
    }

    public function unauthorized($ask = false)
    {
        if ($ask)
        {
            header("WWW-Authenticate: Basic realm=\"$this->realm\"");
        }
        throw new RestException(401, "You are not authorized to access this resource.");
    }

    public function handle($uri, ...$params)
    {
        $this->url    = $uri;
        $this->urlWithVersion = '/'. $params[0]['version'] . '/' . $uri;
        $this->method = $this->getMethod();
        $this->format = Format::JSON;

        if ($this->method == 'PUT' || $this->method == 'POST')
        {
            $this->data = $this->getData();
        }
        
        list($obj, $method, $params, $this->params, $noAuth, $loggedIn) = $this->findUrl();
        if ($obj)
        {
            if (is_string($obj))
            {
                if (class_exists($obj))
                {
                    $obj = new $obj(false);
                }
                else
                {
                    throw new Exception("Class $obj does not exist");
                }
            }

            $obj->server = $this;
            try
            {
                if (method_exists($obj, 'init'))
                {
                    $obj->init();
                }

                if (!$noAuth && method_exists($obj, 'authorize'))
                {
                    if (!$obj->authorize())
                    {
                        $this->sendData($this->unauthorized(false)); //@todo unauthorized returns void
                        exit;
                    }
                }
                
                if ($loggedIn && method_exists($obj, 'checkLogin'))
                {
                    if (!$obj->checkLogin())
                    {
                        $this->sendData($this->unauthorized(false)); //@todo unauthorized returns void
                        exit;
                    }
                }
                
                $result = call_user_func_array(array($obj, $method), $params);

                if ($result !== null)
                {
                    //$this->traffic->add($this->urlWithVersion, $this->method);
                    echo $this->sendData($result);
                }
            }
            catch (RestException $e)
            {
                $this->handleError($e->getCode(), $e->getMessage());
            }
        }
        else
        {
            $this->handleError(404);
        }
    }

    public function addClass($class, $basePath = '')
    {
        if (is_string($class) && !class_exists($class))
        {

            throw new Exception('Invalid method or class');
        }
        elseif (!is_string($class) && !is_object($class))
        {
            throw new Exception('Invalid method or class; must be a classname or object');
        }
        if (substr($basePath, 0, 1) == '/')
        {
            $basePath = substr($basePath, 1);
        }
        if ($basePath && substr($basePath, -1) != '/')
        {
            $basePath .= '/';
        }
        $this->generateMap($class, $basePath);
    }

    public function addErrorClass($class)
    {
        $this->errorClasses[] = $class;
    }

    public function handleError($statusCode, $errorMessage = null)
    {
        $method = "handle$statusCode";
        foreach ($this->errorClasses as $class)
        {
            if (is_object($class))
            {
                $reflection = new \ReflectionObject($class);
            }
            elseif (class_exists($class))
            {
                $reflection = new \ReflectionClass($class);
            }

            if (isset($reflection))
            {
                if ($reflection->hasMethod($method))
                {
                    $obj = is_string($class) ? new $class() : $class;
                    $obj->$method();
                    return;
                }
            }
        }

        $message = $this->codes[$statusCode] . ($errorMessage && $this->mode == 'debug' ? ': ' . $errorMessage : '');

        $this->setStatus($statusCode);
        $data = [
            'status' => false,
            'code' => $statusCode,
            'message' => $message,
        ];
       // $this->traffic->add($this->urlWithVersion, $this->method, $statusCode);
        $this->sendData($data);
    }

    protected function findUrl()
    {
        $urls = $this->map[$this->method];
        if (!$urls)
            return null;
        foreach ($urls as $url => $call)
        {
            $args = $call[2];

            if (!strstr($url, '$'))
            {
                if ($url == $this->url)
                {
                    if (isset($args['data']))
                    {
                        $params                = array_fill(0, $args['data'] + 1, null);
                        $params[$args['data']] = $this->data;   //@todo data is not a property of this class
                        $call[2]               = $params;
                    }
                    else
                    {
                        $call[2] = [];
                    }
                    return $call;
                }
            }
            else
            {
                $regex = preg_replace('/\\\\\$([\w\d]+)\.\.\./', '(?P<$1>.+)', str_replace('\.\.\.', '...', preg_quote($url)));
                $regex = preg_replace('/\\\\\$([\w\d]+)/', '(?P<$1>[^\/]+)', $regex);
                if (preg_match(":^$regex$:", urldecode($this->url), $matches))
                {
                    $params   = array();
                    $paramMap = array();
                    if (isset($args['data']))
                    {
                        $params[$args['data']] = $this->data;
                    }
                    foreach ($matches as $arg => $match)
                    {
                        if (is_numeric($arg))
                            continue;
                        $paramMap[$arg] = $match;

                        if (isset($args[$arg]))
                        {
                            $params[$args[$arg]] = $match;
                        }
                    }
                    ksort($params);
                    // make sure we have all the params we need
                    end($params);
                    $max = key($params);
                    for ($i = 0; $i < $max; $i++)
                    {
                        if (!array_key_exists($i, $params))
                        {
                            $params[$i] = null;
                        }
                    }
                    ksort($params);
                    $call[2] = $params;
                    $call[3] = $paramMap;
                    return $call;
                }
            }
        }
    }

    protected function generateMap($class, $basePath)
    {
        if (is_object($class))
        {
            $reflection = new \ReflectionObject($class);
        }
        elseif (class_exists($class))
        {
            $reflection = new \ReflectionClass($class);
        }
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);    //@todo $reflection might not be instantiated
        foreach ($methods as $method)
        {
            $doc    = $method->getDocComment();
            $noAuth = strpos($doc, '@noAuth') !== false;
            /**
             * @loggedIn will check if the user is logged in 
             */
            $loggedIn = strpos($doc, '@loggedIn') !== false;
            if (preg_match_all('/@url[ \t]+(GET|POST|PUT|DELETE|HEAD|OPTIONS)[ \t]+\/?(\S*)/s', $doc, $matches, PREG_SET_ORDER))
            {
                $params = $method->getParameters();
                foreach ($matches as $match)
                {
                    $httpMethod = $match[1];
                    $url        = $basePath . $match[2];
                    if ($url && $url[strlen($url) - 1] == '/')
                    {
                        $url = substr($url, 0, -1);
                    }
                    $call = array($class, $method->getName());
                    $args = array();
                    foreach ($params as $param)
                    {
                        $args[$param->getName()] = $param->getPosition();
                    }
                    $call[] = $args;
                    $call[] = null;
                    $call[] = $noAuth;
                    $call[] = $loggedIn;

                    $this->map[$httpMethod][$url] = $call;
                }
            }
        }
    }

    public function getMethod()
    {
        $method   = $_SERVER['REQUEST_METHOD'];
        $override = isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']) ? $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] : (isset($_GET['method']) ? $_GET['method'] : '');
        if ($method == 'POST' && strtoupper($override) == 'PUT')
        {
            $method = 'PUT';
        }
        elseif ($method == 'POST' && strtoupper($override) == 'DELETE')
        {
            $method = 'DELETE';
        }
        return $method;
    }

    public function getData()
    {
        $data = file_get_contents('php://input');
        $data = json_decode($data);

        return $data;
    }

    public function sendData($data)
    {
        
        try
        {
            ob_start();
            header("Cache-Control: no-cache, must-revalidate");
            header("Expires: 0");
            header('Content-Type: ' . $this->format);

            if (is_object($data) && method_exists($data, '__keepOut'))
            {
                $data = clone $data;
                foreach ($data->__keepOut() as $prop)
                {
                    unset($data->$prop);
                }
            }
            $options = 0;
            if ($this->mode == 'debug')
            {
                $options = JSON_PRETTY_PRINT;
            }
            $content = ob_get_clean();
            echo json_encode($data);//$options
        }
        catch (LogicException $e)
        {
            if (ob_get_length() > 0)
            {
                ob_end_clean();
            }

            throw $e;
        }
    }

    public function setStatus($code)
    {
        $this->httpCode = $code;
        if (function_exists('http_response_code'))
        {
            http_response_code($code);
        }
        else
        {
            $protocol = $_SERVER['SERVER_PROTOCOL'] ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
            $code .= ' ' . $this->codes[strval($code)];
            header("$protocol $code");
        }
    }

}
