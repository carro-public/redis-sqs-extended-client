# Redis SQS Extended Client

## Introduce

SQS message limit size is 256KB. So for those Job's Payload larger than this, we can't push directly to SQS.

https://docs.aws.amazon.com/AWSSimpleQueueService/latest/SQSDeveloperGuide/quotas-messages.html
```
The minimum message size is 1 byte (1 character). The maximum is 262,144 bytes (256 KiB).

To send messages larger than 256 KB, you can use the Amazon SQS Extended Client Library for Java. This library allows you to send an Amazon SQS message that contains a reference to a message payload in Amazon S3. The maximum payload size is 2 GB.
```

It will throw exception like below

```log
(Aws\\Sqs\\Exception\\SqsException(code: 0): Error executing \"SendMessage\" on \"https://sqs.ap-southeast-1.amazonaws.com/xxxxxxx/queue-name\"; AWS HTTP error: Client error: `POST https://sqs.ap-southeast-1.amazonaws.com/xxxxxxx/queue-name` resulted in a `400 Bad Request` response:\n<?xml version=\"1.0\"?><ErrorResponse xmlns=\"http://queue.amazonaws.com/doc/2012-11-05/\"><Error><Type>Sender</Type><Code>I (truncated...)\n InvalidParameterValue (client): One or more parameters are invalid. Reason: Message must be shorter than 262144 bytes. - <?xml version=\"1.0\"?><ErrorResponse xmlns=\"http://queue.amazonaws.com/doc/2012-11-05/\"><Error><Type>Sender</Type><Code>InvalidParameterValue</Code><Message>One or more parameters are invalid. Reason: Message must be shorter than 262144 bytes.</Message><Detail/></Error><RequestId>xxxxxxx</RequestId></ErrorResponse> at ...
```

## Install

```shell
composer require carropublic/redis-sqs-extended-client
```

## Config

```php
return [
    'sqs' => [
        'driver' => 'sqs',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
        'queue' => env('QUEUE_TUBE', 'default'),
        'suffix' => env('SQS_SUFFIX'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        # Extra Config of the Redis Connection to Store Large Payload
        'redis_storage' => [
            # The credentials of Redis connection should be configured in database.redis
            'connection' => 'default',
             # 256KB
            'threshold' => 262144,
            'prefix' => 'sqs_payload_',
            # SQS Message has max retention of 14 days
            'retention_days' => 14,
        ]
    ],
];
```
