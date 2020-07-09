<?php declare(strict_types=1);

namespace Kiboko\Component\ETL\Satellite;

use Kiboko\Component\ETL\Promise\DeferredInterface;

interface ProducerInterface
{
    public function send(MessageInterface $message): DeferredInterface;
}