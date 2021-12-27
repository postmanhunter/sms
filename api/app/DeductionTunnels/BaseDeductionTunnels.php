<?php

namespace App\DeductionTunnels;

use Ramsey\Uuid\Uuid;
use App\Helper\LoggerHelper;
use App\Models\Admin\UpMessageModel;
use App\Models\Admin\MerModel;
use App\Models\Admin\MoneyChangeModel;
use App\Models\Admin\RechargeModel;
use Illuminate\Support\Facades\DB;
use App\Helper\RabbitmqHelper;
use Exception;

class BaseDeductionTunnels
{
    use LoggerHelper;
    protected static $instances;
    /**
     * 生成 hs_order_id 的后半部分
     */
    public function genHsOrderId() 
    {
        $uuid4 = Uuid::uuid4();
        $uuid_string = explode('-', $uuid4->toString());
        $hs_order_id = "{$uuid_string[0]}{$uuid_string[1]}";
        $hs_order_id .= rand(1, 10);
        return $hs_order_id;
    }
    final public static function getInstance()
    {
        $called_class = get_called_class();

        if (!isset(static::$instances[$called_class])) {
            static::$instances[$called_class] = new $called_class();
        }

        return static::$instances[$called_class];
    }
    /**
     * 获取回调地址
     */
    public function getNotifyUrl()
    {
        // return "http://livetestd.h2h7.com/payment/deduction/callback/Fcpay";
        return env('APP_URL').'/api/recharge_notify/'.$this->flag;
    }
    /**
     * 获取跳转地址
     */
    public function getReturnUrl()
    {
        return env('APP_URL').'/api/recharge_return';
    }
    /**
     * 设置实例的参数
     */
    public function setParams($tunnel_params)
    {
        $this->tunnel_params = $tunnel_params;
    }
    /**
     * 更新该笔订单的查单以及回调信息
     */
    public function updateMessage($info)
    {
        UpMessageModel::record($info);
    }
    private function getDividerMoney($pay_money, $fee)
    {
        $fee>1 && $fee = $fee/100;  
        $mer_money = $pay_money*(1-$fee);
        $mer_money = ((int)($mer_money*100))/100;

        return [
            $mer_money,
            $pay_money-$mer_money
        ];
    }  
    /**
     * 获取上游收费以及实际收入
     */
    private function getTrueFee($money, $up_rate, $platform_money)
    {
        $up_rate>1 && $up_rate = $up_rate/100;  
        $up_money = $money*$up_rate;
        $up_money = ((int)($up_money*100))/100;

        return [
            $platform_money-$up_money,
            $up_money
        ];
    }
  
    /**
     * 处理回调之后的逻辑
     */
    public function handleAfterUpCallback($recharge_id, $pay_money, $input)
    {
        //1.更新用户余额，更新订单状态（包括成功订单），生成帐变，更新三方通道成功率
        try{
            $recharge_info = RechargeModel::where('id', $recharge_id)->first();

            //订单完成回调，防止重复回调
            if ($recharge_info->status==RechargeModel::QUERY_FINISHED) {
                return;
            }
            $mer_info = MerModel::where('id', $recharge_info->m_id)->lockForUpdate()->first();
            list($mer_money, $platform_money) = $this->getDividerMoney($pay_money, $recharge_info->platform_rate);
            $time = date('Y-m-d H:i:s');
            list($true_fee, $up_fee) = $this->getTrueFee($pay_money, $recharge_info->up_rate, $platform_money);

            //帐变相关
            $money_change = [
                'mer_id' => $mer_info->id,
                'order_money' => $pay_money,
                'before_money' => $mer_info->money,
                'after_money' => $mer_info->money+$mer_money,
                'true_money' => $mer_money,
                'fee_money' => $platform_money,
                'order_id' => $recharge_info->platform_order_id,
                'type' => MoneyChangeModel::RECHARGE_ORDER,
                'created_at' => $time,
                'sanfang_id' => $recharge_info->sanfan_id,
                'sanfang_tunnel_id' => $recharge_info->sanfan_tunnelid,
                'platform_tunnel_id' => $recharge_info->platform_tunnelid,
                'platform_rate' => $recharge_info->platform_rate,
                'up_rate' => $recharge_info->up_rate,
                'up_fee' => $up_fee,
                'true_fee' => $true_fee
            ];

            //订单相关
            $recharge_info->status = RechargeModel::QUERY_FINISHED;
            $recharge_info->finished_at = $time;
            $recharge_info->pay_money = $pay_money;

            //用户余额相关
            $mer_info->money = $mer_info->money+$mer_money;

            DB::beginTransaction();
            $recharge_info->save();
            $mer_info->save();
            MoneyChangeModel::insert($money_change);
            DB::commit();

            //开始给商户回调处理
            RabbitmqHelper::getInstance()->pushDelayMsg($recharge_info->id,'recharge_callback',2);
        } catch(Exception $e) {
            $this->logger("[Recharge]---when---money-change-[{$e->getMessage()}]---[{$e->getLine()}]",'payment');
            DB::rollback();
        }
    }
}