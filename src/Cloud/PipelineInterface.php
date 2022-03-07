<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud;

use Kiboko\Component\Satellite\Adapter\DTO;

interface PipelineInterface
{
    public function authenticate(\Kiboko\Component\Satellite\Cloud\DTO\CredentialsInterface $credentials): void;

    public function fromConfiguration(array $configuration): \Kiboko\Component\Satellite\Cloud\DTO\Pipeline;

    public function create(\Kiboko\Component\Satellite\Cloud\DTO\Pipeline $pipeline): \Kiboko\Component\Satellite\Cloud\DTO\CommandBatch;

    public function update(\Kiboko\Component\Satellite\Cloud\DTO\PipelineId $id, \Kiboko\Component\Satellite\Cloud\DTO\Pipeline $pipeline): \Kiboko\Component\Satellite\Cloud\DTO\CommandBatch;

    public function remove(\Kiboko\Component\Satellite\Cloud\DTO\PipelineId $id): \Kiboko\Component\Satellite\Cloud\DTO\CommandBatch;
}
