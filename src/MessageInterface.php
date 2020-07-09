<?php declare(strict_types=1);

namespace Kiboko\Component\ETL\Satellite;

interface MessageInterface extends \JsonSerializable
{
    public function getUuid(): string;
    public function getPayload(): \JsonSerializable;
}