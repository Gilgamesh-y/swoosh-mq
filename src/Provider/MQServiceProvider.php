<?php

namespace Src\MQ\Provider;

use Src\Core\AbstractProvider;
use Src\MQ\MQManager;

class MQServiceProvider extends AbstractProvider
{
    public function register()
    {
        $this->app->set('mq', function () {
            return (new MQManager)->getConnection();
        });

        $this->app->set('mq_receiver', function () {
            return (new MQManager)->getConnection()->receive();
        });
    }
}
