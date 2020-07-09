<?php declare(strict_types=1);

namespace Kiboko\Component\ETL\Satellite\ZMQ;

use Kiboko\Component\ETL\Satellite\ConsumerInterface;
use Kiboko\Component\ETL\Satellite\MessageInterface;
use Ramsey\Uuid\Uuid;

final class Consumer implements ConsumerInterface
{
    private \ZMQSocket $receiver;
    private \ZMQSocket $sender;
    private \ZMQSocket $command;

    public function __construct(
        string $receiverDSN = 'tcp://localhost:5557',
        string $senderDSN = 'tcp://localhost:5558',
        string $commandDSN = 'tcp://localhost:5559'
    ) {
        $context = new \ZMQContext();

        try {
            $this->receiver = new \ZMQSocket($context, \ZMQ::SOCKET_PULL);
            $this->receiver->connect($receiverDSN);
        } catch (\ZMQSocketException $exception) {
            throw new \RuntimeException('Could not open receiver socket.', 0, $exception);
        }

        try {
            $this->sender = new \ZMQSocket($context, \ZMQ::SOCKET_PUSH);
            $this->sender->connect($senderDSN);
        } catch (\ZMQSocketException $exception) {
            throw new \RuntimeException('Could not open sender socket.', 0, $exception);
        }

        try {
            $this->command = new \ZMQSocket($context, \ZMQ::SOCKET_SUB);
            $this->command->connect($commandDSN);
        } catch (\ZMQSocketException $exception) {
            throw new \RuntimeException('Could not open command socket.', 0, $exception);
        }
    }

    public function poll(): iterable
    {
        yield from $this->command->recvMulti(\ZMQ::MODE_DONTWAIT);
    }

    public function receive(): MessageInterface
    {
        try {
            return Consumer\Request::fromJson($this->receiver->recv());
        } catch (\ZMQSocketException $exception) {
            throw new \RuntimeException('Could not receive message.', 0, $exception);
        }
    }

    public function send(MessageInterface $response): void
    {
        try {
            $this->sender->send(json_encode($response), \ZMQ::MODE_DONTWAIT);
        } catch (\ZMQSocketException $exception) {
            throw new \RuntimeException('Could not send message.', 0, $exception);
        }
    }
}