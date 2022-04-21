<?php

declare(strict_types=1);

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
        try {
            /** @var \stdClass $result */
            $result = $this->client->addComposerPipelinePipelineCollection(
                (new Api\Model\PipelineAddPipelineComposerPSR4AutoloadCommandInput())
                    ->setPipeline((string)$command->pipeline)
                    ->setNamespace($command->namespace)
                    ->setPaths($command->paths),
            );
        } catch (Api\Exception\AddComposerPipelinePipelineCollectionBadRequestException $exception) {
            throw new Cloud\AddPipelineComposerPSR4AutoloadFailedException(
                'Something went wrong while trying to add PSR4 autoloads into the pipeline. Maybe your client is not up to date, you may want to update your Gyroscops client.',
                previous: $exception
            );
        } catch (Api\Exception\AddComposerPipelinePipelineCollectionUnprocessableEntityException $exception) {
            throw new Cloud\AddPipelineComposerPSR4AutoloadFailedException(
                'Something went wrong while trying to add PSR4 autoloads into the pipeline. It seems the data you sent was invalid, please check your input.',
                previous: $exception
            );
        }

        if ($result === null) {
            // TODO: change the exception message, it doesn't give enough details on how to fix the issue
            throw new Cloud\AddPipelineComposerPSR4AutoloadFailedException('Something went wrong while trying to add PSR4 autoloads into the pipeline.');
        }

        return new Cloud\Event\AddedPipelineComposerPSR4Autoload($result->id, $result->namespace, $result->paths);
    }
}
