<?php
namespace App\MqConsume\Sms;
use App\Helper\RabbitmqHelper;
use App\Http\Sms\Qiniu;
use App\Http\Sms\Tx;
use App\Models\Admin\RecordModel;
use App\Helper\RedisHelper;

class QuerySmsStatus
{
    use RedisHelper;
    # php /www/wwwroot/sms/api/artisan ExecuteConsume --path=Sms --action=up --class=QuerySmsStatus
    # php /usr/share/nginx/html/www/test/laravel8/artisan ExecuteConsume --path=Sms --action=up --class=QuerySmsStatus
    public function up()
    {
        $queue_name = 'query_sms_status';
        
        $class_qiniu = new Qiniu();
        $class = [
            1 => $class_qiniu,
        ];
        $recordModel = new RecordModel();
        $redis = $this->getRedisInstance();
        RabbitmqHelper::getInstance()->listen($queue_name,function($data) use($class,$recordModel,$redis){
            var_dump($data);
            $service_id = $data['params']['service_id'];
            $status = $class[$service_id]->getSmsStatus($data['params'],$data['request_id']);
            var_dump($status);
            if($status){
                $recordModel->updateSms($data['message_id'],3);
            }else{
                $delay = $redis->get('qiniu_check_delay_'.$data['message_id']);
                if ($delay && $delay<300) {
                    echo $delay.date('Y-m-d H:i:s').PHP_EOL;
                    $delay += 30;
                    RabbitmqHelper::getInstance()->pushDelayMsg($data,'query_sms_status',$delay);
                    $redis->set('qiniu_check_delay_'.$data['message_id'],$delay);
                    $redis->expire('qiniu_check_delay_'.$data['message_id'],400);
                } else {
                    $recordModel->updateSms($data['message_id'],2);
                }
            }
        },function() use($redis,$recordModel){
            //这里可以重新访问数据库和redis，保活mysql以及redis的socket连接，因为mysql和redis的socket连接默认8小时没有连接，服务端会主动断开客户端的连接
            $redis->set('keeplive_sms_push',date('Y-m-d H:i:s'));
            $recordModel->first();
        });
    }
}