<?php

namespace App\DeductionTunnels;

use Exception;

class GetDeductionInstance
{
    public function load($payway, $tunnel_params = [])
    {
        $payway = str_replace('.', ' ', $payway);
        $payway = ucwords(strtolower($payway));
        $payway = str_replace(' ', '', $payway);
        if (class_exists("App\DeductionTunnels\Tunnel\\{$payway}")) {
            $instance = call_user_func_array(["App\DeductionTunnels\Tunnel\\{$payway}", 'getInstance'], []);
            $instance->tunnel_params = $tunnel_params;
        } else {
            throw new Exception("不存在的支付通道", 9927657);
        }
        return $instance;
    }
}
