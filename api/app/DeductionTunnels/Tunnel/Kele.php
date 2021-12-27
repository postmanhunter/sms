<?php

namespace App\DeductionTunnels\Tunnel\DeductionTunnels;

use App\DeductionTunnels\BaseDeductionTunnels;
use GuzzleHttp\Client;
use Ramsey\Uuid\Uuid;

/**
 * 支付商对接 Kele
 */

class Kele extends BaseDeductionTunnels
{
    // 三方平台唯一标识, 一般是对应的 host, 如果有变化, 需要同步修改该值与文件名
    protected $tunnel_flag = 'Kele';

    /**
     * 部分三方平台需要先通知创建订单, 如果需要, 请在这里补充, 并按格式返回
     * [{创建订单成功与否, 会存储在 co_status 中}, {创建订单时, 三方平台返回的内容, 会存储在 co_content 中}]
     */
    public function createOrder($hs_order_id, $paytype, $amount)
    {
        try{
            $paramArray = array(
                "fxid" => $this->tunnel_params['merch'], //商户ID
                "fxfee" => $this->editAmount($amount),  //商户应用ID
                "fxddh" => $hs_order_id,  //支付产品ID
                "fxdesc" => '支付',  //支付产品ID
                "fxpay"=> empty($this->tunnel_params['third_code']) ? $this->Typychange($paytype) : $this->tunnel_params['third_code'],
                "fxip"=>$this->tunnel_params['_client_ip_'],
                "fxbackurl"=>$this->getCallbackUrl2PF(),
                "fxnotifyurl"=>$this->getCallbackUrl2PF()
            );
            $sign_json = json_encode($paramArray);
            logger("[Kele]--sign_params--------------[{$sign_json}]--[{$this->api_url}]",'payment');
          
            $paramArray["fxsign"] = md5($paramArray['fxid'].$paramArray['fxddh'].$paramArray['fxfee'].$paramArray['fxnotifyurl'].$this->tunnel_params['key']); //签名
            
            $client = new Client();
            $response = $client->post($this->api_url.'/Pay',[
                'form_params'=>$paramArray
            ]);
            if($response->getStatusCode() != 200) {
                logger("[Kele]--out: {$response->getBody()->getContents()}",'payment');
                return [false,[]];
            }
            $response = $response->getBody()->getContents();
            logger("[Kele]--return_params------------------[{$response}]",'payment');
            $result  = json_decode($response,true);
            return [true,$result];
            if($result['status']==1){
                return [true,$result];
            }else{
                return [false,$result];
            }
        }catch(\Exception $e){
            $mess = $e->getMessage();
            logger("[Kele]--query_error------------------[{$mess}]",'payment');
        }
        
    }
    /**
     * 支付类型转换
     * 1.支付宝扫码
     * 2.支付宝WAP
     * 3.微信扫码
     * 4.微信WAP
     * 5.京东扫码
     * 6.银联扫码
     * 
     */
    public function Typychange($type){
        $array = [
            'ALIPAYWAP'=>'alipay_trans'            
        ];
        return $array[$type];
    }
    public function editAmount($amount){
        $arr = explode('.',$amount);
        if(!isset($arr[1])){
            return (string)($amount).'.00';
        }else if(strlen($arr[1])==1){
            return (string)($amount).'0';
        }else{
            return round($amount*100)/100;
        }
    }
    /**
     * 输入恒山订单 ID 查询三方平台的订单状态, 并模拟三方平台回调通知 callback
     * 部分平台查询到的结果和回调的数据不一致, 需兼容
     * 在定时任务中调用
     */
    public function queryCallbackOrder($params)
    {
        $client = new Client();
        $url = $this->api_url.'/Pay?'.http_build_query($params);
        $response = $client->get($url);
        if($response->getStatusCode() != 200) {
            logger("[Kele]--out: {$response->getBody()->getContents()}",'payment');
            return;
        }
        $response = $response->getBody()->getContents();
        logger("[Kele]--return_params------------------[{$response}]",'payment');
        $result  = json_decode($response,true);
        if($result['fxstatus']==1){
            return true;
        }else{
            return false;
        }
        
    }

    /**
     * 输入恒山订单, 返回平台实际支付应跳转的地址
     */
    public function returnPayurl($order)
    {

        $content = json_decode($order['co_content'],true);
        logger("[Kele]--con_content-----------[{$order['co_content']}]",'payment');
        return $content['payurl'];
    }
    /**
     * 三方平台回调时, 获取传给三方平台的恒山订单号和请求数据
     * 如果三方平台通过 get 通知, 则这里要返回 getData
     * 如果三方平台通过 post 通知, 则之类要返回 postData
     */
    public function loadInputData($input)
    {
        $request = $input->postData();
        $data = json_encode($request);
        // $request1 = file_get_contents("php://input");
        // logger("[ Haiwang ]callback------------[{$request1}]",'payment');
        // $request = json_decode($request1,true);
        // $request = json_decode('{"fxid":"2020284","fxddh":"D202012018e78c6c8f3549","fxdesc":"\u652f\u4ed8","fxorder":"qzf2020120114220053687","fxfee":"30.00","fxattch":"","fxtime":"1606803720","fxstatus":"1","fxsign":"ce1c3494728c698f64616186df7b5a6b"}',true);
        // $redis = $this->getRedis();
        // $order = $redis->get($request['order_sn']);
        logger("[Kele]--callback------------[{$data}]",'payment');
        return [$request['fxddh'], $request];
    }

    /**
     * 处理回调, 外部已处理重复调用逻辑
     * 并通知业务系统进行发货操作
     * 如果三方平台的回调依赖页面输出值, 请在这里填写
     */
    protected function handelCallback($hs_order_id, $input)
    {
        $checkRes = $this->checkCallBackMoney($hs_order_id, $input['fxfee']);

        //验证金额
        if($checkRes){
            try{
                //查单验证
                $params= [
                    "fxid" => $this->tunnel_params['merch'],
                    'fxddh'=>$input['fxddh'],
                    'fxaction'=>'orderquery'
                ];
                $json = json_encode($params);
                logger("[Kele]--call_back_query_trade_no_params [{$json}]: ", 'payment');
                $params['fxsign'] = md5($params['fxid'].$params['fxddh'].$params['fxaction'].$this->tunnel_params['key']);  //签名
               
                if($this->queryCallbackOrder($params)){
                    //查单成功，验签
                    $paramArray = array(
                        "fxid" => $this->tunnel_params['merch'], //商户ID
                        "fxstatus" => $input['fxstatus'],  //商户应用ID
                        "fxddh" => $input['fxddh'],  //支付产品ID
                        "fxfee"=> $input['fxfee']
                    );
                    $sign_json = json_encode($paramArray);
                    logger("[Kele]--callback_sign_params--------------[{$sign_json}]--[{$this->api_url}]",'payment');
                
                    $sign = md5($paramArray['fxstatus'].$paramArray['fxid'].$paramArray['fxddh'].$paramArray['fxfee'].$this->tunnel_params['key']);  //签名
                    logger("[{$sign}][{$input['fxsign']}]",'payment');
                    if($sign===trim($input['fxsign'])){
                        //验签成功
                        logger("[Kele]--query [success] [{$hs_order_id}]: " . json_encode($input), 'payment');
                        // $orderstatus 订单状态, true 成功, false 失败
                        $orderstatus = true;
                        // 通知发货, $notice_code 通知发货的结果
                        $notice_code = $this->noticeCallback($hs_order_id, $orderstatus);

                        // 更新订单状态
                        $this->updateOrder($hs_order_id,$input['fxorder'], $orderstatus, $notice_code, $input);
                        echo 'success';
                    }else{
                        logger("[Kele]------回调验签失败",'payment');
                    }
                }else{
                    logger("[Kele]------回调验签失败",'payment');
                }
            }catch(\Exception $e){
                $message = $e->getMessage();
                logger("[Kele]-------------error---------[$message]",'payment');
            }
            
        }else{
            logger("[Kele]-----check money error",'payment');
            return;
        }
    }

    /**
     * 生成 hs_order_id 的后半部分
     */
    public function genHsOrderId() 
    {
        $uuid4 = Uuid::uuid4();
        $uuid_string = explode('-', $uuid4->toString());

        $hs_order_id = "{$uuid_string[0]}{$uuid_string[1]}{$uuid_string[2]}{$uuid_string[3]}";
        $hs_order_id = substr($hs_order_id,0,-8);
        $hs_order_id .= rand(1, 10);
        return $hs_order_id;
    }
    protected function paramArraySign($paramArray, $mchKey){
		
		ksort($paramArray);  //字典排序
		reset($paramArray);
	
		$md5str = "";
		foreach ($paramArray as $key => $val) {
			if( strlen($key)  && strlen($val) ){
				$md5str = $md5str . $key . "=" . $val . "&";
			}
        }
        $str1 = $md5str . "paySecret=" . $mchKey;
        logger("[Kele]--------------[{$str1}]",'payment');
		$sign = strtoupper(md5($str1));  //签名
		logger("[sign]-----------------------------[{$sign}]",'payment');
		return $sign;
		
	}
}
