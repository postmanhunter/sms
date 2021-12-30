<?php
namespace App\MqConsume\Sms;
use App\Helper\RabbitmqHelper;
use App\Http\Sms\Qiniu;
use App\Http\Sms\Tx;
use App\Models\Admin\RecordModel;

class QuerySmsStatus
{
    # php /usr/share/nginx/html/www/test/laravel8/artisan ExecuteConsume --path=Test --action=up --class=Test
    public function up()
    {
        $queue_name = 'query_sms_status';
        
        $class_qiniu = new Qiniu();
        $class_tx = new Tx();
        $class = [
            1 => $class_qiniu,
            3 => $class_tx
        ];
        $recordModel = new RecordModel();
        RabbitmqHelper::getInstance()->listen($queue_name,function($data) use($class,$recordModel){
            var_dump($data);
            $service_id = $data['params']['service_id'];
            $status = $class[$service_id]->getSmsStatus($data['params'],$data['request_id']);
            var_dump($status);
            if($status){
                $recordModel->updateSms($data['message_id'],3);
            }else{
                $recordModel->updateSms($data['message_id'],2);
            }
        });
    }
}