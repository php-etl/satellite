<?php declare(strict_types=1);

namespace Kiboko\Component\ETL\Satellite\ZMQ\Consumer;

use Kiboko\Component\ETL\Satellite\MessageInterface;
use Kiboko\Component\ETL\Satellite\ZMQ\MessageTrait;
use Ramsey\Uuid\Uuid;

final class Response implements MessageInterface
{
    use MessageTrait;

    public function __construct(Request $request, \JsonSerializable $payload)
    {
        $this->uuid = Uuid::fromString($request->getUuid());
        $this->payload = $payload;
    }
}