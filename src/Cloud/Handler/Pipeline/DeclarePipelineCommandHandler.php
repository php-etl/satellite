<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud;

final class DeclarePipelineCommandHandler
{
    public function __construct(
        private Api\Client $client
    ) {}

    public function __invoke(Cloud\Command\Pipeline\DeclarePipelineCommand $command): Cloud\Event\PipelineDeclared
    {
        $result = $this->client->declarePipelinePipelineCollection(
            (new Api\Model\PipelineDeclarePipelineCommandInput())
                ->setLabel($command->label)
                ->setCode($command->code)
                ->setProject((string) $command->project)
        );

        assert($result !== null);

        return new Cloud\Event\PipelineDeclared($result->id);
    }
}