<?php

namespace Src\MQ\Connections;

use Kafka\Consumer;
use Kafka\ConsumerConfig;

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

    public function create_receiver()
    {
        $config = ConsumerConfig::getInstance();
        $config->setMetadataRefreshIntervalMs(10000);
        $config->setMetadataBrokerList('127.0.0.1:9093');
        $config->setGroupId('test');
        $config->setBrokerVersion('1.0.0');
        $config->setTopics(['t1']);
        $config->setOffsetReset('earliest');
        // if use ssl connect
        //$config->setSslLocalCert('/home/vagrant/code/kafka-php/ca-cert');
        //$config->setSslLocalPk('/home/vagrant/code/kafka-php/ca-key');
        //$config->setSslEnable(true);
        //$config->setSslPassphrase('123456');
        //$config->setSslPeerName('nmred');
        $consumer = new Consumer();
        $consumer->start(function ($topic, $part, $message): void {
            var_dump($message);
        });
    }
}