<?php

namespace unit\Kiboko\Component\Satellite\Cloud\CLient;

use Gyroscops;
use Gyroscops\Api\Model\WorkflowRead;

class GetWorkflowItem extends Gyroscops\Api\Client
{
    public function getWorkflowItem(string $id, string $fetch = self::FETCH_OBJECT, array $accept = []): WorkflowRead
    {
        $workflowRead = new WorkflowRead();
        $workflowRead->setId($id);
        $workflowRead->setLabel('Extract data from Akeneo');
        $workflowRead->setCode('from_akeneo');

        return $workflowRead;
    }
}
