<?php declare(strict_types=1);

namespace Kiboko\Component\ETL\Satellite\ZMQ\Producer;

use Kiboko\Component\ETL\Satellite\MessageInterface;
use Kiboko\Component\ETL\Satellite\ZMQ\MessageTrait;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class Request implements MessageInterface
{
    use MessageTrait;

    public function __construct(\JsonSerializable $payload)
    {
        $this->uuid = Uuid::uuid4();
        $this->payload = $payload;
    }
}