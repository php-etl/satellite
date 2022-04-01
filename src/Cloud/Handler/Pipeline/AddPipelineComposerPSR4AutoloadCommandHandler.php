<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud;

final class AddPipelineComposerPSR4AutoloadCommandHandler
{
    public function __construct(
        private Api\Client $client
    ) {}

    public function __invoke(Cloud\Command\Pipeline\AddPipelineComposerPSR4AutoloadCommand $command): Cloud\Event\AddedPipelineComposerPSR4Autoload
    {
        $result = $this->client->addComposerPipelinePipelineCollection(
            (new Api\Model\PipelineAddPipelineComposerPSR4AutoloadCommandInput())
                ->setPipeline((string) $command->pipeline)
                ->setNamespace($command->namespace)
                ->setPaths($command->paths),
        );

        if ($result === null) {
            throw throw new \RuntimeException('Something went wrong wile adding composer PSR4 into the pipeline.');
        }

        return new Cloud\Event\AddedPipelineComposerPSR4Autoload($result->id, $result->namespace, $result->paths);
    }
}
