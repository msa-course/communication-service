<?php

namespace App\Console\Commands;

use App\Services\KafkaConsumerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ConsumeKafkaMessages extends Command
{
    protected $signature = 'kafka:consume {topic?}';
    protected $description = 'Consume messages from Kafka topic';

    public function handle(KafkaConsumerService $consumer)
    {
        $topic = $this->argument('topic') ?: env('KAFKA_TOPIC', 'laravel_kafka_topic');

        $this->info("Starting Kafka consumer for topic: {$topic}");

        $consumer->consume($topic, function ($message, $kafkaMessage) {
            $this->info("Received message: " . $message);
            Log::info('Kafka message received', [
                'payload' => $message,
                'topic' => $kafkaMessage->topic_name,
                'partition' => $kafkaMessage->partition,
                'offset' => $kafkaMessage->offset
            ]);
        });
    }
}
