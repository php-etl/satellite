<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud;

final class Result
{
    public function __construct(private int $statusCode, private string $body)
    {
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function toArray(): array
    {
        return json_decode($this->body, true, 512, JSON_THROW_ON_ERROR);
    }
}
