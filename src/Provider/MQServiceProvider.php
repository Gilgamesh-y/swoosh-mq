<?php

namespace Src\Log\Provider;

use Src\Core\AbstractProvider;
use Src\MQ\MQManager;

class MQServiceProvider extends AbstractProvider
{
    public function register()
    {
        $this->app->set('mq', function () {
            return (new MQManager)->getConnection();
        });
    }
}
