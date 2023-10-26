<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud;

use Gyroscops\Api\Client;
use Kiboko\Component\Satellite\Cloud\DTO\PipelineId;

interface PipelineInterface
{
    public static function fromLegacyConfiguration(array $configuration): DTO\Pipeline;

    public static function fromApiWithId(Client $client, PipelineId $id, array $configuration): DTO\ReferencedPipeline;

    public static function fromApiWithCode(Client $client, string $code, array $configuration): DTO\ReferencedPipeline;

    public function create(DTO\SatelliteInterface&DTO\PipelineInterface $pipeline): DTO\CommandBatch;

    public function update(DTO\ReferencedPipeline $actual, DTO\SatelliteInterface&DTO\PipelineInterface $desired): DTO\CommandBatch;

    public function remove(DTO\PipelineId $id): DTO\CommandBatch;
}
