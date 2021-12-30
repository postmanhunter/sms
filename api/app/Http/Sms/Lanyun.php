<?php
namespace App\Http\Sms;
use  GuzzleHttp\Client;

class Lanyun
{
    const Schema = "SharedAccessSignature";
    const SignKey = "sig";
    const KeyNameKey = "skn";
    const ExpiryKey = "se";

    public function send($message,$params){
        $paramters = [];
        $index = 1;
        foreach($params['temp_params'] as $v){
            $paramters[$v] = $message[$index++];
        }

        $lanyun_params = [
            'phoneNumber' => $message[0],
            'extend' => '12',
            'messageBody' => '',
            'messageBody.candidateSig' =>'',
            'messageBody.templateName' => $params['temp_id'],
            'messageBody.templateParam' => json_encode($paramters)
        ];
        // dd($lanyun_params);
        $url = "https://bluecloudccs.21vbluecloud.com/services/sms/messages?apiversion=2018-10-01";
        // dd($params['token']);
        $data = $this->Post($url,$lanyun_params,$params['account'],$params['token']);
        // dd(json_decode($data,true));
        // $data = $this->Get('https://bluecloudccs.21vbluecloud.com/services/sms/templates?apiversion=2018-10-01&continuationToken=&count=10
        // ','',$params['account'],$params['token']);
        dd($data);
    }
    public function getToken($params){
        $expire = time() + 3600;
        $map = [
            self::ExpiryKey => $expire,
            self::KeyNameKey => $params['keyName']
        ];
        $string = http_build_query($map);
        $toSign = sha1(UrlEncode(base64_encode($string)));
        $token = self::Schema." ".$params['signingKey'].'='.'';
        
    }
    public function Post($url,$data,$acount,$auth) {
        $curl = curl_init();
 
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        if(!$data){
            return 'data is null';
        }
        if(is_array($data))
        {
            $data = json_encode($data);
        }
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER,array(
                'Content-Type: application/json; charset=utf-8',
                'Content-Length:' . strlen($data),
                'Cache-Control: no-cache',
                'Pragma: no-cache',
                'Account:'.$acount,
                'Authorization:'.$auth
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($curl);
        $errorno = curl_errno($curl);
        if ($errorno) {
            return $errorno;
        }
        curl_close($curl);
        return $res;
 
    }
    function Get($url,$data,$acount,$token,$timeout = 30){
        if($url == "" || $timeout <= 0){
            return false;
        };
        $con = curl_init((string)$url);
        curl_setopt($con, CURLOPT_HEADER, false);
        curl_setopt($con, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($con, CURLOPT_TIMEOUT, (int)$timeout);
        curl_setopt($con, CURLOPT_HTTPHEADER,array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length:' . strlen($data),
            'Cache-Control: no-cache',
            'Pragma: no-cache',
            'Account:'.$acount,
            'Authorization:'.$token
        ));
        return curl_exec($con);
    }
}