<?php

namespace CarroPublic\RedisSqsExtendedClient\ServiceProviders;

use Illuminate\Queue\QueueManager;
use Illuminate\Support\ServiceProvider;
use CarroPublic\RedisSqsExtendedClient\Connectors\SqsConnector;

class RedisSqsExtendedClientServiceProvider extends ServiceProvider
{
    public function boot()
    {
        /** @var QueueManager $queueManger */
        $queueManger = $this->app->make('queue');

        $queueManger->addConnector('sqs', function () {
            return new SqsConnector();
        });
    }
}
