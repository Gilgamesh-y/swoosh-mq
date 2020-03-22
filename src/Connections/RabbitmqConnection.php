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
     * Message durability
     *
     * @var boolean
     */
    protected $durability = false;

    /**
     * If this var is true ,it's mean dont't need ack
     *
     * @var boolean
     */
    protected $no_ack = false;

    /**
     * The list of the rabbitmq queue name
     *
     * @var array
     */
    protected $queues = [];

    /**
     * The list of the rabbitmq exchange name
     *
     * @var array
     */
    protected $exchanges = [];

    /**
     * The channel of the rabbitmq
     *
     * @var AMQPChannel
     */
    protected $channels = [];

    /**
     * The connection of the rabbitmq
     *
     * @var AMQPStreamConnection
     */
    protected $connection = [];

    public function __construct()
    {
        $config = App::get('config')->get('app.mq');
        $this->host = $config['host'];
        $this->port = $config['port'];
        $this->user = $config['user'];
        $this->password = $config['password'];
        $this->durability = $config['durability'];
        $this->no_ack = $config['no_ack'];

        $rabbitmq_name_config = App::get('config')->get('rabbitmq');
        $this->queues = $rabbitmq_name_config['queues'];
        $this->exchanges = $rabbitmq_name_config['exchanges'];
    }

    /**
     * Get the connection of the rabbitmq
     *
     * @return AMQPStreamConnection
     */
    public function getConnection(): AMQPStreamConnection
    {
        $cid = \Swoole\Coroutine::getCid();
        if (!isset($this->connection[$cid])) {
            $this->connection[$cid] = new AMQPStreamConnection($this->host, $this->port, $this->user, $this->password);
        }
        
        return $this->connection[$cid];
    }

    /**
     * Get the channel of the rabbitmq
     *
     * @return AMQPChannel
     */
    public function getChannel(): AMQPChannel
    {
        $cid = \Swoole\Coroutine::getCid();
        if (!isset($this->channels[$cid])) {
            $this->channels[$cid] = $this->getConnection()->channel();
        }
        
        
        return $this->channels[$cid];
    }

    /**
     * Send msg
     */

    /**
     * Send msg to queue
     *
     * @param string $queue_name
     * @param $msg
     * @return void
     */
    public function send_to_queue(string $queue_name, $msg)
    {
        $this->getChannel()->queue_declare($queue_name, false, $this->durability);
        $msg = new AMQPMessage($msg);
        $this->getChannel()->basic_publish($msg, '', $queue_name);
        $this->close();

        return;
    }

    /**
     * Send msg to queue
     *
     * @param string $exchange_name
     * @param mix $msg
     * @param string $routing_key
     * @return void
     */
    public function send_to_exchange(string $exchange_name, $msg, string $routing_key = '')
    {
        $type = isset($this->exchanges[$exchange_name]) ? $this->exchanges[$exchange_name]['type'] : 'fanout';
        $this->getChannel()->exchange_declare($exchange_name, $type, false, false, false);
        $msg = new AMQPMessage($msg);
        $this->getChannel()->basic_publish($msg, $exchange_name, $routing_key);
        $this->close();

        return;
    }

    /**
     * Receive msg from queue
     *
     * @return void
     */
    public function receive_from_queue()
    {
        foreach ($this->queues as $queue_name) {
            go(function () use ($queue_name) {
                defer(function () {
                    $this->close();
                });
                $this->getChannel()->queue_declare($queue_name, false, $this->durability);
                // Fair scheduling
                $this->getChannel()->basic_qos(null, 1, null);

                $this->basic_consume($queue_name);
                while(count($this->getChannel()->callbacks)) {
                    $this->getChannel()->wait();
                }
            });
        }
    }

    /**
     * Receive msg from exchange
     *
     * @return void
     */
    public function receive_from_exchange()
    {
        foreach ($this->exchanges as $exchange => $config) {
            go(function () use ($exchange, $config) {
                defer(function () {
                    $this->close();
                });
                $type = $config['type'];
                $queues = isset($config['queues']) ? $config['queues'] : null;
                // Create exchange and set type
                $this->getChannel()->exchange_declare($exchange, $type, false, false, false);
                // Binding queue、exchange and routing_key when routing_key exists and the type is not fanout
                if (!$queues) {
                    try {
                        list($queue_name, ) = $this->getChannel()->queue_declare('', false, $this->durability, true, false);
                        $this->getChannel()->queue_bind($queue_name, $exchange);
                        $this->basic_consume($queue_name);
                    } catch (\Throwable $th) {
                        //throw $th;
                    }
                }
                if ($queues && $type != 'fanout') {
                    foreach ($queues as $queue_name => $routing_keys) {
                        try {
                            $this->getChannel()->queue_declare($queue_name, false, $this->durability, true, false);
                            foreach ($routing_keys as $routing_key) {
                                $this->getChannel()->queue_bind($queue_name, $exchange, $routing_key);
                            }
                            $this->basic_consume($queue_name);
                        } catch (\Throwable $th) {
                            //throw $th;
                        }
                    }
                }
                while(count($this->getChannel()->callbacks)) {
                    $this->getChannel()->wait();
                }
            });
        }
    }

    public function basic_consume(string $queue_name)
    {
        $this->getChannel()->basic_consume($queue_name, '', false, $this->no_ack, false, false, function($msg) {
            event(unserialize($msg->body));
            if (!$this->no_ack) {
                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
            }
        });
    }

    public function receive()
    {
        $this->receive_from_exchange();
        $this->receive_from_queue();
    }

    public function close()
    {
        $this->getConnection()->close();
        $this->getChannel()->close();
        $this->connection = $this->channel = null;
    }
}