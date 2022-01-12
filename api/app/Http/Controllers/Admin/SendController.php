<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Apis;
use App\Http\Requests\Admin\SendRequest;
use App\Http\Sms\Qiniu;
use App\Http\Sms\Tx;
use App\Http\Sms\EmptyCheck;
use App\Models\Admin\ServiceModel;
use App\Models\Admin\TempModel;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use App\Helper\RabbitmqHelper;
use App\Models\Admin\RecordModel;
use App\Helper\LoggerHelper;
use App\Helper\RedisHelper;
class SendController extends Apis
{
    use LoggerHelper,RedisHelper;
    public function __construct()
    {
        $this->redis = $this->getRedisInstance();
    }
    public function send(SendRequest $request){
        $len = $this->redis->llen('sms_message');
        if($len){
            return $this->response(400000,"当前还有{$len}个短信未发送!");
        }
        $file = $request->file;
        // $file = 'storage/uploads/0c58e88eef112084944f3d6f62a20bb9.xlsx';
        $public = public_path();
        $path = $public.'/'.$file;
        $reader = new Xlsx();
        $spreadsheet = $reader->load($path);
    
        //需要发送的人的信息
        $sheetData = $spreadsheet->getActiveSheet()->ToArray();
        // dd($sheetData);
        $temp = 1;
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
        $check_params = [
            'secretId' => $this->redis->get('secretId'),
            'secretKey' => $this->redis->get('secretKey')
        ];
        foreach($sheetData as $v_data){
            $data = [
                'message' =>$v_data,
                'params' =>$params,
            ];

            //如果开启短信空号检测则检测
            if($this->redis->get('check_status')==='start'){
                $emptyMessage = EmptyCheck::check($v_data[0],$check_params);
                if(!isset($emptyMessage['data']['status'])){
                    throw new \Exception('检测空号接口异常');
                }
                $mobile_status = $emptyMessage['data']['status'];
                
                if($mobile_status != 1){
                    //检测到空号
                    (new RecordModel)->add([
                        'mobile' => $v_data[0],
                        'temp_id' => $params['temp_id'],
                        'status' => 2,
                        'created_at' => date('Y-m-d H:i:s'),
                        'reason' => "empty mobile number,status is {$mobile_status}",
                        'service_id' => $params['service_id']
                    ]);
                    continue;
                }
            }
            $this->redis->lpush('sms_message',json_encode($data));
        }
        $this->redis->set('sms_delay_count',$request->time);
        $this->redis->set('sms_delay',$request->time);
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
    public function stopSmsPush(){
        if($this->redis->set('sms_push_status','stop')){
            return $this->response([]);
        }
        return $this->response(400000,'关闭失败');
    }
    public function startSmsPush(){
        if($this->redis->set('sms_push_status','start')){
            return $this->response([]);
        }
        return $this->response(400000,'开启失败');
    }
    public function cleanSms(){
        $this->redis->ltrim('sms_message',0,0);
        $this->redis->lpop('sms_message');
        return $this->response([]);
    }
}
