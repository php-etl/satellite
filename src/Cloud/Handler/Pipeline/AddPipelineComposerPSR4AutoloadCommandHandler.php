<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud;

final readonly class AddPipelineComposerPSR4AutoloadCommandHandler
{
    public function __construct(
        private Api\Client $client
    ) {
    }

    public function __invoke(Cloud\Command\Pipeline\AddPipelineComposerPSR4AutoloadCommand $command): Cloud\Event\AddedPipelineComposerPSR4Autoload
    {
        try {
            $result = $this->client->addComposerAutoloadPipelinePipelineItem(
                $command->pipeline->asString(),
                (new Api\Model\PipelineAddPipelineComposerPSR4AutoloadCommandInput())
                    ->setNamespace($command->namespace)
                    ->setPaths($command->paths),
            );
        } catch (Api\Exception\AddComposerAutoloadPipelinePipelineItemBadRequestException $exception) {
            throw new Cloud\AddPipelineComposerPSR4AutoloadFailedException('Something went wrong while trying to add PSR4 autoloads into the pipeline. Maybe your client is not up to date, you may want to update your Gyroscops client.', previous: $exception);
        } catch (Api\Exception\AddComposerAutoloadPipelinePipelineItemUnprocessableEntityException $exception) {
            throw new Cloud\AddPipelineComposerPSR4AutoloadFailedException('Something went wrong while trying to add PSR4 autoloads into the pipeline. It seems the data you sent was invalid, please check your input.', previous: $exception);
        }

        $id = $result instanceof Api\Model\PipelineAddPipelineComposerPSR4AutoloadCommandJsonldRead
            ? ($result->getId() ?? '')
            : '';

        return new Cloud\Event\AddedPipelineComposerPSR4Autoload($id, $command->namespace, $command->paths);
    }
}
