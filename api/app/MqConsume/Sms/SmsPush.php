<?php
namespace App\MqConsume\Sms;
use App\Helper\RabbitmqHelper;
use App\Http\Sms\Qiniu;
use App\Http\Sms\Tx;
use App\Models\Admin\RecordModel;
use App\Helper\RedisHelper;

class SmsPush{
    use RedisHelper;
    # php /usr/share/nginx/html/www/test/laravel8/artisan ExecuteConsume --path=Test --action=up --class=Test
    public function up(){
        $queue_name = 'sms_push';
        
        $class_qiniu = new Qiniu();
        $class_tx = new Tx();
        $class = [
            1 => $class_qiniu,
            3 => $class_tx
        ];
        $recordModel = new RecordModel();
        $redis = $this->getRedisInstance();
        RabbitmqHelper::getInstance()->listen($queue_name,function($data) use($class,$recordModel){
            
            var_dump($data);
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
                $id = $recordModel->add($insert);

                //请求成功在发送查单(七牛云查单，腾讯回调)
            if($service_id==1 && $insert['status']==1){
                $data['message_id'] = $id;
                RabbitmqHelper::getInstance()->pushDelayMsg($data,'query_sms_status',20);
            }
          
            
           
        },function() use($redis,$recordModel){
            //这里可以重新访问数据库和redis，保活mysql以及redis的socket连接，因为mysql和redis的socket连接默认8小时没有连接，服务端会主动断开客户端的连接
            $redis_val = $redis->set('keeplive_sms_push',date('Y-m-d H:i:s'));
            $recordModel->first();
        });
    }
}