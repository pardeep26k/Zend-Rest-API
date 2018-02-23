<?php

namespace Storage\Insurance;

use Database\DealerAdapter;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Predicate\In;
use Zend\Db\Sql\Predicate\Operator;
use Storage\Insurance\IbApiLog;
use Storage\Insurance\IbQuotations;
use Storage\Insurance\IbEvaluatorLivePhotos;
use Storage\Insurance\IbCarDetails;
use Storage\Insurance\UsedCarCertification;
use Storage\Insurance\DcDealers;
use Storage\Insurance\IbBankList;
use Storage\Insurance\DcDealerOtherINfo;
use Storage\Dealer\CityList;
use Helper\MailHelper;

/**
 * Description of IbCarDealerMapper
 *
 * @author shashikant kumar
 */
class IbCarDealerMapper extends DealerAdapter {

    public $table = 'ib_car_dealer_mapper';
    private $true = 1;
    private $false = 0;
    private $preferredIdv = 5;
    private $livePhotoFilter = [
                  'doc_front'   =>'2',
                  'doc_left'    =>'3',
                  'doc_back'    =>'4',
                  'doc_right'   =>'5',
                  'doc_odometer'=>'7',
                 'doc_vinplate' =>'23',
                   'doc_winext' =>'33',
                 'doc_winint'   => '39'
    ];
    private $appVersion=45;
    private $nomineeAge=18;
    private $tagValue  = array("2", "3", "4", "5", "7", "23", "33", "39");
    public function getInsuranceQuotes($params) {
        $requestValidate = $this->validateQuoteRequest($params);
        if ($requestValidate['status'] == 'T') {
            if ($params['type'] == 'count') {
                $this->getInsuranceQuotesCoundData($params);
            } else {
                $this->getInsuranceQuotesData($params);
            }
        } else {
            return $requestValidate;
        }
    }

    public function checkProcessIDWIthCarID($processId, $car_id, $notappld = 0) {
        $result = $this->select(function(Select $select) use ($processId, $car_id, $notappld) {
                    $select->columns(['id']);
                    $select->where(array('id' => $processId, 'car_id' => $car_id));
                    if ($notappld == 0) {
                        $select->where(array('policy_id' => '0'));
                    }
                 //echo str_replace('"', '', $select->getSqlString()); die;
                })->toArray();
        return $result[0]['id'] > $this->false ? $this->true : $this->false;
    }
    


    public function getAllRequestByRegNo($regNo) {
        return $results = $this->select(function(Select $select) use ($regNo) {
                    $select->columns(['id', 'ib_carid', 'policy_id', 'dt_added', 'status']);
                    $select->join(array('ibd' => 'ib_car_details'), "ibd.id = ib_car_dealer_mapper.ib_carid", array('regno'), 'INNER');
                    $select->where($select->where->EqualTo('ibd.regno', $regNo));
                    $select->order('ib_car_dealer_mapper.dt_added DESC');
                })->toArray();
    }
    public function checkofflinebtnStatus($carId, $ucdId) {
        $today = date('Y-m-d H:i:s');
        $results = $this->select(function(Select $select) use ($carId, $ucdId) {
                    $select->columns(['id', 'date_off_quote']);
                    $select->where(array('dealer_id' => $ucdId, 'car_id' => $carId, 'offline_ins' => '1', 'policy_id' => '0'));
                    $select->order('id DESC');
                })->toArray();
        if ($results[0]['date_off_quote'] != '0000-00-00 00:00:00') {
            $expiredDate = date('Y-m-d H:i:s', strtotime(date($results[0]['date_off_quote'])) + (3600 * 24));
            $status = ($today > $expiredDate) ? 'true' : 'false';
        } else {
            $status = 'true';
        }
        return $status;
    }

    public function validateInsuranceStep($processId, $step) {
        $stepDone = 'step_done_' . $step;
        return $this->save([$stepDone => '1'], ['id' => $processId]);
    }

    public function checkAlreadyBooked($regNo) {
        $todayplus45daysdate = date("Y-m-d", strtotime("+45 day"));
        $todayplus60daysdate = date("Y-m-d", strtotime("-60 day"));
        $results = $this->select(function(Select $select) use ($regNo) {
                    $select->columns(['car_id', 'new_policy_end_date', 'strtDate' => new \Zend\Db\Sql\Expression('Date(statusUpdated)'), 'policy_id', 'status']);
                    $select->join(array('ibcd' => 'ib_car_details'), "ibcd.id = ib_car_dealer_mapper.ib_carid", array(), 'INNER');
                    $regNumber = trim(strtolower(str_replace(array(' ', '-', '_'), '', $regNo)));
                    $select->where(array(new \Zend\Db\Sql\Expression('lower(ibcd.regno)') => $regNumber));
                    $select->where(new Operator('ib_car_dealer_mapper.policy_id', '>', '0'));
                    $select->where(new In('ib_car_dealer_mapper.policy_id', ['1', '0']));
                    $select->order('ib_car_dealer_mapper.id DESC');
                    $select->limit(1);
                   //echo str_replace('"', '', $select->getSqlString()); exit;
                })->toArray();
        if ($results[0]['new_policy_end_date'] == '0000-00-00') {
            return $this->true;
        } elseif ($results[0]['new_policy_end_date'] > $todayplus45daysdate && $results[0]['status'] == '1' && $results[0]['car_id'] == '0') {
            return $this->false;
        } elseif ($results[0]['strtDate'] > $todayplus60daysdate && $results[0]['status'] == '1' && $results[0]['car_id'] != '0') {
            return $this->false;
        } elseif ($results[0]['policy_id'] > 0 && $results[0]['status'] == 0) {
            return $this->false;
        } else {
            return $this->true;
        }
    }

    public function getRegNoForInspected($processId) {

        return $result = $this->select(function(Select $select) use ($processId) {
                    $select->columns(['regno']);
                    $select->join(array('ibcd' => 'ib_car_details'), "ib_car_dealer_mapper.ib_carid=ibcd.id", [], 'inner');
                    $select->where(array('ib_car_dealer_mapper.id' => $processId));
                    $select->limit(1);
                })->toArray();
    }

    public function checkAlreadeyRequest($processId) {
        $data = $this->select(function (Select $select) use ($processId) {
                    $select->columns(['id']);
                    $select->where(['id' => $processId, 'status' => '0']);
                    $select->where(new Operator('policy_id', '>', '0'));
                })->toArray();
        return $data[0]['id'] > 0 ? 0 : 1;
    }

    public function checkInsuranceStep($processId, $step) {
        $stepDone = 'step_done_' . $step;
        $data = $this->select(function (Select $select) use ($processId, $stepDone) {
                    $select->columns(['id']);
                    $select->where(['id' => $processId, $stepDone => '1']);
                })->toArray();
        return $data[0]['id'] > 0 ? 1 : 0;
    }

    public function checkProcessOfflineinProcess($processId) {
        $data = $this->select(function (Select $select) use ($processId) {
                    $select->columns(['id']);
                    $select->where(['id' => $processId, 'policy_id' => '0', 'offline_ins' => '1']);
                    $select->order('id desc');
                    $select->limit(1);
                })->toArray();
        if ($data[0]['id'] > 0) {
            return false;
        } else {
            return true;
        }
    }
    
    public function uploadInsuranceDocSqlCaseOne($processId) {
            $data = $this->select(function (Select $select) use ($processId) {
                    $select->columns(['id']);
                    $select->where(['id' => $processId]);
                    $select->where("(doc_rc_copy!='' or doc_rc_copy_2!='') and doc_form_29!='' and doc_form_30_image1!='' and doc_form_30_image2!=''");
                    })->toArray();
                    return $data;
    }
    
    public function uploadInsuranceDocSqlCaseTwo($processId) {
        return $data = $this->select(function (Select $select) use ($processId) {
                    $select->columns(['id']);
                    $select->where(['id' => $processId]);
                    $select->where(" ( doc_rc_copy != '' 
                            OR doc_rc_copy_2 != '' ) 
                            AND ( doc_prev_policy_copy_image1 != '' 
                            OR doc_prev_policy_copy_image2 != '' 
                            OR doc_prev_policy_copy_image3 != '') ");
                    //echo str_replace('"', '', $select->getSqlString()); die;

                })->toArray();
    }
    public function uploadInsuranceDocSqlCaseThree($processId) {
        return $data = $this->select(function (Select $select) use ($processId) {
                    $select->columns(['id']);
                    $select->where(['id' => $processId]);
                    $select->where(" ( doc_rc_copy != '' 
                            OR doc_rc_copy_2 != '' )");
                })->toArray();
    }
    
    public function livePhotoPrefilling($request) {
        $ibQuotations          = new IbQuotations();
        $ibEvaluatorLivePhotos = new IbEvaluatorLivePhotos();
        $isLiveData            = $ibQuotations->setColumns(['is_live_photo'])->setWhere(['selected' => '1', 'ib_mapper_id' => $request['process_id']])->get()->result();
        if ($isLiveData[0]['is_live_photo'] == 0)
        {
            $this->save([
                'doc_reup_to_mygaadi' => date('Y-m-d H:i:s'),
                'bu_hit'              => '0',
                'live_profilling'     => '0'], ['id' => $request['process_id']]);
            return array('status' => 'T', 'msg' => 'invalid Image', 'liveflag' => 'false');
        }
        if ($request['car_id']) 
        {
            $carId = $request['car_id'];//'1406408';
            $reqeustData['evaluationData'] = '{"apikey":"U3KqyrewdMuCotTS","output":"json","car_id":"' . $carId . '","hours":"24"}';
            foreach ($reqeustData as $k => $v) {
                $requestValue .= $k . '=' . $v . '&';
            }
            $requestValue = rtrim($requestValue, '&');
            $url =GETIMAGELIVEDATA;//'http://inspection.gaadi.com/webapis/gcloud/getImageLiveData'; 
            $curlResponse = \Helper\InsuranceHelper::curlPostInfo($url,$requestValue, $reqeustData['evaluationData'],'getImageLiveData', $request['ucdid'], 0);
            $response = json_decode($curlResponse,true);
            if ($response['status'] == 'T') 
            {
                $cnt = 0;
                foreach ($response['data'] as $value) 
                {
                    if ((in_array($value['tagID'], $this->tagValue)) && ($value['imgName'] != '')) 
                    {
                        $cnt++;
                    }
                }
                if ($cnt == 8) 
                {
                    $checkInsuranceStep = $this->checkInsuranceStep($request['process_id'], 4);
                    if (!$checkInsuranceStep)
                    {
                        return array('status' => 'F', 'msg' => 'Please Validate Previous Step', 'error' => 'Please Validate Previous Step');
                    }
                    $cnt = 0;
                    foreach ($response['data'] as $value) {
                        if ((in_array($value['tagID'], $this->tagValue)) && ($value['imgName'] != '')) {
                            $liveDocName      = array_search($value['tagID'],$this->livePhotoFilter);
                            $evaluatorId      = $ibEvaluatorLivePhotos->setColumns(['id'])->setWhere(['live_doc_name'=>$liveDocName,'ib_mapper_id'=>$request['process_id']])->get()->result();
                            $saveData=[
                                        'ib_mapper_id'     =>$request['process_id'],
                                        'live_doc_name'    =>$liveDocName,
                                        'live_image'       =>$value['imgName'],
                                        'live_date'        =>$value['captureTime'],
                                        'latitude'         =>$value['latitude'],
                                        'longitude'        =>$value['longitude'],
                                        'addDate'          =>date('Y-m-d H:i:s')
                                    ];
                            if ($evaluatorId[0]['id'] > 0) {
                                    $ibEvaluatorLivePhotos->save($saveData,['ib_mapper_id'=>$request['process_id']]);
                            } else {
                                    $ibEvaluatorLivePhotos->save($saveData);
                            }
                              $cnt++;
                        }
                    }
                    $this->save([
                            'doc_reup_to_mygaadi' => date('Y-m-d H:i:s'),
                            'bu_hit'              => '0',
                            'live_profilling'     => '1'], ['id' => $request['process_id']]);
                    $this->validateInsuranceStep($request['process_id'], 5);

                    return array('status' => 'T', 'msg' => 'valid Image', 'liveflag' => 'true');
                }
                else {
                    return array('status' => 'T', 'msg' => 'invalid Image', 'liveflag' => 'false');
                }
            } else {
                return array('status' => 'F', 'msg' => 'Data Not Found', 'error' => 'Data Not Found');
            }
        }
        return array('status' => 'T', 'msg' => 'Car ID is blank', 'error' => 'Car ID is blank', 'liveflag' => 'false');
    }
    
    public function selectInsurancePaymentMode($request) {
    
        $ibCardetails         = new IbCarDetails();
        $checkRequest         = $this->checkAlreadeyRequest($request['process_id']);
        $processId            = $request['process_id'];
        if (!$checkRequest) {
            $resultPolicy = $this->select(function(Select $select) use ($processId) {
                        $select->columns(['policy_id']);
                        $select->where(array('id' => $processId, 'status' => '0'));
                        $select->where(new Operator('policy_id', '>', '0'));
                    })->toArray();
            $request['type']='count';
            $resultDashboard = $ibCardetails->getInsuranceQuotes($request);
            unset($resultDashboard['status']);
            $dataMonth=end($resultDashboard['monthwise']);
            unset($resultDashboard['monthwise']);
            $resultDashboard['monthwise'][]=$dataMonth;
            $responseMessage = "Insurance Booking Already Initiated. Please Use REQUEST ID " . $resultPolicy[0]['policy_id'] . " for any other query";
            return array('status' => 'T', 'msg' => $responseMessage, 'policy_id' => $resultPolicy[0]['policy_id'], 'dashboard' => $resultDashboard);
        }
        $checkProcessId = $this->checkProcessIDWIthCarID($processId > $this->false ? $processId : $this->false, $request['car_id'] > $this->false ? $request['car_id'] : $this->false);
        if (!$checkProcessId) {
            return array('status' => 'F', 'msg' => 'Please Enter Correct Process ID and Car ID', 'error' => 'Please Enter Correct Process ID and Car ID');
        }
        if ($request['background_upload'] != '1') {
            $checkInsStep = $this->checkInsuranceStep($processId, 5);
            if (!$checkInsStep) {
                return array('status' => 'F', 'msg' => 'Please Validate Previous Step', 'error' => 'Please Validate Previous Step');
            }
        }
        $ValidRespose= \Helper\InsuranceHelper::selectInsurancePaymentModeValidData($request);
        if($ValidRespose['status']=='F'){
            return $ValidRespose;exit;
        }
        if ($request['payment_mode']) 
        {
            $this->save([
                'payment_mode' => ($request['payment_mode']) ? $request['payment_mode'] : '',
                'cheque_no'    => ($request['cheque_no']) ? $request['cheque_no'] : '',
                'draft_no'     => ($request['draft_no']) ? $request['draft_no'] : '',
                'issuing_bank' => ($request['issuing_bank']) ? $request['issuing_bank'] : ''
                    ],['id' => $processId]);
        }
        return $response=$this->getInsuranceFinalDetails($request);
    }
    

    public function getInsuranceFinalDetails($request) {
        $dcDealers            = new DcDealers();
        $ibCardetails         = new IbCarDetails();
        $resultData           = $this->insuranceFinalDetailsSql($request);
        $allData              = (array) $resultData[0];
        $check = $this->checkAlreadyBooked($allData['regno']);
        if (!$check) {
            return array('status' => 'F', 'msg' => 'Insurance Already Booked or In Process For This Reg No', 'error' => 'Insurance Already Booked or In Process For This Reg No');
        }
        $ValidRespose = \Helper\InsuranceHelper::insFinalDataValidation($allData);
        if ($ValidRespose['status'] == 'F') {
            return $ValidRespose;
            exit;
        }

        $response=$this->makeInsurancePaymentModeData($allData,$request);
        if ($response['status'] == '200') {
            $this->save(['policy_id' => $response['ticketId'],'request_date' => date('Y-m-d H:i:s')], ['id' => $request['process_id']]);
            
            $data['policy_id']  = $response['ticketId'];
            $data['car_id']     = $request['car_id'];
            $data['process_id'] = $request['process_id'];
             
            $cust_name = str_replace('  ', ' ', $allData['first_name'] . ' ' . $allData['middle_name'] . ' ' . $allData['last_name']);
            $mmv = $allData['make'] . ' ' . $allData['model'] . ' ' . $allData['version'];
            $dcDealers->sendInsEmailSMS($request['ucdid'], $allData['regno'], $cust_name, $allData['mobile'], $data['policy_id'], $mmv, $allData['insurer_name_trimmed']);
            $request['type']='count';
            $resultDashboard = $ibCardetails->getInsuranceQuotes($request);
            unset($resultDashboard['status']);
            $dataMonth=end($resultDashboard['monthwise']);
            unset($resultDashboard['monthwise']);
            $resultDashboard['monthwise'][]=$dataMonth;
            $responseMessage = "Your request for issuing policy has been taken. Please quote REQUEST ID " . $data['policy_id'] . " in all future communications. Please do not turn off the internet connection before all Insurance documents are uploaded.";
            return array('status' => 'T', 'msg' => $responseMessage, 'policy_id' => $data['policy_id'], 'dashboard' => $resultDashboard);
        }
        
        else if (!$response['status']) {
            foreach ($response['error'] as $key => $value) {
                    return array('status' => 'F', 'msg' => $value['message'], 'error' =>$value['message']);
                    break;
                    exit;
            }
        } else {
            return $response;
            exit;
        }
    }
    public function makeInsurancePaymentModeData($allData,$request){
        $cityList             = new \Storage\Dealer\CityList();
        $stateList            = new \Storage\Dealer\StateList();
        $ibApiLog             = new \Storage\Insurance\IbApiLog();
        $usedCarCertification = new UsedCarCertification();
        $IbBankList           = new IbBankList();
        $customer                = [];
        $address                 = [];
        $vehicle                 = [];
        $ticket                  = [];
        $insuranceUcd            = [];
        $insProposerDetails      = [];
        $correspondanceAddr      = [];
        $vehicleRegistrationAddr = [];
        $deviceArr               = [];

        $customer['first_name']      = $allData['dealername'];
        $customer['last_name']       = '';
        $customer['email']           = $allData['dealeremail'];
        $customer['mobile']          = $allData['dealermobile'];
        $customer['birthday']        = '';
        $customer['gender']          = '';
        $customer['occupation']      = '';
        $customer['dealer_id']       = $allData['dealer_id'];
        $customer['gcd_code']        = $allData['gcd_code'];
        $result['customer']          = $customer;

        $address['address_line_1']   = $allData['dealeraddress'];
        $dealerCentralCityId         = $cityList->getCentralCityId($allData['dealercity_id']);
        $address['city']             = $dealerCentralCityId;
        $dealerCentralStateId        = $stateList->getCentralStateId($allData['dealercity_id']);
        $address['state']            = $dealerCentralStateId;
        $address['country_code']     = 1;
        $address['postal_code']      = $allData['dealerpincode'];
        $address['address_type']     = 1;
        $address['is_primary']       = 1;
        $address['dealer_org']       = $allData['dealerorganization'];
        $result['address']           = $address;

        $vehicle['vehicle_type']     = 2;
        $vehicle['registration_no']  = $allData['regno'];
        $vehicle['make_id']          = $allData['make_id'];
        $vehicle['model_id']         = $allData['model_id'];
        $vehicle['variant_id']       = $allData['variant_id'];
        if ($allData['reg_year'] != "" && $allData['reg_month'] != "" && $allData['reg_date'] != "") {
            $vehicle['registration_date'] = $allData['reg_year'] . '-' . $allData['reg_month'] . '-' . $allData['reg_date'];
        } else {
            $vehicle['registration_date'] = '0000-00-00';
        }
        if ($allData['myear'] != "" && $allData['mm'] != "") {
            $vehicle['manufacturing_date'] = $allData['myear'] . '-' . $allData['mm'] . '-01';
        } else {
            $vehicle['manufacturing_date'] = '0000-00-00';
        }
        $vehicle['engine_no']        = $allData['engine_no'];
        $vehicle['chassis_no']       = $allData['chasis_no'];
        $vehicle['rto']              = $allData['rto'];
        $vehicle['engine_capacity']  = $allData['engine_capacity'];
        $vehicle['seating_capacity'] = $allData['SeatingCapacity'];
        $result['vehicle']           = $vehicle;

        $ticket['firstName']         = $allData['first_name'];
        $ticket['lastName']          = '';
        $ticket['email']             = $allData['email'];
        $ticket['mobile']            = $allData['mobile'];
        $result['ticket']            = $ticket;

        $insuranceUcd['policyRequestDate'] = date('Y-m-d H:i:s');
        $insuranceUcd['policySource']      = 'ucd';
        $insuranceUcd['policyMedium']      = 'Online';
        $insuranceUcd['policySubSource']   = 'Gcloud';
        $insuranceUcd['caseType']          = $allData['business_type'];
        $insuranceUcd['paymentMode']       = $allData['payment_mode'];
        $insuranceUcd['premium']           = $allData['net_premium'];
        if ($allData['car_id'] > 0) {
            $insuranceUcd['inspection_report_link'] = $allData['doc_condition_report'];
        } else {
            $insuranceUcd['inspection_report_link'] = '';
        }
        $insuranceUcd['car_id']        = $allData['car_id'];
        $insuranceUcd['brokerQuoteId'] = $allData['insurance_case_id'];
        $insuranceUcd['planId']        = $allData['plan_id'];
        $insuranceUcd['proposalId']    = '';
        $insuranceUcd['otp']           = $allData['otp_value'];
        $insuranceUcd['customerGSTIN'] = $allData['gstno'];
        $insuranceUcd['pinNumber']     = '';
        $insuranceUcd['pinStatus']     = '';
        $insuranceUcd['car_financed']  = $allData['car_financed'];
        $insuranceUcd['finance_company_id'] = $allData['finance_company'];
        $insuranceUcd['insurerId'] = $allData['insurer_id'];
        if ($allData['car_id'] > $this->false) {
            $datainspect                           = $usedCarCertification->checkCertificationExpiryDate($allData['car_id']);
            $insuranceUcd['inspection_id']         = $datainspect[0]['id'];
            $insuranceUcd['inspection_date']       = $datainspect[0]['certification_date_by_ce'];
            $insuranceUcd['inspection_expiry_date']= date('Y-m-d H:i:s', strtotime($datainspect[0]['certification_date_by_ce']. ' + 27 day'));
        }
        $result['insuranceUcd'] = $insuranceUcd;
        if ($allData['selected_idv'] > $this->false || $allData['isZeroDep'] > $this->false || $allData['voluntaryDedAmt'] > $this->false || $allData['passenger_charges'] > $this->false || $allData['is_paid_driver'] > $this->false ) {
            $insOnlineQotes['quoteFilter']         = $this->true;
        } else {
            $insOnlineQotes['quoteFilter']         = $this->false;
        }
        $insOnlineQotes['addOns']                  = $this->false;
        $insOnlineQotes['addOnsPremium']           = '';
        $insOnlineQotes['chequeNumber']            = ($allData['payment_mode'] == 'cheque') ? $allData['cheque_no'] : '';
        $insOnlineQotes['serviceTaxRate']          = $allData['service_tax'];
        $insOnlineQotes['serviceTaxAmount']        = $allData['service_tax_amt'];
        $insOnlineQotes['premium']                 = $allData['net_premium'] - $allData['service_tax_amt'];
        $insOnlineQotes['zeroDep']                 = $allData['isZeroDep'];
        $insOnlineQotes['zeroDepPremium']          = $allData['zero_dep_premium'];
        $insOnlineQotes['consumables']             = ($allData['consumables_premium'] > $this->false) ? $this->true : $this->false;
        $insOnlineQotes['consumablesPremium']      = $allData['consumables_premium'];
        $insOnlineQotes['engineProtection']        = ($allData['eng_protection_premium'] > $this->false) ? $this->true : $this->false;
        $insOnlineQotes['engineProtectionPremium'] = $allData['eng_protection_premium'];
        $insOnlineQotes['ncbProtection']           = $allData['ncbProtection'];
        $insOnlineQotes['ncbProtectionPremium']    = $allData['ncb_protection_premium'];
        $insOnlineQotes['invoiceCover']            =  $allData['invoiceCover'];
        $insOnlineQotes['invoiceCoverPremium']     = $allData['invoice_cover_premium'];
        $insOnlineQotes['keyCover']                = $allData['keyCover'];
        $insOnlineQotes['keyCoverPremium']         = $allData['key_cover_premium'];
        $insOnlineQotes['prePolicyInsurerId']      = $allData['prev_insurer'];
        $insOnlineQotes['prePolicyNumber']         = $allData['prev_policy_no'];
        if ($allData['issuing_bank']) {
            $issuingBankid                         = $IbBankList->getBankId($allData['issuing_bank']);
        }
        $insOnlineQotes['issuingBankId']           = ($allData['payment_mode'] == 'cheque') ? $issuingBankid : '';
        $insOnlineQotes['driverCover']             = $allData['is_paid_driver'];
        $insOnlineQotes['isRsaCover']              = '';
        $insOnlineQotes['rsa']                     = '';
        $insOnlineQotes['ownerDriver']             = $allData['owner_driver'];
        $insOnlineQotes['loseOfPersonalBeloging']  = '';
        $insOnlineQotes['emergencyTransportHotelCover'] = '';
        $insOnlineQotes['isMedicalCover']          = '';
        $insOnlineQotes['isAmbulanceCover']        = '';
        $insOnlineQotes['isHydrostaticLockCover']  = '';
        $insOnlineQotes['isHospitalCover']         = '';
        $insOnlineQotes['isWinshieldCover']        = '';
        $insOnlineQotes['isLossOfUseCover']        = '';
        $insOnlineQotes['previousYearZeroDep']     = $allData['is_prev_year_zero_dep'];
        $result['insOnlineQotes']                  = $insOnlineQotes;
        
        $ownerType = '';
        if (strtolower($allData['vehicle_owner']) == 'individual') {
            $ownerType = 1;
        } elseif (strtolower($allData['vehicle_owner']) == 'company') {
            $ownerType = 2;
        }
        $insProposerDetails['ownerType']                   = $ownerType;
        $insProposerDetails['gender']                      = $allData['gender'];
        $insProposerDetails['maritalStatus']               = $allData['marital_status'];
        $insProposerDetails['dob']                         = $allData['dob'];
        $insProposerDetails['annualIncome']                = $allData['annual_income'];
        $insProposerDetails['occupation']                  = $allData['occupation'];
        $insProposerDetails['nomineeName']                 = $allData['nominee'];
        $insProposerDetails['nomineeAge']                  = $allData['nominee_age'];
        $insProposerDetails['relationshipWithNominee']     = $allData['relation_with_nominee'];
        $insProposerDetails['appointeeName']               = $allData['appointee_name'];
        $insProposerDetails['relationshipWithAppointee']   = $allData['appointee_relation'];
        $insProposerDetails['panCard']                     = $allData['pancard_no'];
        $insProposerDetails['aadhaarCard']                 = $allData['aadhaar_no'];
        $insProposerDetails['isCorrespondenceAddressSame'] = $allData['is_cross_add'];
         
        $cusCorrsCentralCityId                    = $cityList->getCentralCityId($allData['city_id']);
        $correspondanceAddr['city']               = $cusCorrsCentralCityId;
        $correspondanceAddr['pincode']            = $allData['pin'];
        $correspondanceAddr['address']            = $allData['address'];
        $insProposerDetails['correspondanceAddr'] = $correspondanceAddr;

        $vehCentralCityId                              = $cityList->getCentralCityId($allData['crosscity_id']);
        $vehicleRegistrationAddr['regCityId']          = $vehCentralCityId;
        $vehicleRegistrationAddr['regPincode']         = $allData['crosspin'];
        $vehicleRegistrationAddr['regAddress']         = $allData['crossaddress'];
        $insProposerDetails['vehicleRegistrationAddr'] = $vehicleRegistrationAddr;
        $result['insProposerDetails']                  = $insProposerDetails;
        
        $deviceArr['device_name']                      = $request['device_name'];
        $deviceArr['android_version']                  = $request['android_version'];
        $deviceArr['network_name']                     = $request['network_name'];
        $deviceArr['network_provider']                 = $request['network_provider'];
        $deviceArr['APP_VERSION']                      = $request['APP_VERSION'];
        $result['device_info']                         = json_encode($deviceArr);
        if ($dealerCentralStateId == '') {
            return array('status' => 'F', 'msg' => 'Dealer State Id Cannot be empty', 'error' => 'Dealer State Id Cannot be empty');
        }
        if ($cusCorrsCentralCityId == '') {
            return array('status' => 'F', 'msg' => 'Customer corresponding central city Id Cannot be empty', 'error' => 'Customer corresponding central city Id Cannot be empty');
        }

        if ($request['process_id'] > $this->true) {
            $pcheck = $ibApiLog->checkProcessAlreadyReceived($request['process_id']);
            if (!$pcheck) {
                return array('status' => 'F', 'msg' => 'Insurance Request Already Received or In Process For This Reg No', 'error' => 'Insurance Request Already Received or In Process For This Reg No');
            }
        }
        
        $apiUrl = INSURANCECRMDEV . "tickets/create/productType/7";
        //$data = json_encode($result);
        $curlResponse = \Helper\InsuranceHelper::curlPostMoreDetails($apiUrl, $request, $result, 'putInsuranceMoreDeatil', $request['ucdid'], $request['process_id'],$request);
        $response = json_decode($curlResponse,true);
        //$response =array('status'=>200,'id'=>"INS-UCD-2594","ticketId"=>34315,"message"=>"INS UCD SR Ticket has been created successfully.");
        return $response;
    }
    
    public function insuranceFinalDetailsSql($request) {
        return $sql = $this->select(function(Select $select) use ($request) {
                    $select->columns(['*']);
                    $select->join(array('ibq' => 'ib_quotations'), $this->table . ".id=ibq.ib_mapper_id", ['insurance_case_id', 'service_tax', 'service_tax_amt', 'net_premium', 'consumables_premium', 'eng_protection_premium', 'ncbprotection', 'ncb_protection_premium', 'invoicecover', 'invoice_cover_premium', 'keycover', 'key_cover_premium', 'owner_driver', 'insurance_case_id', 'broker_id', 'insurer_id', 'insurer_name_trimmed', 'selected_idv', 'isZeroDep', 'zero_dep_premium', 'voluntaryDedAmt', 'passenger_charges','is_paid_driver'], 'inner');
                    $select->join(array('ibcd' => 'ib_car_details'), $this->table . ".ib_carid=ibcd.id", ['engine_no', 'chasis_no', 'reg_date', 'mm', 'myear', 'reg_month', 'reg_year', 'regno', 'make', 'model', 'version', 'rto' => 'rto_reg_city'], 'inner');
                    $select->join(array('d' => 'dc_dealers'), new \Zend\Db\Sql\Expression($this->table . ".dealer_id=d.used_car_dealer_id and d.status='1'"), ['dealerorganization' => 'organization', 'gcd_code'], 'inner');
                    $select->join(array('dum' => 'dc_dealer_user_mapping'), new \Zend\Db\Sql\Expression("dum.dealer_id=d.id and dum.status='1'"), [], 'inner');
                    $select->join(array('du' => 'dc_dealer_user'), new \Zend\Db\Sql\Expression("dum.user_id=du.id AND du.user_type='9' AND du.status='1'"), ['dealername' => 'name', 'dealeremail' => 'email', 'dealermobile' => 'mobile'], 'inner');
                    $select->join(array('dcs' => 'dc_showrooms'), new \Zend\Db\Sql\Expression("dcs.dealer_id=d.id AND dcs.is_primary='1' AND dcs.status='1'"), ['dealeraddress' => 'address', 'dealercity_id' => 'city_id', 'dealerpincode' => 'pincode'], 'inner');
                    $select->join(array('mv' => 'model_version'), 'ibcd.versionid=mv.db_version_id', ['fuel_type' => 'uc_fuel_type', 'seatingcapacity', 'engine_capacity' => 'displacement', 'variant_id' => 'central_variant_id'], 'left');
                    $select->join(array('cm' => 'car_make'), 'mv.mk_id = cm.id', ['make_id' => 'central_make_id'], 'left');
                    $select->join(array('mm' => 'make_model'), 'mv.model_id=mm.id', ['model_id' => 'central_model_id'], 'left');
                    $select->where(array('ib_car_dealer_mapper.id' => $request['process_id'], 'ibq.selected' => '1'));
                    //$select->where(array('ib_car_dealer_mapper.id' => 41, 'ibq.selected' => '1'));
                    //  echo str_replace('"', '', $select->getSqlString()); exit;
                })->toArray();
    }     
    
    
    
    public function saveNonListRequestInMapper($request,$id){
                $data=[
                    'car_id'                => '0', 
                    'ib_nlist_carid'        => $id, 
                    'dealer_id'             => $request['ucdid'],
                    'dt_added'              => date('Y-m-d h:i:s'), 
                    'latest'                => '1', 
                    'se_id'                 => ($request['SERVICE_EXECUTIVE_ID'])?$request['SERVICE_EXECUTIVE_ID']:'0',
                    'dc_id'                 => ($request['dc_id'])?$request['dc_id']:'0',
                    'sfa_user_id'           => ($request['sfa_user_id'])?$request['sfa_user_id']:'0',
                    'prev_insurer'          => ($request['prev_insurer'])?$request['prev_insurer']:'0',
                    'is_prev_year_zero_dep' => ($request['is_prev_year_zero_dep'])?$request['is_prev_year_zero_dep']:'0',
                    'cng_lpg_kit'           => ($request['cng_lpg_kit'])?$request['cng_lpg_kit']:'0',
                    'cng_lpg_kit_value'     => ($request['cng_lpg_kit_value'])?$request['cng_lpg_kit_value']:'0',
                    'kit_type'              => ($request['kit_type'])?$request['kit_type']:'0',
                    ];
               return  $this->save($data);
    }
     public function saveRenewalRequestInMapper($request,$ib_car_id){
                $business_type=(strtolower($request['ins_type'])=='renewal')?'Renewal':'Renewal Breakin';
                if($request['cng_lpg_kit'] == 0){
                    $request['kit_type'] = '';
                    $request['cng_lpg_kit_value'] = '';
                }
                $data=[
                    'car_id'                => '0', 
                    'ib_carid'              => $ib_car_id, 
                    'dealer_id'             => $request['ucdid'],
                    'dt_added'              => date('Y-m-d h:i:s'), 
                    'latest'                => '1', 
                    'business_type'         => $business_type,
                    'se_id'                 => ($request['SERVICE_EXECUTIVE_ID'])?$request['SERVICE_EXECUTIVE_ID']:'0',
                    'dc_id'                 => ($request['dc_id'])?$request['dc_id']:'0',
                    'sfa_user_id'           => ($request['sfa_user_id'])?$request['sfa_user_id']:'0',
                    'prev_insurer'          => ($request['prev_insurer'])?$request['prev_insurer']:'0',
                    'is_prev_year_zero_dep' => ($request['is_prev_year_zero_dep'])?$request['is_prev_year_zero_dep']:'0',
                    'claim_taken_in_previous_year' =>($request['claim_taken_in_previous_year'])?$request['claim_taken_in_previous_year']:'0',
                    'ncb'                   => ($request['claim_taken_in_previous_year']>0)?'0':$request['ncb'],
                    'cng_lpg_kit'           => $request['cng_lpg_kit'],
                    'cng_lpg_kit_value'     => $request['cng_lpg_kit_value'],
                    'prev_policy_end_date'  => $request['prev_policy_end_date'],
                    'kit_type'              => $request['kit_type'],
                    'policy_expired_ninty' => ($request['policy_expired_ninty']) ? $request['policy_expired_ninty'] : '0'

                    ];
               return  $this->save($data);
    }
    
    public function saveInspectedRequestInMapper($record,$ibcarid,$request,$cngLpgCheck,$bType){
        return $this->save([
                'car_id'        => $record['id'],
                'ib_carid'      => $ibcarid,
                'dealer_id'     => $request['ucdid'],
                'cng_lpg_kit'   => $cngLpgCheck['cnglpgendorsementinrc'],
                'kit_type'      => $cngLpgCheck['flipment'],
                'dt_added'      => date('Y-m-d H:i:s'),
                'latest'        => '1',
                'se_id'         => ($request['SERVICE_EXECUTIVE_ID']) ? $request['SERVICE_EXECUTIVE_ID'] : '0',
                'dc_id'         => ($request['dc_id']) ? $request['dc_id'] : '0',
                'business_type' => $bType,
                'sfa_user_id'   => ($request['sfa_user_id']) ? $request['sfa_user_id'] : '0',
                'policy_expired_ninty' => ($request['policy_expired_ninty']) ? $request['policy_expired_ninty'] : '0',
                'ncb'           => ($request['ncb']) ? $request['ncb'] : '',
                'is_prev_year_zero_dep'  => ($request['is_prev_year_zero_dep']) ? $request['is_prev_year_zero_dep'] : '',
                'prev_policy_end_date'  => ($request['prev_policy_end_date']) ? $request['prev_policy_end_date'] : '',
                'prev_insurer'          => ($request['prev_insurer'])?$request['prev_insurer']:'0',
                'claim_taken_in_previous_year' => ($request['claim_taken_in_previous_year'])?$request['claim_taken_in_previous_year']:'0',
            ]);
    }
    
     public function addInsuranceMoreDetails($request) {
        $checkProcessId = $this->checkProcessIDWIthCarID($request['process_id'] > $this->false ? $request['process_id'] : $this->false, $request['car_id'] > $this->false ? $request['car_id'] : $this->false);
        if (!$checkProcessId) {
          return array('status' => 'F', 'msg' => 'Please Enter Correct Process ID and Car ID', 'error' => 'Please Enter Correct Process ID and Car ID');
        }
        $checkInsStep = $this->checkInsuranceStep($request['process_id'], 3);
        if (!$checkInsStep) {
           return array('status' => 'F', 'msg' => 'Please Validate Previous Step', 'error' => 'Please Validate Previous Step');
        }
        $ValidRespose= \Helper\InsuranceHelper::checkInsuranceMoreDetailsData($request);
        if($ValidRespose['status']=='F'){
            return $ValidRespose;exit;
        }
        
        if ($request['car_financed'] == '0') {
            $request['finance_company_slug'] = '';
        }
        
        $this->save([
            'car_financed'         =>trim($request['car_financed']),
            'finance_company'      =>trim(addslashes($request['finance_company_slug'])),
            'prev_policy_insurer'  =>$request['prev_policy_insurer'],
            'prev_policy_no'       =>$request['prev_policy_no'],
            'new_policy_start_date'=>$request['new_policy_start_date'],
            'new_policy_end_date'  =>$request['new_policy_end_date']
            ],['id'=>$request['process_id']]);
        $data['car_id']     = $request['car_id'];
        $data['process_id'] = $request['process_id'];
        $this->validateInsuranceStep($request['process_id'], 4);
        return array('status' => 'T', 'msg' => 'Insurance More Details Added', 'car_id' => $data['car_id'], 'process_id' => $data['process_id']);
    }

    public function updateCarDataByType($request,$type){
        $ibCarDetails=new IbCarDetails();
        $result=  $ibCarDetails->getCarDetailPrimaryId($request);
        if($type=='inspected'){
              $ibCarDetails->save([
               'reg_date'=>($request['reg_date'])?$request['reg_date']:''
           ],['id'=>$result['id']]);
        }
        else if ($type=='renewal'){
            $ibCarDetails->save([
                 'engine_no'=>($request['engine_no'])?$request['engine_no']:'',
                 'chasis_no'=>($request['chasis_no'])?$request['chasis_no']:'',
             ],['id'=>$result['id']]);
        }
        
    }
}
