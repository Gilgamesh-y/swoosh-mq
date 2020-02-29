<?php

namespace Src\MQ\Connections;

use Src\App;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitmqConnection
{
    /**
     * The host of the rabbitmq
     *
     * @var string
     */
    protected $host = '127.0.0.1';

    /**
     * The port of the rabbitmq
     *
     * @var string
     */
    protected $port = 5672;

    /**
     * The user of the rabbitmq
     *
     * @var string
     */
    protected $user = 'guest';

    /**
     * The password of the rabbitmq
     *
     * @var string
     */
    protected $password = 'guest';

    /**
     * The lsit of the rabbitmq queue name
     *
     * @var array
     */
    protected $queue_names = [];

    /**
     * The channel of the rabbitmq
     *
     * @var AMQPChannel
     */
    protected $channel;

    /**
     * The receiver quantity of the rabbitmq
     *
     * @var int
     */
    protected $receiver_quantity;

    /**
     * The connection of the rabbitmq
     *
     * @var AMQPStreamConnection
     */
    protected $connection;

    public function __construct()
    {
        $config = App::get('config')->get('app.mq');
        $rabbitmq_config = App::get('config')->get('rabbitmq');
        $this->host = $config['host'];
        $this->port = $config['port'];
        $this->user = $config['user'];
        $this->password = $config['password'];
        $this->receiver_quantity = $config['receiver_quantity'];
        $this->queue_names = $rabbitmq_config['queue_name'];
    }

    /**
     * Get the connection of the rabbitmq
     *
     * @return AMQPStreamConnection
     */
    public function getConnection(): AMQPStreamConnection
    {
        if (!isset($this->chaconnectionnnel)) {
            $this->connection = new AMQPStreamConnection($this->host, $this->port, $this->user, $this->password);
        }
        
        return $this->connection;
    }

    /**
     * Get the channel of the rabbitmq
     *
     * @return AMQPChannel
     */
    public function getChannel(): AMQPChannel
    {
        if (!isset($this->channel)) {
            $this->channel = $this->getConnection()->channel();
        }
        
        
        return $this->channel;
    }

    /**
     * Send msg to queue
     *
     * @param string $queue_name
     * @param $msg
     * @return void
     */
    public function send(string $queue_name, $msg)
    {
        $this->getChannel()->queue_declare($queue_name);
        $msg = new AMQPMessage($msg);
        $this->getChannel()->basic_publish($msg, '', $queue_name);
        $this->close();
    }

    public function receive()
    {
        for ($i = 0; $i < $this->receiver_quantity; $i++) {
            go(function () {
                foreach ($this->queue_names as $queue_name) {
                    $this->getChannel()->queue_declare($queue_name);
                    $this->getChannel()->basic_consume($queue_name, '', false, true, false, false, function($msg) {
                        echo $msg->body, "\n";
                    });
                }
                while(count($this->getChannel()->callbacks)) {
                    $this->getChannel()->wait();
                }
            });
        }
    }

    public function close()
    {
        $this->getConnection()->close();
        $this->getChannel()->close();
        $this->connection = $this->channel = null;
    }
}