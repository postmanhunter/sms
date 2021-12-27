<?php
namespace App\MqConsume\Sms;
use App\Helper\RabbitmqHelper;
use App\Http\Sms\Qiniu;
use App\Models\Admin\RecordModel;

class SmsPush{
    # php /usr/share/nginx/html/www/test/laravel8/artisan ExecuteConsume --path=Test --action=up --class=Test
    public function up(){
        $queue_name = 'sms_push';
        $class = new Qiniu();
        $recordModel = new RecordModel();
        RabbitmqHelper::getInstance()->listen($queue_name,function($data) use($class,$recordModel){
            list($result,$error) = $class->send($data['message'],$data['params']);
            var_dump($data['params']);
            $insert = [
                'mobile'=>$data['message'][0],
                'created_at' => date('Y-m-d H:i:s'),
                'temp_id' => $data['params']['temp_id'],
                'service_id' => $data['params']['service_id']
            ];
            if($error !== null){
                $insert['status'] = 2;
                $insert['reason'] = $error;
                //发送失败；
            }else {
                //发送成功；
                $insert['request_id'] = $result['job_id'];
                $insert['status'] = 1;
            }
            $recordModel->add($insert);
        },false,function(){
            //这里可以重新访问数据库和redis，保活mysql以及redis的socket连接，因为mysql和redis的socket连接默认8小时没有连接，服务端会主动断开客户端的连接
        });
    }
}