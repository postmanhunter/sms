<?php
namespace App\MqConsume\Sms;
use App\Helper\RabbitmqHelper;
use App\Http\Sms\Qiniu;
use App\Http\Sms\Tx;
use App\Models\Admin\RecordModel;
use App\Models\Admin\SendModel;
use App\Helper\RedisHelper;
use App\Http\Sms\EmptyCheck;
class SmsPush{
    use RedisHelper;
    # php /usr/share/nginx/html/www/test/laravel8/artisan ExecuteConsume --path=Sms --action=up --class=SmsPush
    # php /www/wwwroot/sms/api/artisan ExecuteConsume --path=Sms --action=up --class=SmsPush
    public function up(){
        $queue_name = 'sms_push';
        
        $class_qiniu = new Qiniu();
        $class_tx = new Tx();
        $class = [
            1 => $class_qiniu,
            3 => $class_tx
        ];
        $recordModel = new RecordModel();
        $sendModel = new SendModel();
        $redis = $this->getRedisInstance();
        RabbitmqHelper::getInstance()->listen($queue_name,function($data) use($class,$recordModel,$sendModel,$redis){
            $sendModel->addOne($data['params']['send_id']);
            // var_dump($data);
            $check_params = [
                'secretId' => $redis->get('secretId'),
                'secretKey' => $redis->get('secretKey')
            ];
            //如果开启短信空号检测则检测
            if($redis->get('check_status')==='start'){
                $result1 = EmptyCheck::check($data['message'][0],$check_params);
                if(!isset($result1['data']) || !isset($result1['num']) || !isset($result1['data']['data']['status'])){
                    $mobile_status = 1;
                }else {
                    $emptyMessage = $result1['data']; 
                    $redis->set('remain_empty_num',$result1['num']);
                    $mobile_status = $emptyMessage['data']['status'];
                }
                if($mobile_status != 1){
                    var_dump(1);
                    //检测到空号
                    $id = $recordModel->add([
                        'mobile' => $data['message'][0],
                        'temp_id' => $data['params']['temp_id'],
                        'status' => 2,
                        'created_at' => date('Y-m-d H:i:s'),
                        'reason' => "empty mobile number,status is {$mobile_status}",
                        'service_id' => $data['params']['service_id']
                    ]);
                    return;
                }
            }
            $service_id = $data['params']['service_id'];
            // echo $service_id;
            list($result,$error) = $class[$service_id]->send($data['message'],$data['params']);
            $insert = [
                'mobile'=>$data['message'][0],
                'created_at' => date('Y-m-d H:i:s'),
                'temp_id' => $data['params']['temp_id'],
                'service_id' => $service_id
            ];
            switch($service_id) {
                case 1:
                    //七牛云
                    if(empty($result)){
                        //发送失败；
                        $insert['status'] = 2;
                        $insert['reason'] = $error->message();
                    }else {
                        //发送成功；
                        $insert['request_id'] = $result['job_id'];
                        $insert['status'] = 1;
                        $data['request_id'] = $result['job_id'];
                    }
                    break;
                case 3:
                    //腾讯云
                    $insert['request_id'] = $result['RequestId'];
                    $insert['reason'] = $result['message'];
                    $insert['status'] = $result['status'];
                    break;
            }
            $insert['send_id'] = $data['params']['send_id'];
            $id = $recordModel->add($insert);

                //请求成功在发送查单(七牛云查单，腾讯回调)
            if($service_id==1 && $insert['status']==1){
                $data['message_id'] = $id;
                RabbitmqHelper::getInstance()->pushDelayMsg($data,'query_sms_status',30);
                $redis->set('qiniu_check_delay_'.$id,30);
                $redis->expire('qiniu_check_delay_'.$id,400);
            }
          
            
           
        },function() use($redis,$recordModel){
            //这里可以重新访问数据库和redis，保活mysql以及redis的socket连接，因为mysql和redis的socket连接默认8小时没有连接，服务端会主动断开客户端的连接
            $redis_val = $redis->set('keeplive_sms_push',date('Y-m-d H:i:s'));
            $recordModel->first();
        });
    }
}