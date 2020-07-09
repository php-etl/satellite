<?php declare(strict_types=1);

namespace Kiboko\Component\ETL\Satellite;

interface ConsumerInterface
{
    public function receive(): MessageInterface;
    public function send(MessageInterface $message): void;
}