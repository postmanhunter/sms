<?php
namespace App\Http\Sms;
use Qiniu\Sms\Sms;
use Qiniu\Auth;
class Qiniu
{
    public function send($message,$params){
        try{
            $paramters = [];
            $index = 1;
            foreach($params['temp_params'] as $v){
                $paramters[$v] = $message[$index++];
            }
            $auth = new Auth($params['AccessKey'],$params['SecretKey']);
            $sms = new Sms($auth);
          
            $data = $sms->sendMessage($params['temp_id'],[trim($message[0])],$paramters);  
            var_dump($paramters,$data);
            return $data;
        }catch(\Exception $e){
            echo $e->getMessage();
            return ['',$e->getMessage()];
        }
        
    }
    public function getSmsStatus($params,$request_id){
        $auth = new Auth($params['AccessKey'],$params['SecretKey']);
        $sms = new Sms($auth);
        $data = $sms->querySendSms($request_id);
        var_dump($data);
        if(isset($data[0]['items'][0]['status']) && $data[0]['items'][0]['status']=='success'){
            return true;
        }
        return false;
    }
}