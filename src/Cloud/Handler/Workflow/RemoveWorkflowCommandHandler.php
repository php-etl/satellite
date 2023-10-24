<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Workflow;

use Kiboko\Component\Satellite\Cloud;

final class RemoveWorkflowCommandHandler
{
    public function __construct(
        \Gyroscops\Api\Client $client,
    ) {
    }

    public function __invoke(Cloud\Command\Workflow\DeclareWorkflowCommand $command)
    {

    }
}
