<?php

namespace CarroPublic\RedisSqsExtendedClient\Connectors;

use Aws\Sqs\SqsClient;
use Illuminate\Support\Arr;
use Illuminate\Queue\SqsQueue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Contracts\Container\BindingResolutionException;

class SqsConnector extends \Illuminate\Queue\Connectors\SqsConnector
{
    /**
     * Establish a queue connection.
     *
     * @param array $config
     * @return SqsQueue
     * @throws BindingResolutionException
     */
    public function connect(array $config)
    {
        $config = $this->getDefaultConfiguration($config);

        if (!empty($config['key']) && !empty($config['secret'])) {
            $config['credentials'] = Arr::only($config, ['key', 'secret', 'token']);
        }

        if (isset($config['redis_storage'])) {
            return app()->make(\CarroPublic\RedisSqsExtendedClient\Queues\SqsQueue::class, [
                'sqs' => new SqsClient($config),
                'default' => $config['queue'],
                'prefix' => $config['prefix'] ?? '',
                'suffix' => $config['suffix'] ?? '',
                'dispatchAfterCommit' => $config['after_commit'] ?? null,
            ]);
        }

        return new SqsQueue(
            new SqsClient($config),
            $config['queue'],
            $config['prefix'] ?? '',
            $config['suffix'] ?? '',
            $config['after_commit'] ?? null
        );
    }

    /**
     * @param $queueConnection
     * @return Connection
     */
    public static function redisConnection($queueConnection)
    {
        return Redis::connection(config("queue.connections.{$queueConnection}.redis_storage.connection", "default"));
    }

    /**
     * @param $queueConnection
     * @return integer
     */
    public static function getPayloadThreshold($queueConnection)
    {
        return config("queue.connections.{$queueConnection}.redis_storage.threshold", 262144);
    }

    /**
     * @param $queueConnection
     * @return integer
     */
    public static function getPayloadPrefix($queueConnection)
    {
        return config("queue.connections.{$queueConnection}.redis_storage.prefix", 'sqs_payload_');
    }

    /**
     * @param $queueConnection
     * @return integer
     */
    public static function getPayloadRetentionDays($queueConnection)
    {
        return config("queue.connections.{$queueConnection}.redis_storage.retention_days", 14);
    }
}
