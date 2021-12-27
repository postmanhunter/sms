<?php
namespace App\Helper;
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Exception;

class RabbitmqHelper
{
    protected static $instance = null;

    public static function getInstance()
    {
        if (is_null(RabbitmqHelper::$instance)) {
            RabbitmqHelper::$instance = new RabbitmqHelper(config('rabbitmq.default'));
        }

        return RabbitmqHelper::$instance;
    }
    /**
     * 获取所有队列的等待消息的消息以及当前队列的消费者个数
     */
    function getQueues()
    {
        $rabbitmqConfig = config('rabbitmq.default');
        $rabbitmqHost = $rabbitmqConfig['host'];
        $rabbitmqApiport = $rabbitmqConfig['api_port'];
        $rabbitmqVhost = $rabbitmqConfig['vhost'];
        $rabbitmqUsername = $rabbitmqConfig['username'];
        $rabbitmqPassword = $rabbitmqConfig['password'];
        $url = "http://{$rabbitmqHost}:{$rabbitmqApiport}/api/queues{$rabbitmqVhost}";
        $cmd = "curl -s -u {$rabbitmqUsername}:{$rabbitmqPassword} {$url}";
        $ds = json_decode(`$cmd`, true);
        if (!is_array($ds)) {
            return [];
        }

        $ret = [];
        foreach ($ds as $q) {
            if ($q['state'] != 'running') {
                continue;
            }
            $ret[$q['name']] = [
                'consumers' => $q['consumers'],
                'messages_ready' => $q['messages_ready']
            ];
        }
        return $ret;
    }
    //增减所有队列的消费者进程
    function changeConsumers($is_scale, $circus_watcher_name, $consumers, $numprocesses, $incr_count = 5)
    {
        try {
            switch ($is_scale) {
                case true: // 增加
                    for ($i = 1; $i <= $incr_count; $i++) {
                        $cmd = "/usr/local/bin/circusctl --endpoint tcp://backscript-srv:5555 incr {$circus_watcher_name}";
                        
                        $out = trim(`$cmd`);
                        
                    }
                   
                    break;
                case false: // 减少
                    // 消费者数 > 进程数, 执行缩减
                    if ($consumers > $numprocesses) {
                        $cmd = "/usr/local/bin/circusctl --endpoint tcp://backscript-srv:5555 decr {$circus_watcher_name}";
                       
                        $out = trim(`$cmd`);
                        
                    }
                    break;
            }
        } catch (Exception $ex) {
           
        }
    }

    private  $connection;
    private  $channel;
    private  $config;

    /**
     * RabbitmqHelper constructor.
     * 建立连接
     * @param $config
     */
    function __construct(array $config)
    {
        $this->connection = new AMQPStreamConnection($config['host'], $config['port'], $config['username'], $config['password'], $config['vhost']);
        $this->channel = $this->connection->channel();
        $this->config = $config;
    }

    function listen($queue_name, $fn, $fn_keepalive = null)
    {
        if (!is_callable($fn)) {
            return;
        }
        
        $this->queue_declare($queue_name);

        $this->pop($queue_name, function ($msg) use ($fn, $fn_keepalive) {
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);

            $body = unserialize($msg->body);
            
            if (isset($body['__keep_alive__']) && isset($body['__keep_alive__']) == '1') {
                if (is_callable($fn_keepalive)) {
                    $fn_keepalive();
                }
            } else {
                $fn($body);
            }
        }); 
    }

    /**
     * 消息入队列(非延时)
     * @param mixed  $msg 消息
     * @param string $queue 队列名称
     */
    function push($msg, $queue)
    {
        $msg = serialize($msg);
        $this->queue_declare($queue);
        $message = new AMQPMessage($msg, array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
        $this->channel->basic_publish($message, '', $queue);
    }

    /**
     * 消息出队列
     * @param string $queue 队列名称
     * @param        $callback 处理消息的回调函数
     */
    function pop($queue, $callback)
    {
        $this->channel->basic_qos(null, 1, null);
        $this->channel->basic_consume($queue, '', false, false, false, false, $callback);

        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
       
    }

    /**
     * 声明队列，不存在会去创建
     */
    public function queue_declare($queue)
    {
        $this->channel->queue_declare($queue, false, true, false, false);
    }

    /**
     * 关闭连接
     */
    function close()
    {
        $this->channel->close();
        $this->connection->close();
    }
    /**
     * 延时队列
     * @param $msgData string 消息
     * @param $queueName string 队列名称
     * @param int $ttl 延时时间 单位是s 默认5s
     * @throws \Exception
     */
    public function pushDelayMsg($msgData, $queueName, $ttl = 5)
    {
        $msgData = serialize($msgData);
        $this->channel->exchange_declare(
            $queueName,
            'x-delayed-message',
            false,
            true,
            false,
            false,
            false,
            new AMQPTable([
                "x-delayed-type" => 'direct'
            ])
        );
        $this->channel->queue_declare($queueName, false, true, false, false);
        $this->channel->queue_bind($queueName, $queueName, $queueName);
        $msg = new AMQPMessage(
            $msgData,
            [
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'application_headers' => new AMQPTable([
                    'x-delay' => $ttl * 1000,
                ])
            ]
        );
        $this->channel->basic_publish($msg, $queueName, $queueName);
    }
}
