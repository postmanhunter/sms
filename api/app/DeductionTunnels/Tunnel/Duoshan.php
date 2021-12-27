<?php

namespace App\DeductionTunnels\Tunnel;

use App\DeductionTunnels\BaseDeductionTunnels;
use GuzzleHttp\Client;
use Ramsey\Uuid\Uuid;
use App\Models\Admin\RechargePayUrlModel;
use Exception;

/**
 * 恒信支付
 */
class Duoshan extends BaseDeductionTunnels
{
    protected $flag = 'Duoshan';

    public function createOrder($platform_order_id, $pass_code, $amount)
    {
        try {
            $this->logger('[' . $this->flag . '][createOrder] params:' . json_encode($this->tunnel_params), 'payment');
            $params = [
                'pay_memberid' => $this->tunnel_params['mer_id'],
                'pay_orderid' => $platform_order_id,
                'pay_applydate' => date('Y-m-d H:i:s'),
                'pay_notifyurl' => $this->getNotifyUrl(),
                'pay_callbackurl' => $this->getReturnUrl(),
                'pay_amount' => $amount,
                'pay_bankcode' => $pass_code,
            ];
            //签名信息  
            $params['pay_md5sign'] = $this->makeSign($params);
            $params['pay_productname'] = 'lucky';
            $params['pay_post'] = 'json';
            $params['pay_ip'] = $this->tunnel_params['_client_ip_'];

            
                $res = $this->getHttpContent($this->tunnel_params['api_url'], 'POST', $params);
                $this->logger("[$this->flag]return_params-----获取支付链接反馈-------------[{$res}]", 'payment');
                $result = json_decode($res, true);
        } catch (Exception $e) {
            $result = [];
            $result['code'] = 'fail';
            $result['ex_code'] = $e->getCode();
            $result['ex_msg'] = $e->getMessage();
        }
        //验证返回信息
        if (isset($result["status"]) && ($result["status"] == 'success')) {
            return [true, $result];
        } else {
            return [false, $result];
        }

    }

    /**
     * 生成支付链接
     */
    public function getPayUrl($info, $platform_order_id)
    {
        $pay_url = $info['data'];
        RechargePayUrlModel::createOne([
            'message' => $pay_url,
            'type' => 1,
            'create_time' => time(),
            'platform_order_id' => $platform_order_id
        ]);
    }

    public function queryCallbackOrder($data, $platform_order_id)
    {
        try {
            $res = $this->getHttpContent($this->tunnel_params['query'], 'POST', $data);
            $this->logger("queryCallbackOrder[{$this->flag}][query']]---->>" . $res, 'payment');
            $res = json_decode($res, true);
        } catch (Exception $e) {
            $res = [];
            $res['code'] = 'fail';
            $res['ex_code'] = $e->getCode();
            $res['ex_msg'] = $e->getMessage();
        }
        
        //更新查单信息
        $this->updateMessage([
            'order_id' => $platform_order_id,
            'up_query_content' => json_encode($res)
        ]);
        if (is_array($res) && isset($res['returncode']) && $res['returncode'] == "00") {
            return true;
        }
        return false;
    }


    public function loadInputData($input)
    {
        $this->logger("[$this->flag][loadInputData] 多闪回调开始 order: " . json_encode($input), 'payment');
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
        $orderid = $input['orderid']; //商户自定义订单号
        $price = $input['amount']; //交易金额
        $key = $input['sign']; //md5验证签名串
        $fuckingQuery = [
            'pay_memberid' => $input['memberid'],
            'pay_orderid' => $input['orderid'],
        ];
       
        //异步回调签名验证【md5(订单状态+商务号+商户订单号+支付金额+商户秘钥)】
        $post_data=$input;
        if(isset($post_data['sign']))
            unset($post_data['sign']);
        if(isset($post_data['attach']))
            unset($post_data['attach']);
        $makesign = $this->makeSign($post_data);
        if ($key != $makesign) {
            $this->logger("query [fail] [{$this->flag}] [{$platform_order_id}] sign checked error", 'payment');
            echo 'fail[sign error]';

            return ;
        }

        $fuckingQuery['pay_md5sign']=$this->makeSign($fuckingQuery);
        if ($this->queryCallbackOrder($fuckingQuery, $platform_order_id)) {
            $this->logger("query [success] [{$this->flag}] [{$platform_order_id}]", 'payment');
            $this->handleAfterUpCallback($recharge_id, $input['amount'], $input);
        } else {
            $this->logger("query [fail] [{$this->flag}] [{$platform_order_id}]", 'payment');
            echo 'fail';
            return ;
        }
        echo 'OK';
    }

    /**
     * 密钥处理
     *
     * @param [type] $data
     * @return void
     */
    private function makeSign($data)
    {
        ksort($data);
        $str = '';
        foreach ($data as $k => $v) {
            if (empty($v)) continue;
            $str .= $k . '=' . $v . '&';
        }
        $str = rtrim($str, '&');
        $str = $str . '&key=' . $this->tunnel_params['key'];
        $this->logger("[{$this->flag}]sign_params--------------[$str]", 'payment');
        return strtoupper(md5($str));
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
        $platform_order_id = "{$uuid_string[0]}{$uuid_string[1]}{$uuid_string[2]}";
        return $platform_order_id;
    }

    private function getHttpContent($url, $method = 'GET', $postData = array())
    {
        $data = '';
        $user_agent = $_SERVER['HTTP_USER_AGENT']??'';
        $header = array(
            "User-Agent: $user_agent"
        );
        if (!empty($url)) {
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30); //30秒超时
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
                if (strstr($url, 'https://')) {
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                }


                if (strtoupper($method) == 'POST') {
                    $curlPost = is_array($postData) ? http_build_query($postData) : $postData;
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
                }
                $data = curl_exec($ch);
                curl_close($ch);
            } catch (Exception $e) {
                $data = '';
            }
        }
        return $data;
    }
}