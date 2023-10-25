<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Workflow;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud;

final readonly class RemoveWorkflowCommandHandler
{
    public function __construct(
        private \Gyroscops\Api\Client $client,
    ) {
    }

    public function __invoke(Cloud\Command\Workflow\RemoveWorkflowCommand $command): Cloud\Event\Workflow\WorkflowRemoved
    {
        try {
            /** @var Api\Model\WorkflowRemoveWorkflowCommandJsonldRead $result */
            $result = $this->client->softDeleteWorkflowItem(
                $command->id->asString()
            );
        } catch (Api\Exception\SoftDeleteWorkflowItemBadRequestException $exception) {
            throw new Cloud\RemoveWorkflowFailedException('Something went wrong while removing the workflow. Maybe your client is not up to date, you may want to update your Gyroscops client.', previous: $exception);
        } catch (Api\Exception\SoftDeleteWorkflowItemUnprocessableEntityException $exception) {
            throw new Cloud\RemoveWorkflowFailedException('Something went wrong while removing the workflow. It seems the data you sent was invalid, please check your input.', previous: $exception);
        } catch (Api\Exception\SoftDeleteWorkflowItemNotFoundException $exception) {
            throw new Cloud\RemoveWorkflowFailedException('Something went wrong while removing the workflow. It seems the data you want to delete do not exists.', previous: $exception);
        }

        return new Cloud\Event\Workflow\WorkflowRemoved($result->getId());
    }
}
