<?php

namespace Src\MQ\Connections;

class KafkaConnection extends Connection
{
    /**
     * The list of kafka topic
     *
     * @var array
     */
    protected $topics = [];

    public function __construct(array $config, array $route_config)
    {
        parent::__construct($config, $route_config);
        
        $this->topics = $route_config['kafka']['topics'];
    }

    public function send(string $topic, $msg)
    {
        if (is_object($msg)) {
            $msg = serialize($msg);
        }
        $conf = new \RdKafka\Conf();
        $conf->set('metadata.broker.list', $this->host.':'.$this->port);

        //If you need to produce exactly once and want to keep the original produce order, uncomment the line below
        //$conf->set('enable.idempotence', 'true');

        $producer = new \RdKafka\Producer($conf);

        $topic = $producer->newTopic($topic);
        $topic->produce(RD_KAFKA_PARTITION_UA, 0, $msg);
        $producer->poll(0);
        $result = $producer->flush(10000);
        if (RD_KAFKA_RESP_ERR_NO_ERROR !== $result) {
            throw new \RuntimeException('Was unable to flush, messages might be lost!');
        }
    }

    public function create_receiver()
    {
        $conf = new \RdKafka\Conf();

        // Set a rebalance callback to log partition assignments (optional)
        $conf->setRebalanceCb(function (\RdKafka\KafkaConsumer $kafka, $err, array $partitions = null) {
            switch ($err) {
                case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
                    $kafka->assign($partitions);
                    break;

                case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
                    $kafka->assign(NULL);
                    break;

                default:
                    throw new \Exception($err);
            }
        });

        // Configure the group.id. All consumer with the same group.id will consume
        // different partitions.
        $conf->set('group.id', 'myConsumerGroup');

        // Initial list of Kafka brokers
        $conf->set('metadata.broker.list', $this->host.':'.$this->port);

        // Set where to start consuming messages when there is no initial offset in
        // offset store or the desired offset is out of range.
        // 'smallest': start from the beginning
        $conf->set('auto.offset.reset', 'smallest');

        $consumer = new \RdKafka\KafkaConsumer($conf);

        // Subscribe to topic 'test'
        $consumer->subscribe($this->topics);

        /**
         * message
         * object(RdKafka\Message)#41 (9) {
            ["err"]=>
            int(0)
            ["topic_name"]=>
            string(2) "t1"
            ["timestamp"]=>
            int(1585822432268)
            ["partition"]=>
            int(0)
            ["payload"]=>
            string(9) "Message 8"
            ["len"]=>
            int(9)
            ["key"]=>
            NULL
            ["offset"]=>
            int(148)
            ["headers"]=>
            NULL
            }
         */
        swoole_timer_tick(500, function () use ($consumer) {
            $message = $consumer->consume(0);
            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    event(unserialize($message->payload));
                    break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    echo "No more messages; will wait for more\n";
                    break;
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    break;
                default:
                    throw new \Exception($message->errstr(), $message->err);
                    break;
            }
        });
    }
}