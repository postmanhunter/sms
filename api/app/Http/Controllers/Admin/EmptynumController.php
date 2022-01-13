<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Apis;
use App\Http\Requests\Admin\EmptynumRequest;
use App\Http\Sms\Remainnum;
use App\Http\Sms\EmptyCheck;

class EmptynumController extends Apis
{
    public function stopCheckNum()
    {
        if ($this->redis->set('check_status', 'stop')) {
            return $this->response([]);
        }
        return $this->response(400000, '关闭失败');
    }
    public function startCheckNum()
    {
        if (empty($this->redis->get('secretId')) || empty($this->redis->get('secretKey'))) {
            return $this->response(400000, '请先填写服务商参数在开启');
        }
        if ($this->redis->set('check_status', 'start')) {
            return $this->response([]);
        }
        return $this->response(400000, '开启失败');
    }
    public function getNumService()
    {
        return $this->response([
            'secretId' => $this->redis->get('secretId'),
            'secretKey' => $this->redis->get('secretKey')
       ]);
    }
    public function submit(EmptynumRequest $request)
    {
        if ($this->redis->set('secretId', $request->secretId)
            && $this->redis->set('secretKey', $request->secretKey)) {
            return $this->response([]);
        }
        return $this->response(400000, '提交失败');
    }
    public function remainNum()
    {
        $params = [
            'SecretId' => 'AKIDnu113086qmwmgpu4qfqdy4ysim83ghsa2ur',
            'SecretKey' => '6mi8m0T3Y47eijo39cCf3lC5nzsbtG66l4U1zI4B',
            'InstanceId' => 'market-2sg18mz3o'
        ];
        $data = Remainnum::query($params);
        dd($data);
    }
    public function getRemainnum(){
        if($this->redis->exists('remain_empty_num')){
            $num = $this->redis->get('remain_empty_num');
            return $this->response([
                'remain' =>"剩余次数/总次数:{$num}"
            ]);
        }
        return $this->response(['remain'=>'还未查询次数']);
    }
    public function handle(){
        $check_params = [
            'secretId' => $this->redis->get('secretId'),
            'secretKey' => $this->redis->get('secretKey')
        ];
        $result = EmptyCheck::check('15432415231',$check_params);
        if(!isset($result['num'])){
            throw new \Exception('手动刷新异常');
        } 
        $this->redis->set('remain_empty_num',$result['num']);
        return $this->response(['remain'=>'剩余次数/总次数:'.$result['num']]);
    }
}
