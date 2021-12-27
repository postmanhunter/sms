<?php
namespace App\MqConsume\Sms;
use App\Helper\RabbitmqHelper;
use App\Http\Sms\Qiniu;
use App\Http\Sms\Tx;
use App\Models\Admin\RecordModel;

class SmsPush{
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
        RabbitmqHelper::getInstance()->listen($queue_name,function($data) use($class,$recordModel){
            $service_id = $data['params']['service_id'];
            list($result,$error) = $class[$service_id]->send($data['message'],$data['params']);
            var_dump($result,$service_id);
            $insert = [
                'mobile'=>$data['message'][0],
                'created_at' => date('Y-m-d H:i:s'),
                'temp_id' => $data['params']['temp_id'],
                'service_id' => $service_id
            ];
            switch($service_id) {
                case 1:
                    //七牛云
                    if($error !== null){
                        $insert['status'] = 2;
                        $insert['reason'] = $error;
                        //发送失败；
                    }else {
                        //发送成功；
                        $insert['request_id'] = $result['job_id'];
                        $insert['status'] = 1;
                    }
                    break;
                case 3:
                    //腾讯云
                    $insert['request_id'] = $result['RequestId'];
                    $insert['reason'] = $result['message'];
                 
                    $insert['status'] = $result['status'];
                  
                    break;
            }
            $recordModel->add($insert);
        },false,function(){
            //这里可以重新访问数据库和redis，保活mysql以及redis的socket连接，因为mysql和redis的socket连接默认8小时没有连接，服务端会主动断开客户端的连接
        });
    }
}