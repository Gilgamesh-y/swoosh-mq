<?php

namespace Src\MQ;

use Src\App;
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
    public function createConnection()
    {
        $config = $this->getMQConfig();
        switch ($config['driver']) {
            case 'rabbitmq':
                return new RabbitmqConnection;
                break;
        }

        throw new \Exception('Unsupported driver [' . $config['driver'] . ']');
    }

    public function getConnection()
    {
        if (!$this->driver) {
            $this->driver = $this->createConnection();
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