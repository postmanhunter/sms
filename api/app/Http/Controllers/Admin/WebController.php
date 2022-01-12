<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Apis;
use App\Helper\RedisHelper;

class WebController extends Apis{
    use RedisHelper;
    public function __construct()
    {
        $this->redis = $this->getRedisInstance();
    }
    public function getInfo(){
        $status = $this->redis->get('sms_push_status');
        $check_status = $this->redis->get('check_status');
        $data = [
            'upload_url' => env('APP_URL').'/api/upload',
            'sms_push_status' => empty($status)?'stop':$status,
            'check_status' => empty($check_status)?'stop':$check_status,
            'tx_callback_url' => env('APP_URL').'/api/callback',
            'num' => $this->redis->llen('sms_message'),
            ''
        ];
        return $this->response($data);
    }
}