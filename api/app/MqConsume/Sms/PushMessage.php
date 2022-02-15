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
        $members = $this->redis->sMembers('sms_send_member');
        if ($members) { 
            $rabbitmq = RabbitmqHelper::getInstance();
            foreach($members as $key=>$temp_id){
                $count = count($members);
                $status = $this->redis->get('sms_push_status_'.$temp_id);
                $this->logger("{$temp_id} status is {$status} member count is {$count},key is {$key}",'push_message');
                if($status==='start'){
                    //获取当前模板上次发送时间
                    $last_time = $this->redis->get('sms_cur_send_'.$temp_id);

                    //获取模板发送时间间隔、
                    $gap = $this->redis->get('sms_delay_'.$temp_id);

                    //超过时间间隔就发送
                    $cur = time();
                    if(($cur-$last_time)>=$gap){
                        $nums = $this->redis->get('every_nums_'.$temp_id);
                        $this->logger("nums is {$nums}","push_message");
                        for($i=0;$i<$nums;$i++){
                            $data = $this->redis->rpop('sms_message_'.$temp_id);
                            if($data){
                                $this->logger("{$temp_id} push rabbitmq message---,key is {$i}",'push_message');
                                $rabbitmq->push(json_decode($data,true),'sms_push');
                                $this->redis->set('sms_cur_send_'.$temp_id,$cur);
                            }else {
                                //队列没有信息,则移除当前正在发送的队列
                                $this->redis->sRem('sms_send_member',$temp_id);

                                //并且统计发送成功的有多少条
                                $rabbitmq->pushDelayMsg($temp_id,'count_success_push',1);
                                $this->logger("{$temp_id} no message,rm redis queue", 'push_message');
                                // break;
                            }
                        }
                    }
                   
                }else{
                    $this->logger("{$temp_id} push status is stop",'push_message');
                }
            }
        }else{
            for($i=0;$i<3;$i++){
                 $this->logger("no message need push,key is {$i}",'push_message');
            }
           
        }
        
        
    }
}
