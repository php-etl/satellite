<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Composer;

use Composer\Package\Package;
use Composer\Package\PackageInterface;

final class Accumulator implements \IteratorAggregate, \Stringable
{
    /** @var iterable|PackageInterface[] */
    private iterable $packages = [];

    public function __construct() {}

    public function append(PackageInterface ...$packages): self
    {
        array_push($this->packages, ...$packages);

        return $this;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->packages);
    }

    public function formatPluginInstance(): \Generator
    {
        /** @var Package $package */
        foreach ($this as $package) {
            $configuration = $package->getExtra();
            if (\array_key_exists('satellite', $configuration)) {
                // Enter fallback mode.
                if (!\array_key_exists('class', $configuration['satellite'])) {
                    continue;
                }

                yield <<<PHP
                    new \\{$configuration['satellite']['class']}(\$context->interpreter())
                    PHP;
                continue;
            }
            if (!\array_key_exists('gyroscops', $configuration)) {
                continue;
            }
            if (!\array_key_exists('plugins', $configuration['gyroscops'])) {
                continue;
            }

            if (\is_string($configuration['gyroscops']['plugins'])) {
                yield <<<PHP
                    new \\{$configuration['gyroscops']['plugins']}(\$context->interpreter())
                    PHP;
            } elseif (\is_array($configuration['gyroscops']['plugins'])) {
                foreach ($configuration['gyroscops']['plugins'] as $plugin) {
                    yield <<<PHP
                        new \\{$plugin}(\$context->interpreter())
                        PHP;
                }
            }
        }
    }

    public function formatAdapterInstance(): \Generator
    {
        /** @var Package $package */
        foreach ($this as $package) {
            $configuration = $package->getExtra();
            if (!\array_key_exists('gyroscops', $configuration)) {
                continue;
            }
            if (!\array_key_exists('adapters', $configuration['gyroscops'])) {
                continue;
            }

            if (\is_string($configuration['gyroscops']['adapters'])) {
                yield <<<PHP
                    new \\{$configuration['gyroscops']['adapters']}(\$context->workingDirectory())
                    PHP;
            } elseif (\is_array($configuration['gyroscops']['adapters'])) {
                foreach ($configuration['gyroscops']['adapters'] as $adapter) {
                    yield <<<PHP
                        new \\{$adapter}(\$context->workingDirectory())
                        PHP;
                }
            }
        }
    }

    public function formatRuntimeInstance(): \Generator
    {
        /** @var Package $package */
        foreach ($this as $package) {
            $configuration = $package->getExtra();
            if (!\array_key_exists('gyroscops', $configuration)) {
                continue;
            }
            if (!\array_key_exists('runtimes', $configuration['gyroscops'])) {
                continue;
            }

            if (\is_string($configuration['gyroscops']['runtimes'])) {
                yield <<<PHP
                    new \\{$configuration['gyroscops']['runtimes']}()
                    PHP;
            } elseif (\is_array($configuration['gyroscops']['runtimes'])) {
                foreach ($configuration['gyroscops']['runtimes'] as $runtime) {
                    yield <<<PHP
                        new \\{$runtime}()
                        PHP;
                }
            }
        }
    }

    public function formatActionInstance(): \Generator
    {
        /** @var Package $package */
        foreach ($this as $package) {
            $configuration = $package->getExtra();
            if (\array_key_exists('satellite', $configuration)) {
                // Enter fallback mode.
                if (!\array_key_exists('class', $configuration['satellite'])) {
                    continue;
                }

                yield <<<PHP
                    new \\{$configuration['satellite']['class']}(\$context->interpreter())
                    PHP;
                continue;
            }
            if (!\array_key_exists('gyroscops', $configuration)) {
                continue;
            }
            if (!\array_key_exists('actions', $configuration['gyroscops'])) {
                continue;
            }

            if (\is_string($configuration['gyroscops']['actions'])) {
                yield <<<PHP
                    new \\{$configuration['gyroscops']['actions']}(\$context->interpreter())
                    PHP;
            } elseif (\is_array($configuration['gyroscops']['actions'])) {
                foreach ($configuration['gyroscops']['actions'] as $plugin) {
                    yield <<<PHP
                        new \\{$plugin}(\$context->interpreter())
                        PHP;
                }
            }
        }
    }

    public function __toString()
    {
        return sprintf(
            <<<'PHP'
                <?php declare(strict_types=1);
                use \Kiboko\Component\Satellite\RuntimeContextInterface;
                return fn (RuntimeContextInterface $context) => (new \Kiboko\Component\Satellite\Service())
                    ->registerPlugins(
                        %s
                    )
                    ->registerAdapters(
                        %s
                    )
                    ->registerRuntimes(
                        %s
                    )
                    ->registerActions(
                        %s
                    );
                PHP,
            implode(",\n".str_pad('', 8), iterator_to_array($this->formatPluginInstance())),
            implode(",\n".str_pad('', 8), iterator_to_array($this->formatAdapterInstance())),
            implode(",\n".str_pad('', 8), iterator_to_array($this->formatRuntimeInstance())),
            implode(",\n".str_pad('', 8), iterator_to_array($this->formatActionInstance())),
        );
    }
}
