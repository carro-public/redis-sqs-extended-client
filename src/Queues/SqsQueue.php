<?php

namespace CarroPublic\RedisSqsExtendedClient\Queues;

use CarroPublic\RedisSqsExtendedClient\Jobs\SqsJob;
use CarroPublic\RedisSqsExtendedClient\Connectors\SqsConnector;

class SqsQueue extends \Illuminate\Queue\SqsQueue
{
    /**
     * Push a raw payload onto the queue.
     *
     * @param  string  $payload
     * @param  string|null  $queue
     * @param  array  $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $payload = static::ensureValidPayloadSize($payload, $this->connectionName);

        return parent::pushRaw($payload, $queue, $options);
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string|null  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        $response = $this->sqs->receiveMessage([
            'QueueUrl' => $queue = $this->getQueue($queue),
            'AttributeNames' => ['ApproximateReceiveCount'],
        ]);

        if (! is_null($response['Messages']) && count($response['Messages']) > 0) {
            return app()->make(SqsJob::class, [
                'container' => $this->container,
                'sqs' => $this->sqs,
                'job' => $response['Messages'][0],
                'connectionName' => $this->connectionName,
                'queue' => $queue,
            ]);
        }
    }

    /**
     * Check payload size, if larger than threshold, move to redis store
     * @param $payload
     * @param $connectionName
     * @return string
     */
    public static function ensureValidPayloadSize($payload, $connectionName)
    {
        if (strlen($payload) >= SqsConnector::getPayloadThreshold($connectionName)) {
            $payloadId = data_get(json_decode($payload, true), 'uuid', md5($payload));

            $payloadKey = SqsConnector::getPayloadPrefix($connectionName) . $payloadId;

            # Push payload to Redis DB and set TTL = 14 days
            SqsConnector::redisConnection($connectionName)->set(
                $payloadKey,
                $payload,
                'EX',
                SqsConnector::getPayloadRetentionDays($connectionName) * 86400,
            );

            # Update Job Payload to Put in SQS
            # We will only put the $payloadKey, later we will fetch the whole payload from Redis DB
            return $payloadKey;
        }

        return $payload;
    }
}
