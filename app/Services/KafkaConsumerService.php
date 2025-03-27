<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class KafkaConsumerService
{
    protected $consumer;
    protected $topic;

    public function __construct()
    {
        $conf = new \RdKafka\Conf();

        $conf->set('metadata.broker.list', env('KAFKA_BROKERS', 'localhost:9092'));

        // Set the group id (required)
        $conf->set('group.id', env('KAFKA_GROUP_ID', 'laravel_kafka_group'));

        // Set where to start consuming messages when there is no initial offset
        $conf->set('auto.offset.reset', 'earliest');

        // Enable auto commit (optional)
        $conf->set('enable.auto.commit', 'false');

        // Set error callback
        $conf->setErrorCb(function ($kafka, $err, $reason) {
            Log::error("Kafka error: " . rd_kafka_err2str($err) . " (reason: $reason)");
        });

        // Set rebalance callback
        $conf->setRebalanceCb(function ($kafka, $err, $partitions) {
            switch ($err) {
                case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
                    $kafka->assign($partitions);
                    break;
                case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
                    $kafka->assign(NULL);
                    break;
                default:
                    throw new \Exception($err);
            }
        });

        $this->consumer = new \RdKafka\KafkaConsumer($conf);
    }

    public function consume(string $topicName, callable $handler, int $timeoutMs = 10000)
    {
        // Subscribe to topic
        $this->consumer->subscribe([$topicName]);

        Log::info("Starting to consume messages from topic: {$topicName}");

        while (true) {
            $message = $this->consumer->consume($timeoutMs);

            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    // Process message
                    $handler($message->payload, $message);

                    // Manually commit offset (if auto commit is disabled)
                    $this->consumer->commit($message);
                    break;

                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    Log::debug("No more messages; will wait for more");
                    break;

                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    Log::debug("Timed out waiting for message");
                    break;

                default:
                    throw new \Exception($message->errstr(), $message->err);
            }
        }
    }
}
