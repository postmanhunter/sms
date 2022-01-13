<?php
namespace App\MqConsume\Sms;
use App\Helper\RabbitmqHelper;
use App\Helper\LoggerHelper;
use App\Helper\RedisHelper;

class PushMessage
{
    use LoggerHelper,RedisHelper;
    # * * * * * php /usr/share/nginx/html/www/sms/api/scripts/second.php
    # php /usr/share/nginx/html/www/test/laravel8/artisan ExecuteConsume --path=Test --action=up --class=QuerySmsStatus
    public function __construct()
    {
        $this->redis = $this->getRedisInstance();
    }
    public function up()
    {
        if($this->redis->get('sms_push_status')==='start'){
            $rabbitmq = RabbitmqHelper::getInstance();
            $data = $this->redis->rpop('sms_message');
            if($data){
                $delay_count = $this->redis->get('sms_delay_count');
                $delay = $this->redis->get('sms_delay');
                $this->logger("{$delay_count}---{$delay}",'push_message');
                $rabbitmq->pushDelayMsg(json_decode($data,true),'sms_push',$delay_count);
                $this->redis->set('sms_delay_count',$delay_count+$delay);
            }else {
                $this->logger("no message",'push_message');
            }

            $data = $this->redis->rpop('sms_message');
            if($data){
                $delay_count = $this->redis->get('sms_delay_count');
                $delay = $this->redis->get('sms_delay');
                $this->logger("{$delay_count}---{$delay}",'push_message');
                $rabbitmq->pushDelayMsg(json_decode($data,true),'sms_push',$delay_count);
                $this->redis->set('sms_delay_count',$delay_count+$delay);
            }else {
                $this->logger("no message",'push_message');
            }
            
            $data = $this->redis->rpop('sms_message');
            if($data){
                $delay_count = $this->redis->get('sms_delay_count');
                $delay = $this->redis->get('sms_delay');
                $this->logger("{$delay_count}---{$delay}",'push_message');
                $rabbitmq->pushDelayMsg(json_decode($data,true),'sms_push',$delay_count);
                $this->redis->set('sms_delay_count',$delay_count+$delay);
            }else {
                $this->logger("no message",'push_message');
            }
        }else{
            $this->logger("push stop",'push_message');
        }
        
    }
}