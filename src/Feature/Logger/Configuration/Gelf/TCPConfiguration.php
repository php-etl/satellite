<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\Logger\Configuration\Gelf;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class TCPConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('tcp');

        /* @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->children()
            ->scalarNode('host')
            ->info('The host to connect to.')
            ->end()
            ->scalarNode('port')
            ->info('Port on the host.')
            ->end()
            ->end()
        ;

        return $builder;
    }
}
