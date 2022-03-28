<?php declare(strict_types=1);

namespace App\Pipeline;

use Bunny\Client;
use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Contract\Pipeline\LoaderInterface;

final class ConfigurableLoader implements LoaderInterface
{
    public function load(): \Generator
    {
        $client = new Client([
            'host'      => getenv('RABBITMQ_HOST'),
            'vhost'     => getenv('RABBITMQ_VHOST'),
            'user'      => getenv('RABBITMQ_USER'),
            'password'  => getenv('RABBITMQ_PASSWORD'),
            'port'      => getenv('RABBITMQ_PORT'),
        ]);

        $client->connect();

        $channel = $client->channel();

        $channel->exchangeDeclare(
            'akeneo',
            'topic',
            arguments: [
                'alternate-exchange' => 'unroutable',
                'x-dead-letter-exchange' => 'dl',
            ]

        );
        $channel->queueDeclare(
            'products',
            arguments: [
                'x-dead-letter-exchange' => 'dl',
                'x-dead-letter-routing-key' => 'products'
            ],
        );
        $channel->queueBind(
            'products',
            'akeneo',
            'products',
        );

        $line = yield;
        do {
            $channel->publish(
                \json_encode($line),
                [],
                'akeneo',
                'products'
            );
        } while ($line = yield new AcceptanceResultBucket($line));
    }
}
