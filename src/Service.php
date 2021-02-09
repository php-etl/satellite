<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite;

use Kiboko\Component\Satellite;
use Kiboko\Contract\Configurator;
use Kiboko\Plugin\API;
use Kiboko\Plugin\CSV;
use Kiboko\Plugin\Akeneo;
use Kiboko\Plugin\Sylius;
use Kiboko\Plugin\FastMap;
use Kiboko\Component\Satellite\Plugin\Custom;
use Kiboko\Component\Satellite\Plugin\Log;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Symfony\Component\Config\Definition\Processor;

final class Service implements Configurator\FactoryInterface
{
    private Processor $processor;
    private ConfigurationInterface $configuration;

    public function __construct()
    {
        $this->processor = new Processor();
        $this->configuration = (new Configuration())
            ->addAdapters(
                new Adapter\Docker\Configuration(),
                new Adapter\Filesystem\Configuration(),
            )
            ->addRuntimes(
                new Runtime\Api\Configuration(),
                new Runtime\HttpHook\Configuration(),
                new Runtime\Pipeline\Configuration(),
            );
    }

    public function configuration(): ConfigurationInterface
    {
        return $this->configuration;
    }

    /**
     * @throws Configurator\ConfigurationExceptionInterface
     */
    public function normalize(array $config): array
    {
        try {
            return $this->processor->processConfiguration($this->configuration, $config);
        } catch (Symfony\InvalidTypeException|Symfony\InvalidConfigurationException $exception) {
            throw new Configurator\InvalidConfigurationException($exception->getMessage(), 0, $exception);
        }
    }

    public function validate(array $config): bool
    {
        try {
            $this->processor->processConfiguration($this->configuration, $config);

            return true;
        } catch (Symfony\InvalidTypeException|Symfony\InvalidConfigurationException $exception) {
            return false;
        }
    }

    /**
     * @throws Configurator\ConfigurationExceptionInterface
     */
    public function compile(array $config): Configurator\RepositoryInterface
    {
        if (array_key_exists('pipeline', $config)) {
            return $this->compilePipeline($config);
        } else if (array_key_exists('http_hook', $config)) {
            return $this->compileHook($config);
        } else if (array_key_exists('http_api', $config)) {
            return $this->compileApi($config);
        }

        throw new \LogicException('Not implemented');
    }

    public function compilePipeline(array $config): Satellite\Builder\Repository\Pipeline
    {
        $pipeline = new Satellite\Builder\Pipeline();

        foreach ($config['pipeline']['steps'] as $step) {
            if (array_key_exists('akeneo', $step)) {
                $repository = (new Akeneo\Service())->compile($step['akeneo']);
                if (array_key_exists('extractor', $step['akeneo'])) {
                    $pipeline->addExtractor($repository->getBuilder()->getNode());
                } elseif (array_key_exists('loader', $step['akeneo'])) {
                    $pipeline->addLoader($repository->getBuilder()->getNode());
                }
            } elseif (array_key_exists('sylius', $step)) {
                $repository = (new Sylius\Service())->compile($step['sylius']);
                if (array_key_exists('extractor', $step['sylius'])) {
                    $pipeline->addExtractor($repository->getBuilder()->getNode());
                } elseif (array_key_exists('loader', $step['sylius'])) {
                    $pipeline->addLoader($repository->getBuilder()->getNode());
                }
            } elseif (array_key_exists('csv', $step)) {
                $repository = (new CSV\Service())->compile($step['csv']);
                if (array_key_exists('extractor', $step['csv'])) {
                    $pipeline->addExtractor($repository->getBuilder()->getNode());
                } elseif (array_key_exists('loader', $step['csv'])) {
                    $pipeline->addLoader($repository->getBuilder()->getNode());
                }
            } elseif (array_key_exists('api', $step)) {
                $repository = (new API\Service())->compile($step['api']);
                if (array_key_exists('extractor', $step['api'])) {
                    $pipeline->addExtractor($repository->getBuilder()->getNode());
                } elseif (array_key_exists('loader', $step['api'])) {
                    $pipeline->addLoader($repository->getBuilder()->getNode());
                }
//            } elseif (array_key_exists('custom', $step)) {
//                $repository = (new Satellite\Plugin\Custom\Service())->compile($step['custom']);
//                if (array_key_exists('extractor', $step['custom'])) {
//                    $pipeline->addExtractor($repository->getBuilder()->getNode());
//                } elseif (array_key_exists('transformer', $step['custom'])) {
//                    $pipeline->addTransformer($repository->getBuilder()->getNode());
//                } elseif (array_key_exists('loader', $step['custom'])) {
//                    $pipeline->addLoader($repository->getBuilder()->getNode());
//                }
//            } elseif (array_key_exists('log', $step)) {
//                $repository = (new Satellite\Plugin\Log\Service())->compile($step['log']);
//                if (array_key_exists('loader', $step['log'])) {
//                    $pipeline->addLoader($repository->getBuilder()->getNode());
//                }
            } elseif (array_key_exists('stream', $step)) {
                $repository = (new Satellite\Plugin\Stream\Service())->compile($step['stream']);
                if (array_key_exists('loader', $step['stream'])) {
                    $pipeline->addLoader($repository->getBuilder()->getNode());
                }
            } elseif (array_key_exists('fastmap', $step)) {
                $repository = (new FastMap\Service())->compile($step['fastmap']);
                $pipeline->addTransformer($repository->getBuilder()->getNode());
            }
        }

        return new Satellite\Builder\Repository\Pipeline($pipeline);
    }

    private function compileApi(array $config): Satellite\Builder\Repository\API
    {
        $pipeline = new Satellite\Builder\API();

        return new Satellite\Builder\Repository\API($pipeline);
    }

    private function compileHook(array $config): Satellite\Builder\Repository\Hook
    {
        $pipeline = new Satellite\Builder\Hook();

        return new Satellite\Builder\Repository\Hook($pipeline);
    }
}
