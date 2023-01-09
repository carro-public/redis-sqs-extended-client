<?php

namespace CarroPublic\RedisSqsExtendedClient\Jobs;

use Aws\Sqs\SqsClient;
use Illuminate\Support\Str;
use Illuminate\Container\Container;
use CarroPublic\RedisSqsExtendedClient\Connectors\SqsConnector;

class SqsJob extends \Illuminate\Queue\Jobs\SqsJob
{
    public function __construct(Container $container, SqsClient $sqs, array $job, $connectionName, $queue)
    {
        if (Str::startsWith($job['Body'], SqsConnector::getPayloadPrefix($connectionName))) {
            $job['RedisPayloadKey'] = $job['Body'];
            $job['Body'] = SqsConnector::redisConnection($connectionName)->get($job['Body']);
        }

        parent::__construct($container, $sqs, $job, $connectionName, $queue);
    }
    
    public function delete()
    {
        parent::delete();

        # Check if job has been instantiated from Redis payload
        if (isset($this->job['RedisPayloadKey'])) {
            SqsConnector::redisConnection($this->getConnectionName())->del($this->job['RedisPayloadKey']);
        }
    }
}
