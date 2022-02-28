<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud\Command\Pipeline\AddPipelineComposerPSR4AutoloadCommand;
use Kiboko\Component\Satellite\Cloud\Result;

final class AddPipelineComposerPSR4AutoloadCommandHandler
{
    public function __construct(private Api\Client $client)
    {}

    public function __invoke(AddPipelineComposerPSR4AutoloadCommand $command): Result
    {
        $response = $this->client->addComposerPipelinePipelineCollection(
            (new Api\Model\PipelineAddPipelineComposerPSR4AutoloadCommandInputJsonld())
                ->setId($command->pipeline)
                ->setNamespace($command->namespace)
                ->setPaths($command->paths)
        );

        if ($response !== null && $response->getStatusCode() !== 202) {
            throw new \RuntimeException($response->getReasonPhrase());
        }

        return new Result($response->getStatusCode(), $response->getBody()->getContents());
    }
}
