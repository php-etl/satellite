<?php

namespace unit\Kiboko\Component\Satellite\Cloud\CLient;

use Gyroscops\Api\Client;
use Gyroscops\Api\Exception\GetWorkflowItemNotFoundException;

class GetWorkflowItemThrowNotFoundException extends Client
{
    public function getWorkflowItem($id, string $fetch = self::FETCH_OBJECT, array $accept = [])
    {
        throw new GetWorkflowItemNotFoundException();
    }
}
