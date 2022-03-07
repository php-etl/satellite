<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud;

use Gyroscops\Api\Client;
use Kiboko\Component\Satellite\Adapter\DTO;

final class Pipeline implements PipelineInterface
{
    public function __construct(
        private Client $client
    ) {}

    public function authenticate(\Kiboko\Component\Satellite\Cloud\DTO\CredentialsInterface $credentials): void
    {}

    public function fromConfiguration(array $configuration): \Kiboko\Component\Satellite\Cloud\DTO\Pipeline
    {
        return new \Kiboko\Component\Satellite\Cloud\DTO\Pipeline(
            $configuration['code'],
            $configuration['name'],
            new \Kiboko\Component\Satellite\Cloud\DTO\StepList(),
        );
    }

    public function create(\Kiboko\Component\Satellite\Cloud\DTO\Pipeline $pipeline): \Kiboko\Component\Satellite\Cloud\DTO\CommandBatch
    {
        return new \Kiboko\Component\Satellite\Cloud\DTO\CommandBatch();
    }

    public function update(\Kiboko\Component\Satellite\Cloud\DTO\PipelineId $id, \Kiboko\Component\Satellite\Cloud\DTO\Pipeline $pipeline): \Kiboko\Component\Satellite\Cloud\DTO\CommandBatch
    {
        return new \Kiboko\Component\Satellite\Cloud\DTO\CommandBatch();
    }

    public function remove(\Kiboko\Component\Satellite\Cloud\DTO\PipelineId $id): \Kiboko\Component\Satellite\Cloud\DTO\CommandBatch
    {
        return new \Kiboko\Component\Satellite\Cloud\DTO\CommandBatch();
    }
}
