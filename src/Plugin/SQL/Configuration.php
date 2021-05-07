<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\SQL;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $extractor = new Configuration\Extractor();
        $loader = new Configuration\Loader();

        $builder = new TreeBuilder('sql');

        /** @phpstan-ignore-next-line */
        $builder->getRootNode();

        return $builder;
    }
}
