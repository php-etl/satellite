<?php

return (new \Monolog\Logger())
    ->setHandlers([
        new \Monolog\Handler\StreamHandler(
            stream: 'php://stderr',
            level: 'warning',
            filePermission: 0644,
            useLocking: true
        ),
        new \Monolog\Handler\SyslogHandler(
            ident: 'conmon',
            facility: 40,
            level: 'warning',
            logopts: 48
        ),
        (new \Monolog\Handler\GelfHandler(
            publisher: new \Gelf\Publisher(
                transport: new \Gelf\Transport\TcpTransport(host: 'amqp.example.com', port: 4000)
            ),
            level: 'warning'
        ))->setFormatter(new \Monolog\Formatter\LogstashFormatter(applicationName: 'pipeline')),
        new \Monolog\Handler\ElasticSearchHandler(
            client: \Elasticsearch\ClientBuilder::create()->setHosts([0 => [0 => 'elasticsearch.example.com:9200']])->build(),
            level: 'warning'
        )
    ])
    ->pushProcessor(new \Monolog\Processor\PsrLogMessageProcessor())
    ->pushProcessor(new \Monolog\Processor\MemoryUsageProcessor());
