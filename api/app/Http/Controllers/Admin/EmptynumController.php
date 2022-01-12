<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Apis;
use App\Http\Requests\Admin\EmptynumRequest;
class EmptynumController extends Apis
{
    public function stopCheckNum(){
        if($this->redis->set('check_status','stop')){
            return $this->response([]);
        }
        return $this->response(400000,'关闭失败');
    }
    public function startCheckNum(){
        if(empty($this->redis->get('secretId')) || empty($this->redis->get('secretKey'))) {
            return $this->response(400000,'请先填写服务商参数在开启');
        }
        if($this->redis->set('check_status','start')){
            return $this->response([]);
        }
        return $this->response(400000,'开启失败');
    }
    public function getNumService(){
       return $this->response([
            'secretId' => $this->redis->get('secretId'),
            'secretKey' => $this->redis->get('secretKey')
       ]);
    }
    public function submit(EmptynumRequest $request){
        if($this->redis->set('secretId',$request->secretId)
            && $this->redis->set('secretKey',$request->secretKey)){
            return $this->response([]);
        }
        return $this->response(400000,'提交失败');
    }

}