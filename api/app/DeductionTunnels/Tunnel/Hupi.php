<?php

namespace App\DeductionTunnels\Tunnel;

use App\DeductionTunnels\BaseDeductionTunnels;
use GuzzleHttp\Client;
use Ramsey\Uuid\Uuid;
use App\Models\Admin\RechargePayUrlModel;
use Exception;

/**
 * 蚂蚁支付
 */
class Hupi extends BaseDeductionTunnels
{
    protected $flag = 'Hupi';

    public function createOrder($platform_order_id, $pass_code, $amount)
    {
        try {
            $this->logger('[' . $this->flag . '][createOrder] params:' . json_encode($this->tunnel_params), 'payment');
            $params = [
                'version' => $this->tunnel_params['version'],
                'trade_order_id' => $platform_order_id,
                'appid' => $this->tunnel_params['mer_id'],
                'total_fee' => $amount,
                'title' => 'vip',
                'time' => time(),
                'notify_url' => $this->getNotifyUrl(),
                'nonce_str' => time(),
            ];
            //签名信息  
            $params['hash'] = $this->makeSign($params);
                       
            $client = new Client();
            $response = $client->post($this->tunnel_params['api_url'],[
                'form_params'=>$params
            ]);
            if($response->getStatusCode() != 200) {
                $this->logger("[Hupi]--out: {$response->getBody()->getContents()}",'payment');
                return [false,[]];
            }
            $response = $response->getBody()->getContents();
            $this->logger("[Hupi]--query--pay--return------------------[{$response}]",'payment');
            $result = json_decode($response, true);
            if  ($result['errcode']==0) {
                return [true,$result];
            } else {
                return [false,$result];
            }
            
        }catch(\Exception $e){
            $this->logger("[Hupi]----query-pay-error--[{$e->getMessage()}]",'payment');
        }

    }

    /**
     * 生成支付链接
     */
    public function getPayUrl($info, $platform_order_id)
    {
        $pay_url = $info['url'];
        RechargePayUrlModel::createOne([
            'message' => $pay_url,
            'type' => 1,
            'create_time' => time(),
            'platform_order_id' => $platform_order_id
        ]);
    }

    public function queryCallbackOrder($params)
    {
        $client = new Client();
        $response = $client->post($this->tunnel_params['query'],[
            'form_params'=>$params
        ]);
        if($response->getStatusCode() != 200) {
            $this->logger("[Hupi]--out: {$response->getBody()->getContents()}",'payment');
            return [false,[]];
        }
        $response = $response->getBody()->getContents();
        $this->logger("[Hupi]--query--order--return------------------[{$response}]",'payment');
        $result = json_decode($response, true);
        if($result['errcode']==0 && isset($result['data']['status']) && $result['data']['status']=='OD') {
            return true;
        }
        return false;
    }


    public function loadInputData($input)
    {
        $this->logger("[$this->flag]--get-pay--url: " . json_encode($input), 'payment');
        $platform_order_id = $input['orderid'];
        $this->updateMessage([
            'order_id' => $platform_order_id,
            'up_callback_message' => json_encode($input)
        ]);
        return $platform_order_id;
    }

    /**
     * 回调处理
     *
     * @param [type] $platform_order_id
     * @param [type] $input
     * @return void
     */
    public function handelCallback($platform_order_id, $recharge_id, $input)
    {

        $params = [
            'appid' => $this->tunnel_params['mer_id'],
            'out_trade_order' => $input['out_trade_order'],
            'time' => time(),
            'nonce_str' => time(),
        ];
        $params['hash'] = $this->makeSign($params);
        if ($this->queryCallbackOrder($params)) {
            $returnSign = $input['hash'];
            unset($input['hash']);
            $sign = $this->makeSign($input);
            if($returnSign==$sign){
                $this->logger("query [success] [{$this->flag}] [{$platform_order_id}]", 'payment');
                $this->handleAfterUpCallback($recharge_id, $input['amount'], $input);
            } else {
                $this->logger("[Hupi]----check--sign--error-[{$platform_order_id}]--[{$returnSign}]--[{$sign}]",'payment');
            }
        } else {
            $this->logger("[Hupi]----query-order-order-[{$platform_order_id}]",'payment');
        }
    }

    /**
     * 密钥处理
     *
     * @param [type] $data
     * @return void
     */
    function makeSign($datas){
        ksort($datas);
        reset($datas);
        $arg  = '';
        foreach ($datas as $key=>$val){
            if($key=='hash'||is_null($val)||$val===''){continue;}
                if($arg){$arg.='&';}
            $arg.="$key=$val";
        }

        return md5($arg.$this->tunnel_params['key']);
}

    /**
     * 订单号处理
     *
     * @return void
     */
    public function genHsOrderId()
    {
        $uuid4 = Uuid::uuid4();
        $uuid_string = explode('-', $uuid4->toString());
        $platform_order_id = "{$uuid_string[1]}{$uuid_string[2]}";
        return $platform_order_id;
    }
}