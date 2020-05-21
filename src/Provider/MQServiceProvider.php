<?php

namespace Src\MQ\Provider;

use Src\App;
use Src\MQ\MQManager;
use Swoole\Server;
use Src\Core\AbstractProvider;

class MQServiceProvider extends AbstractProvider
{
    public function register()
    {
        $this->app->set('mq', function () {
            $config = App::get('config')->get('app.mq');
            $route_config = App::get('config')->get('mq');
            return (new MQManager)->getConnection($config, $route_config);
        });

        $this->app->set('mq_receiver', function () {
            $config = App::get('config')->get('app.mq');
            $route_config = App::get('config')->get('mq');
            return (new MQManager)->getConnection($config, $route_config)->create_receiver();
        });
    }

    public function active()
    {
        $this->app->active('mq_receiver');
    }
}
