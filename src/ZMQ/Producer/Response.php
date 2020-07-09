<?php declare(strict_types=1);

namespace Kiboko\Component\ETL\Satellite\ZMQ\Producer;

use Kiboko\Component\ETL\Satellite\MessageInterface;
use Kiboko\Component\ETL\Satellite\ZMQ\MessageTrait;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class Response implements MessageInterface
{
    use MessageTrait;

    public function __construct(UuidInterface $uuid, \JsonSerializable $payload)
    {
        $this->uuid = $uuid;
        $this->payload = $payload;
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);
        return new self(Uuid::fromString($data['uuid']), new class($data['payload']) implements \JsonSerializable {
            public array $payload;

            public function __construct(array $payload)
            {
                $this->payload = $payload;
            }

            public function jsonSerialize()
            {
                return $this->payload;
            }
        });
    }
}