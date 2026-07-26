<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Kafka Broker
    |--------------------------------------------------------------------------
    |
    | The host and port of your Kafka broker. In Docker, this should match
    | the service name and exposed port from docker-compose.yml.
    |
    */
    'brokers' => env('KAFKA_BROKERS', 'kafka:9092'),

    /*
    |--------------------------------------------------------------------------
    | Kafka Consumer Group ID
    |--------------------------------------------------------------------------
    |
    | Each consumer group has a unique ID. Consumers in the same group share
    | the same message load. You can override this in your consumer classes.
    |
    */
    'consumer_group_id' => env('KAFKA_CONSUMER_GROUP_ID', 'jaramarket-consumer-group'),

    /*
    |--------------------------------------------------------------------------
    | Security Protocol
    |--------------------------------------------------------------------------
    |
    | Options: PLAINTEXT, SSL, SASL_PLAINTEXT, SASL_SSL
    |
    */
    'security_protocol' => env('KAFKA_SECURITY_PROTOCOL', 'PLAINTEXT'),

    /*
    |--------------------------------------------------------------------------
    | Kafka Authentication
    |--------------------------------------------------------------------------
    |
    | If you are using SASL or SSL, configure authentication settings here.
    | For PLAINTEXT (local dev), these are ignored.
    |
    */
    'sasl' => [
        'username' => env('KAFKA_SASL_USERNAME'),
        'password' => env('KAFKA_SASL_PASSWORD'),
        'mechanisms' => env('KAFKA_SASL_MECHANISMS', 'PLAIN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto Commit
    |--------------------------------------------------------------------------
    |
    | If true, Kafka will automatically commit offsets periodically.
    | If false, you’ll need to commit manually in your consumer logic.
    |
    */
    'auto_commit' => env('KAFKA_AUTO_COMMIT', true),

    /*
    |--------------------------------------------------------------------------
    | Kafka Topics
    |--------------------------------------------------------------------------
    |
    | Define all the topics used in your app here for convenience.
    |
    */
    'topics' => [
        'orders' => env('KAFKA_TOPIC_ORDERS', 'orders'),
        'wallet' => env('KAFKA_TOPIC_WALLET', 'wallet'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Consumer Options
    |--------------------------------------------------------------------------
    |
    | Low-level librdkafka options for fine-tuning.
    |
    */
    'consumer_options' => [
        'enable.auto.commit' => 'true',
        'auto.offset.reset' => 'earliest',
    ],

    /*
    |--------------------------------------------------------------------------
    | Producer Options
    |--------------------------------------------------------------------------
    |
    | Additional options for producers.
    |
    */
    'producer_options' => [
        'acks' => 'all',
        'retries' => 3,
    ],
];
