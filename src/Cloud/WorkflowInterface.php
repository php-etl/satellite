<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud;

use Gyroscops\Api\Client;

interface WorkflowInterface
{
    public static function fromLegacyConfiguration(array $configuration): DTO\Workflow;

    public static function fromApiWithId(Client $client, DTO\WorkflowId $id): DTO\ReferencedWorkflow;

    public static function fromApiWithCode(Client $client, string $code): DTO\ReferencedWorkflow;

    public function create(DTO\WorkflowInterface $workflow): DTO\CommandBatch;

    public function update(DTO\ReferencedWorkflow $actual, DTO\WorkflowInterface $desired): DTO\CommandBatch;

    public function remove(DTO\WorkflowId $id): DTO\CommandBatch;
}
