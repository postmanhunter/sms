<?php
namespace App\Http\Sms;

class EmptyCheck
{
    public static function check($mobile,$params)
    {
        // // 云市场分配的密钥Id
        // $secretId = 'AKIDMmb81L5jslokBuJBSV2E4es1j18sDS21Xxx';
        // // 云市场分配的密钥Key
        // $secretKey = 'td9l5RZx0C2sSB8vSHjiEaqrkRV9yb2QtSnkpW1';
        // 云市场分配的密钥Id
        $secretId = $params['secretId'];
        // 云市场分配的密钥Key
        $secretKey = $params['secretKey'];
        $source = 'market';

        // 签名
        $datetime = gmdate('D, d M Y H:i:s T');
        $signStr = sprintf("x-date: %s\nx-source: %s", $datetime, $source);
        $sign = base64_encode(hash_hmac('sha1', $signStr, $secretKey, true));
        $auth = sprintf('hmac id="%s", algorithm="hmac-sha1", headers="x-date x-source", signature="%s"', $secretId, $sign);

        // 请求方法
        $method = 'POST';
        // 请求头
        $headers = array(
            'X-Source' => $source,
            'X-Date' => $datetime,
            'Authorization' => $auth,

        );
        // 查询参数
        $queryParams = array(

        );
        // body参数（POST方法下）
        $bodyParams = array(
            'mobile_number' => $mobile,
        );
        // url参数拼接
        $url = 'http://service-rnvuo8zq-1305308687.gz.apigw.tencentcs.com/release/mobile/empty-check';
        if (count($queryParams) > 0) {
            $url .= '?' . http_build_query($queryParams);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_map(function ($v, $k) {
            return $k . ': ' . $v;
        }, array_values($headers), array_keys($headers)));
        if (in_array($method, array('POST', 'PUT', 'PATCH'), true)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($bodyParams));
        }

        $data = curl_exec($ch); 
        
        if (curl_errno($ch)) {
            throw new \Exception('检测空号异常');
            curl_close($ch);
        } else {
            curl_close($ch);
            return json_decode($data,true);
        }
    }
}