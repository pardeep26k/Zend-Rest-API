<?php

namespace Storage\Api;

/**
 * Description of DCTraffic
 *
 * @author Ankit Vishwakarma <ankit.vishwakarma@gaadi.com>
 */
class DCTraffic extends \Database\DealerAdapter
{
    public $table = 'api_dc_traffic';
    
    public function add($url, $method, $code = 200)
    {
        if(\Config\Config::captureApiTraffic())
        {
            $trafficData = $this->getByFields([
                'url' => $url,
                'method' => $method,
                'http_code' => $code,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                'date' => date('Y-m-d'),
            ]);
            if(empty($trafficData))
            {
                parent::save([
                    'url'       => $url,
                    'method'    => $method,
                    'http_code' => $code,
                    'hits'      => 1,
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                    'date' => date('Y-m-d'),
                ]);
            }
            else
            {
                parent::save(['id' => $trafficData['id'], 'hits' => $trafficData['hits'] + 1]);
            }
        }
    }
}
