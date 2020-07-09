<?php declare(strict_types=1);

namespace Kiboko\Component\ETL\Satellite\ZMQ;

use Ramsey\Uuid\UuidInterface;

trait MessageTrait
{
    private UuidInterface $uuid;
    private \JsonSerializable $payload;

    public function getUuid(): string
    {
        return $this->uuid->toString();
    }

    public function getPayload(): \JsonSerializable
    {
        return $this->payload;
    }

    public function jsonSerialize()
    {
        return [
            'uuid' => $this->uuid,
            'payload' => $this->payload,
        ];
    }
}