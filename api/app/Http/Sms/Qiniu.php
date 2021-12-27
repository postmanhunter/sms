<?php
namespace App\Http\Sms;
use Qiniu\Sms\Sms;
use Qiniu\Auth;
class Qiniu
{
    public function send($message,$params){
        $paramters = [];
        $index = 1;
        foreach($params['temp_params'] as $v){
            $paramters[$v] = $message[$index++];
        }
        $auth = new Auth($params['AccessKey'],$params['SecretKey']);
        $sms = new Sms($auth);
        return $sms->sendMessage($params['temp_id'],[trim($message[0])],$paramters);
    }
}