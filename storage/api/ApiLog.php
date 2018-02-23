<?php

namespace Storage\Api;

/**
 * Description of ApiLog
 *
 * @author Pardeep Kumar
 */
class ApiLog extends \Database\DealerAdapter
{
    public $table = 'api_log';
    
    public function saveApiLog($params) {
        return $this->save([
                        'mobile'        => $params['mobile'],
                        'session_id'    => $params['session_id'],
                        'ussd_string'   => $params['ussd_string'],
          ]);
    }
    
    public function updateApiLog($id,$output){
        return $this->save([
                        'response'        => $output,
          ],['id'=>$id]);
    }
}
