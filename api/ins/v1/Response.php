<?php
/**
 * Created by PhpStorm.
 * User=> Pardeep Kumar
 * Date=> 19/09/17
 * Time=> 12=>35 PM
 */

namespace Api\INS\V1;

use Cache\FileSystem;
use Config;
use Config\Site;
use Storage\Api\DCTraffic;

class Response {

    /**
     * This is for updating the version of the parameters passed 
     * since no cache is generated for the parameters we 
     * have to update the parameters version from here 
     */

    private $salt = 'U3KqyrewdMuCotTS';
    private $monthRange = 6;
    public $publicKey = 'restricted';
    const SESSION_FLAG = '1';
    const CACHE_REFRESH_PER_SEC = 's';
    const CACHE_REFRESH_PER_MIN = 'i';
    const CACHE_REFRESH_PER_HR = 'H';
    const CACHE_REFRESH_PER_DAY = 'd';
    const CACHE_REFRESH_PER_MONTH = 'm';
    const CACHE_REFRESH_PER_YEAR = 'Y';

    public function __construct() {
        
    }

    public function authorize() {
       if (!\Config\Site::isProduction())
        {
            return true;
        }
        //if api key is passed through header
        $apiKey = \Config\Site::env('HTTP_APIKEY');

        //if api key is passed in query string
        if (!$apiKey) {
            $apiKey = \Config\Site::getAllRequestParams();
        }
        //return md5($this->salt . date('M') . date('Y')) == $apiKey;
        return $this->salt == $apiKey['apikey'];
    }

    /**
     * Returns a JSON Array object
     *
     * @url    POST /getup
     */
    public function getup() {
        $requestParams = \Config\Site::getAllRequestParams();
        $this->setErrorReporting($requestParams);
        $api_log_id=$this->saveApiLog($requestParams);
        switch ($requestParams['method']) {
            case 'getInsuranceCases':
                $response = $this->getInsuranceCase($requestParams);
                break;
            default: $response = array('status' => 'F', 'msg' => 'Method not valid', 'error' => 'Method not valid');
                break;
        }
        if ($api_log_id && $response) {
              $this->updateApiLog($api_log_id,$response);
        }
        return $response;
    }

    /**
     * Returns a JSON Array object
     *
     * @url    GET /putup
     */
    public function putup() {
        $requestParams = \Config\Site::getAllRequestGetParams();
        $this->setErrorReporting($requestParams);
        $api_log_id=$this->saveApiLog($requestParams);
        switch ($requestParams['method']) {
            case 'getInsuranceCases':
                $response = $this->getInsuranceCase($requestParams);
                break;
            default: $response = array('status' => 'F', 'msg' => 'Method not valid', 'error' => 'Method not valid');
                break;
        }
        if ($api_log_id && $response) {
             $this->updateApiLog($api_log_id,$response);
        }
        return $response;
    }

    private function saveApiLog($requestParams) {
              $apiLogObj     = new \Storage\Api\ApiLog();
              $id= $apiLogObj->saveApiLog($requestParams);
              return array('status'=>true,'type'=>'mysql','_id'=>$id);
    }
    private function updateApiLog($id, $response) {
         $apiLogObj     = new \Storage\Api\ApiLog();
         $response='hello';
         return $apiLogObj->updateApiLog($id['_id'],$response);
    }
    
    public function setErrorReporting($params){
        if($params['debug']){
                \Config\Config::debug($params['debug']);
        }
    }

}
