<?php declare(strict_types=1);

namespace Kiboko\Component\ETL\Satellite\ZMQ;

use Kiboko\Component\ETL\Promise\DeferredInterface;
use Kiboko\Component\ETL\Promise\Promise;
use Kiboko\Component\ETL\Promise\ResolvablePromiseInterface;
use Kiboko\Component\ETL\Satellite\MessageInterface;
use Kiboko\Component\ETL\Satellite\ProducerInterface;

final class Producer implements ProducerInterface
{
    private \ZMQSocket $producer;
    private \ZMQSocket $collector;
    private \ZMQSocket $command;
    /** @var array<string,ResolvablePromiseInterface>|ResolvablePromiseInterface[] */
    private array $pendingPromises;

    public function __construct(
        string $producerDSN = 'tcp://*:5557',
        string $collectorDSN = 'tcp://*:5558',
        string $commandDSN = 'tcp://*:5559'
    ) {
        $context = new \ZMQContext();

        try {
            $this->producer = new \ZMQSocket($context, \ZMQ::SOCKET_PUSH);
            $this->producer->bind($producerDSN);
        } catch (\ZMQSocketException $exception) {
            throw new \RuntimeException('Could not open producer socket.', 0, $exception);
        }

        try {
            $this->collector = new \ZMQSocket($context, \ZMQ::SOCKET_PULL);
            $this->collector->bind($collectorDSN);
        } catch (\ZMQSocketException $exception) {
            throw new \RuntimeException('Could not open collector socket.', 0, $exception);
        }

        try {
            $this->command = new \ZMQSocket($context, \ZMQ::SOCKET_PUB);
            $this->command->bind($commandDSN);
        } catch (\ZMQSocketException $exception) {
            throw new \RuntimeException('Could not open command socket.', 0, $exception);
        }

        $this->pendingPromises = [];
    }

    public function send(MessageInterface $message): DeferredInterface
    {
        $promise = new Promise();

        try {
            $this->producer->send(json_encode($message));
        } catch (\ZMQSocketException $exception) {
            $promise->fail($exception);
        }

        $this->pendingPromises[$message->getUuid()] = $promise;

        return $promise->defer();
    }

    public function publish(string $command): void
    {
        $this->command->send($command);
    }

    public function poll(): void
    {
        try {
            if (false === ($messages = $this->collector->recvMulti(\ZMQ::MODE_DONTWAIT))) {
                return;
            }

            foreach ($messages as $json) {
                $message = Producer\Response::fromJson($json);

                if (!isset($this->pendingPromises[$message->getUuid()])) {
                    file_put_contents('php://stderr', sprintf('Received an untracked message: %s', $message->getUuid()) . PHP_EOL);
                    continue;
                }

                $promise = $this->pendingPromises[$message->getUuid()];
                unset($this->pendingPromises[$message->getUuid()]);
                $promise->resolve($message->getPayload());
            }
        } catch (\ZMQSocketException $exception) {
            throw new \RuntimeException('Could not read from collector.', 0, $exception);
        }
    }
}