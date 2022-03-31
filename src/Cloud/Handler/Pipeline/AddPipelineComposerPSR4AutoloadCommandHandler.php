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
        $response = $this->client->addComposerPipelinePipelineCollection(
            (new Api\Model\PipelineAddPipelineComposerPSR4AutoloadCommandInput())
                ->setPipeline((string) $command->pipeline)
                ->setNamespace($command->namespace)
                ->setPaths($command->paths),
            Api\Client::FETCH_RESPONSE
        );

        if ($response !== null && $response->getStatusCode() !== 202) {
            throw throw new \RuntimeException($response->getReasonPhrase());
        }

        $result = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return new Cloud\Event\AddedPipelineComposerPSR4Autoload($result["id"], $result["namespace"], $result["paths"]);
    }
}
