<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud;

use Gyroscops\Api\Client;
use Kiboko\Component\Satellite\Cloud\DTO\WorkflowId;

interface WorkflowInterface
{
    public static function fromLegacyConfiguration(array $configuration): DTO\Workflow;

    public static function fromApiWithId(Client $client, WorkflowId $id, array $configuration): DTO\ReferencedPipeline;

    public static function fromApiWithCode(Client $client, string $code, array $configuration): DTO\ReferencedPipeline;

    public function create(DTO\PipelineInterface $pipeline): DTO\CommandBatch;

    public function update(DTO\ReferencedPipeline $actual, DTO\PipelineInterface $desired): DTO\CommandBatch;

    public function remove(DTO\PipelineId $id): DTO\CommandBatch;
}
