<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\DependencyInjection\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class ParametersConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('parameters');

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->arrayPrototype()
                ->variablePrototype()->end()
            ->end();

        return $builder;
    }
}
