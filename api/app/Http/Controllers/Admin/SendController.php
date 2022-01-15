<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Apis;
use App\Http\Requests\Admin\SendRequest;
use App\Models\Admin\ServiceModel;
use App\Models\Admin\TempModel;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use App\Helper\RabbitmqHelper;
use App\Models\Admin\RecordModel;
use App\Models\Admin\SendModel;
use App\Helper\LoggerHelper;
use App\Helper\RedisHelper;
use App\Helper\ClientIpHelper;
class SendController extends Apis
{
    use LoggerHelper,RedisHelper;
    public function __construct()
    {
        $this->redis = $this->getRedisInstance();
    }
    public function send(SendRequest $request){
        $file = $request->file;
        // $file = 'storage/uploads/0c58e88eef112084944f3d6f62a20bb9.xlsx';
        $public = public_path();
        $path = $public.'/'.$file;
        $reader = new Xlsx();
        $spreadsheet = $reader->load($path);
    
        //需要发送的人的信息
        $sheetData = $spreadsheet->getActiveSheet()->ToArray();
        $service_params = ServiceModel::getService($request->service_id);
        $param = json_decode($service_params->params,true);
       
        //获取模板参数
        $temp_params = TempModel::getTemp($request->temp_id);
        $temp_param = json_decode($temp_params->param,true);
    
        $params = [];
        foreach($param as $val){
            $val1 = json_decode($val,true);
            $params[$val1['title']] = $val1['value'];
        }
        if ($temp_param) {
            foreach($temp_param as $v){
                $v1 = json_decode($v,true);
                $params['temp_params'][] = $v1['title'];
            }
        }
       
        $params['temp_id'] = $request->temp_id;
        $params['service_id'] = $service_params->service_id;
        $count = count($sheetData);
        $insert = [
            'service' => $request->service_id,
            'temp_id' => $request->temp_id,
            'time_gap' => $request->time,
            'nums' => $request->nums,
            'total' => $count,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $send_id = SendModel::add($insert);
        $params['send_id'] = $send_id;
        foreach($sheetData as $v_data){
            $data = [
                'message' =>$v_data,
                'params' =>$params,
            ];
            $this->redis->lpush('sms_message_'.$send_id,json_encode($data));
        }
        
        $this->redis->set('sms_cur_send_'.$send_id,time());
        $this->redis->set('sms_delay_'.$send_id,$request->time);
        $this->redis->set('every_nums_'.$send_id,$request->nums);
        //添加发送模板成员
        !$this->redis->sIsMember('sms_send_member',$send_id) && $this->redis->sAdd('sms_send_member',$send_id);

        return $this->response([]);
        
    }
    public function delete(){
        $rabbitInstance = RabbitmqHelper::getInstance(); 
        $count = $rabbitInstance->delQueues('sms_push');
        return $this->response(['count'=>$count]);
    }
    public function getMessageNUm(){
        $num = $this->redis->llen('sms_message');
        return $this->response(['num'=>$num]);
    }

    public function getMessageStatus(SendRequest $request){
        // $data = [
        //     'time' => date('Y-m-d H:i:s')
        // ];
        // $string = json_encode($data);
        // // $this->redis->lpush('sms_message',$string);
        // $this->redis->ltrim('sms_message',0,0);
       
        // $this->redis->lpop('sms_message');
        dd($this->redis->llen('sms_message'));
    }
    public function callback(SendRequest $request){
        
        $data = $request->input();                                                        
        $update = [];
        if($data[0]['report_status'] === 'SUCCESS'){
            $update = [
                'status' => 3
            ];
        } else {
        
            $update = [
                'status' => 2,
            ];
        }
        RecordModel::updateRe($data[0]['sid'],$update);
    }
    public function stopSmsPush(SendRequest $request){
        if($this->redis->set('sms_push_status_'.$request->id,'stop')){
            return $this->response([]);
        }
        return $this->response(400000,'关闭失败');
    }
    public function startSmsPush(SendRequest $request){
        if($this->redis->set('sms_push_status_'.$request->id,'start')){
            return $this->response([]);
        }
        return $this->response(400000,'开启失败');
    }
    public function cleanSms(){
        $this->redis->ltrim('sms_message',0,0);
        $this->redis->lpop('sms_message');
        return $this->response([]);
    }
    public function getList(SendRequest $request){
        $service = ServiceModel::getList()->toArray();
        $data = SendModel::getList($request);
        $data1 = [];
        foreach($service as $val){
            $data1[$val['id']] = $val['service_name'];
        }
        $status = [
            1 => '就绪中',
            2 => '发送中',
            3 => '暂停',
            4 => '已完成',
        ];
        $members = $this->redis->sMembers('sms_send_member');
        // var_dump($data);die;
        foreach($data['data'] as &$val){
            $send_status = $this->redis->get('sms_push_status_'.$val['id']);
            $val['service_name'] = $data1[$val['service']];
            $val['status_name'] = $status[$val['status']];
            $val['condition'] = $val['finish'].'/'.$val['total'];
            $val['send_status'] = $send_status === 'start'?'start':'stop';
            if (!empty($members)) {
                if(in_array($val['id'],$members)) {
                    $val['click_status'] ='open';
                    $val['status_n'] = '1';
                }else {
                    $val['status_n'] = '2';
                }
            } else {
                $val['click_status'] = 'close';
                $val['status_n'] = '2';
            }
        }
        return $this->response($data);
    }
    public function test(){
        $ip = ClientIpHelper::getRemoteIP();
        $this->logger("ip:{$ip}",'ip');
    }
}
