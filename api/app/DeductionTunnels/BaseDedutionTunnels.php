<?php

namespace App\DeductionTunnels;
use Ramsey\Uuid\Uuid;

class BaseDeductionTunnels
{
    protected static $instances;
    /**
     * 生成 hs_order_id 的后半部分
     */
    public function genHsOrderId() 
    {
        $uuid4 = Uuid::uuid4();
        $uuid_string = explode('-', $uuid4->toString());
        $hs_order_id = "{$uuid_string[0]}{$uuid_string[1]}{$uuid_string[2]}{$uuid_string[3]}";
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
}