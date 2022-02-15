<?php
namespace App\MqConsume\Sms;
use App\Helper\RabbitmqHelper;
use App\Models\Admin\SendModel;

class CountSuccessPush
{
    # php /www/wwwroot/sms/api/artisan ExecuteConsume --path=Sms --action=up --class=CountSuccessPush
    # php /usr/share/nginx/html/www/test/laravel8/artisan ExecuteConsume --path=Sms --action=up --class=QuerySmsStatus
    public function up()
    {
        $queue_name = 'count_success_push';
        $sendModel = new SendModel();
      
        RabbitmqHelper::getInstance()->listen($queue_name,function($data) use($sendModel){
            var_dump($data);
            $sendModel->countNum($data);
        });
    }
}