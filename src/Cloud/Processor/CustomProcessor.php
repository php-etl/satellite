<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Processor;

use Symfony\Component\Config\Definition\NodeInterface;
use Symfony\Component\Config\Definition\Processor;

class CustomProcessor extends Processor
{
    public function process(NodeInterface $configTree, array $configs): array
    {
        return $configTree->finalize(array_merge(...$configs));
    }
}
