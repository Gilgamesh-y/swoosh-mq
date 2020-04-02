<?php

namespace Src\MQ;

use Src\App;
use Src\MQ\Connections\KafkaConnection;
use Src\MQ\Connections\RabbitmqConnection;

class MQManager
{
    /**
     * MQ driver
     */
    protected $driver;
    
    /**
     * Witch MQ service will be used
     */
    public function createConnection(array $config, array $route_config)
    {
        $config = $this->getMQConfig();
        switch ($config['driver']) {
            case 'rabbitmq':
                return new RabbitmqConnection($config, $route_config);
                break;
            case 'kafka':
                return new KafkaConnection($config, $route_config);
                break;
        }

        throw new \Exception('Unsupported driver [' . $config['driver'] . ']');
    }

    public function getConnection(array $config, array $route_config)
    {
        if (!$this->driver) {
            $this->driver = $this->createConnection($config, $route_config);
        }
        return $this->driver;
    }

    public function getMQConfig()
    {
        return App::get('config')->get('app.mq');
    }

    public function __call($method, $parameters)
    {
        return $this->getChannel()->$method(...$parameters);
    }
}