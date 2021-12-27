<?php

namespace App\DeductionTunnels\Tunnel;

use App\DeductionTunnels\BaseDeductionTunnels;
use GuzzleHttp\Client;
use Ramsey\Uuid\Uuid;
use App\Models\Admin\RechargePayUrlModel;

/**
 * 支付商对接 Daye
 */

class Daye extends BaseDeductionTunnels
{
    // 三方平台唯一标识, 一般是对应的 host, 如果有变化, 需要同步修改该值与文件名
    protected $tunnel_flag = 'Daye';

    /**
     * 部分三方平台需要先通知创建订单, 如果需要, 请在这里补充, 并按格式返回
     * [{创建订单成功与否, 会存储在 co_status 中}, {创建订单时, 三方平台返回的内容, 会存储在 co_content 中}]
     */
    public function createOrder($platform_order_id, $pass_code, $amount)
    {
        // dd($platform_order_id,$this->tunnel_params);
        try{
            $paramArray = array(
                "uid" => $this->tunnel_params['mer_id'], //商户ID
                "amount" => $amount,  //金额
                "out_trade_no" => $platform_order_id,  //支付产品ID
                "type"=> $pass_code,
                "notify_url"=>$this->getNotifyUrl(),
                "product_name" => 'vip',
                "return_url" => $this->getReturnUrl(),
            );
            $sign_json = json_encode($paramArray);
            $this->logger("[Daye]--sign_params-----[{$platform_order_id}]---------[{$sign_json}]--[{$this->tunnel_params['api_url']}]",'payment');
            $paramArray["sign"] = $this->paramArraySign($paramArray,$this->tunnel_params['key']);
            $client = new Client();
            $response = $client->post($this->tunnel_params['api_url'],[
                'form_params'=>$paramArray
            ]);
            if($response->getStatusCode() != 200) {
                $this->logger("[Daye]--------[{$platform_order_id}]----out: {$response->getBody()->getContents()}",'payment');
                return [false,[]];
            }
            $response = $response->getBody()->getContents();
            $this->logger("[Daye]--return_params-------[{$platform_order_id}]-----------[{$response}]",'payment');
            $result  = json_decode($response,true);
            return [true,['pay_url'=>'http://www.baidu.com']];
            if($result['status']==1){
                return [true,$result];
            }else{
                return [false,$result];
            }
        }catch(\Exception $e){
            $mess = $e->getMessage();
            $this->logger("[Daye]--query_error------[{$platform_order_id}]------------[{$mess}]",'payment');
            return [false,[$mess]];
        }
        
    }
    public function genHsOrderId() 
    {
        $uuid4 = Uuid::uuid4();
        $uuid_string = explode('-', $uuid4->toString());
        $hs_order_id = "{$uuid_string[0]}{$uuid_string[1]}{$uuid_string[2]}{$uuid_string[3]}";
        $hs_order_id .= rand(1, 100);
        return $hs_order_id;
    }
    public function getPayUrl($info, $platform_order_id)
    {
        $pay_url = $info['pay_url'];
        RechargePayUrlModel::createOne([
            'message' => '1',
            'type' => 2,
            'create_time' => time(),
            'platform_order_id' => $platform_order_id
        ]);
    }
    // /**
    //  * 输入恒山订单 ID 查询三方平台的订单状态, 并模拟三方平台回调通知 callback
    //  * 部分平台查询到的结果和回调的数据不一致, 需兼容
    //  * 在定时任务中调用
    //  */
    // public function queryCallbackOrder($params)
    // {
    //     $client = new Client();
    //     $url = $this->api_url.'/Pay?'.http_build_query($params);
    //     $response = $client->get($url);
    //     if($response->getStatusCode() != 200) {
    //         $this->logger("[Daye]--out: {$response->getBody()->getContents()}",'payment');
    //         return;
    //     }
    //     $response = $response->getBody()->getContents();
    //     $this->logger("[Daye]--return_params------------------[{$response}]",'payment');
    //     $result  = json_decode($response,true);
    //     if($result['fxstatus']==1){
    //         return true;
    //     }else{
    //         return false;
    //     }
        
    // }

    // /**
    //  * 输入恒山订单, 返回平台实际支付应跳转的地址
    //  */
    // public function returnPayurl($order)
    // {

    //     $content = json_decode($order['co_content'],true);
    //     $this->logger("[Daye]--con_content-----------[{$order['co_content']}]",'payment');
    //     return $content['payurl'];
    // }
    // /**
    //  * 三方平台回调时, 获取传给三方平台的恒山订单号和请求数据
    //  * 如果三方平台通过 get 通知, 则这里要返回 getData
    //  * 如果三方平台通过 post 通知, 则之类要返回 postData
    //  */
    // public function loadInputData($input)
    // {
    //     $request = $input->postData();
    //     $data = json_encode($request);
    //     // $request1 = file_get_contents("php://input");
    //     // $this->logger("[ Haiwang ]callback------------[{$request1}]",'payment');
    //     // $request = json_decode($request1,true);
    //     // $request = json_decode('{"fxid":"2020284","fxddh":"D202012018e78c6c8f3549","fxdesc":"\u652f\u4ed8","fxorder":"qzf2020120114220053687","fxfee":"30.00","fxattch":"","fxtime":"1606803720","fxstatus":"1","fxsign":"ce1c3494728c698f64616186df7b5a6b"}',true);
    //     // $redis = $this->getRedis();
    //     // $order = $redis->get($request['order_sn']);
    //     $this->logger("[Daye]--callback------------[{$data}]",'payment');
    //     return [$request['fxddh'], $request];
    // }

    // /**
    //  * 处理回调, 外部已处理重复调用逻辑
    //  * 并通知业务系统进行发货操作
    //  * 如果三方平台的回调依赖页面输出值, 请在这里填写
    //  */
    // protected function handelCallback($hs_order_id, $input)
    // {
    //     $checkRes = $this->checkCallBackMoney($hs_order_id, $input['fxfee']);

    //     //验证金额
    //     if($checkRes){
    //         try{
    //             //查单验证
    //             $params= [
    //                 "fxid" => $this->tunnel_params['merch'],
    //                 'fxddh'=>$input['fxddh'],
    //                 'fxaction'=>'orderquery'
    //             ];
    //             $json = json_encode($params);
    //             $this->logger("[Daye]--call_back_query_trade_no_params [{$json}]: ", 'payment');
    //             $params['fxsign'] = md5($params['fxid'].$params['fxddh'].$params['fxaction'].$this->tunnel_params['key']);  //签名
               
    //             if($this->queryCallbackOrder($params)){
    //                 //查单成功，验签
    //                 $paramArray = array(
    //                     "fxid" => $this->tunnel_params['merch'], //商户ID
    //                     "fxstatus" => $input['fxstatus'],  //商户应用ID
    //                     "fxddh" => $input['fxddh'],  //支付产品ID
    //                     "fxfee"=> $input['fxfee']
    //                 );
    //                 $sign_json = json_encode($paramArray);
    //                 $this->logger("[Daye]--callback_sign_params--------------[{$sign_json}]--[{$this->api_url}]",'payment');
                
    //                 $sign = md5($paramArray['fxstatus'].$paramArray['fxid'].$paramArray['fxddh'].$paramArray['fxfee'].$this->tunnel_params['key']);  //签名
    //                 $this->logger("[{$sign}][{$input['fxsign']}]",'payment');
    //                 if($sign===trim($input['fxsign'])){
    //                     //验签成功
    //                     $this->logger("[Daye]--query [success] [{$hs_order_id}]: " . json_encode($input), 'payment');
    //                     // $orderstatus 订单状态, true 成功, false 失败
    //                     $orderstatus = true;
    //                     // 通知发货, $notice_code 通知发货的结果
    //                     $notice_code = $this->noticeCallback($hs_order_id, $orderstatus);

    //                     // 更新订单状态
    //                     $this->updateOrder($hs_order_id,$input['fxorder'], $orderstatus, $notice_code, $input);
    //                     echo 'success';
    //                 }else{
    //                     $this->logger("[Daye]------回调验签失败",'payment');
    //                 }
    //             }else{
    //                 $this->logger("[Daye]------回调验签失败",'payment');
    //             }
    //         }catch(\Exception $e){
    //             $message = $e->getMessage();
    //             $this->logger("[Daye]-------------error---------[$message]",'payment');
    //         }
            
    //     }else{
    //         $this->logger("[Daye]-----check money error",'payment');
    //         return;
    //     }
    // }

    // /**
    //  * 生成 hs_order_id 的后半部分
    //  */
    // public function genHsOrderId() 
    // {
    //     $uuid4 = Uuid::uuid4();
    //     $uuid_string = explode('-', $uuid4->toString());

    //     $hs_order_id = "{$uuid_string[0]}{$uuid_string[1]}{$uuid_string[2]}{$uuid_string[3]}";
    //     $hs_order_id = substr($hs_order_id,0,-8);
    //     $hs_order_id .= rand(1, 10);
    //     return $hs_order_id;
    // }
    protected function paramArraySign($paramArray, $mchKey){
		
		ksort($paramArray);  //字典排序
		reset($paramArray);
	
		$md5str = "";
		foreach ($paramArray as $key => $val) {
			if( strlen($key)  && strlen($val) ){
				$md5str = $md5str . $key . "=" . $val . "&";
			}
        }
        $str1 = $md5str . "key=" . $mchKey;
		$sign = strtoupper(md5($str1));  //签名
		return $sign;
		
	}
}
