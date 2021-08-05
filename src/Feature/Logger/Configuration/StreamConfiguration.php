<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\Logger\Configuration;

use Psr\Log\LogLevel;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class StreamConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('stream');

        /** @phpstan-ignore-next-line */
        $builder->getRootNode()
            ->children()
                ->scalarNode('path')->end()
                ->booleanNode('use_locking')
                    ->info('Try to lock log file before doing any writes')
                ->end()
                ->scalarNode('file_permissions')
                    ->info('Optional file permissions (default (0644) are only for owner read/write)')
                ->end()
                ->enumNode('level')
                    ->info('The minimum logging level at which this handler will be triggered')
                    ->values([
                        LogLevel::DEBUG,
                        LogLevel::INFO,
                        LogLevel::NOTICE,
                        LogLevel::WARNING,
                        LogLevel::ERROR,
                        LogLevel::CRITICAL,
                        LogLevel::ALERT,
                        LogLevel::EMERGENCY,
                    ])
                ->end()
            ->end();

        return $builder;
    }
}
