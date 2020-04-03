<?php

namespace Src\MQ\Connections;

use Src\MQ\Contract\ConnectionInterface;

abstract class Connection implements ConnectionInterface
{
    /**
     * The host of the mq
     *
     * @var string
     */
    protected $host = '127.0.0.1';

    /**
     * The port of the mq
     *
     * @var string
     */
    protected $port = 5672;

    /**
     * The user of the mq
     *
     * @var string
     */
    protected $user = 'guest';

    /**
     * The password of the mq
     *
     * @var string
     */
    protected $password = 'guest';

    public function __construct(array $config, array $route_config)
    {
        $this->host = $config['host'];
        $this->port = $config['port'];
        $this->user = $config['user'];
        $this->password = $config['password'];
    }
}