<?php
namespace App\MqConsume\Sms;
use App\Helper\RabbitmqHelper;
use App\Helper\LoggerHelper;

class KeepLive
{
    use LoggerHelper;
    # php /usr/share/nginx/html/www/test/laravel8/artisan ExecuteConsume --path=Test --action=up --class=QuerySmsStatus
    public function up()
    {
        $queue_name = array('query_sms_status','sms_push');
        $message = [
            '__keep_alive__' => 1
        ];
        foreach($queue_name as $val){
            $str = 'keep_live:'.$val;
            $this->logger($str,'keeplive');
            RabbitmqHelper::getInstance()->push($message,$val);
        }
        
    }
}