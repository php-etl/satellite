<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite;

use Kiboko\Component\Satellite;
use Kiboko\Contract\Configurator;
use Kiboko\Plugin\CSV;
use Kiboko\Plugin\Akeneo;
use Kiboko\Plugin\Sylius;
use Kiboko\Plugin\FastMap;
use Kiboko\Plugin\Spreadsheet;
use Kiboko\Plugin\SQL;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Kiboko\Component\SatelliteToolbox;

final class Service
{
    private Processor $processor;
    /**
     * @var array<ConfigurationInterface>
     */
    private array $configurations;

    public function __construct()
    {
        $this->processor = new Processor();
        $this->configurations = [
            new SatelliteToolbox\Configuration\ImportConfiguration(),
            (new Configuration())
            ->addAdapters(
                new Adapter\Docker\Configuration(),
                new Adapter\Filesystem\Configuration(),
            )
            ->addRuntimes(
                new Runtime\Api\Configuration(),
                new Runtime\HttpHook\Configuration(),
                new Runtime\Pipeline\Configuration(),
                new Runtime\Workflow\Configuration(),
            ),
        ];
    }

    /**
     * @throws Configurator\ConfigurationExceptionInterface
     */
    public function normalize(array $configs): array
    {
        try {
            $configImports = ['imports' => $configs['imports']];
            $configSatellite = ['satellite' => $configs['satellite']];

           foreach ($this->configurations as $configuration) {
               if ($configuration instanceof SatelliteToolbox\Configuration\ImportConfiguration && $configs['imports']) {
                   $config["imports"] = $this->processor->processConfiguration($configuration, $configImports);
               } elseif ($configuration instanceof Configuration && $configs['satellite']) {
                   $config["satellite"] = $this->processor->processConfiguration($configuration, $configSatellite);
               }
           }

           return $config;
        } catch (Symfony\InvalidTypeException | Symfony\InvalidConfigurationException $exception) {
            throw new Configurator\InvalidConfigurationException($exception->getMessage(), 0, $exception);
        }
    }

    public function validate(array $configs): bool
    {
        try {
            $configImports = ['imports' => $configs['imports']];
            $configSatellite = ['satellite' => $configs['satellite']];

            foreach ($this->configurations as $configuration) {
                if ($configuration instanceof SatelliteToolbox\Configuration\ImportConfiguration) {
                    $this->processor->processConfiguration($configuration, $configImports);
                } else {
                    $this->processor->processConfiguration($configuration, $configSatellite);
                }
            }

            return true;
        } catch (Symfony\InvalidTypeException | Symfony\InvalidConfigurationException $exception) {
            return false;
        }
    }

    /**
     * @throws Configurator\ConfigurationExceptionInterface
     */
    public function compile(array $config): Configurator\RepositoryInterface
    {
        if (array_key_exists('imports', $config)) {
            return $this->compileImports($config);
        } elseif (array_key_exists('satellite', $config)) {
            if (array_key_exists('imports', $config["satellite"])) {
                return $this->compileImports($config);
            } elseif (array_key_exists('workflow', $config["satellite"])) {
                return $this->compileWorkflow($config);
            } elseif (array_key_exists('pipeline', $config["satellite"])) {
                return $this->compilePipeline($config);
            } elseif (array_key_exists('http_hook', $config["satellite"])) {
                return $this->compileHook($config);
            } elseif (array_key_exists('http_api', $config["satellite"])) {
                return $this->compileApi($config);
            }
        }

        throw new \LogicException('Not implemented');
    }

    private function compileWorkflow(array $config): Satellite\Builder\Repository\Workflow
    {
        $workflow = new Satellite\Builder\Workflow();
        $repository = new Satellite\Builder\Repository\Workflow($workflow);

        if (array_key_exists('imports', $config["workflow"])) {
            foreach ($config['workflow']['imports'] as $imports) {
                foreach ($imports as $import) {
                    $fileLocator = new FileLocator();
                    $loaderResolver = new LoaderResolver([
                        new Satellite\Console\Config\YamlFileLoader($fileLocator),
                        new Satellite\Console\Config\JsonFileLoader($fileLocator)
                    ]);
                    $pipeline = $this->compilePipeline($import(new DelegatingLoader($loaderResolver)));

                    $repository->merge($pipeline);
                    $workflow->addJob($pipeline->getBuilder());
                }
            }
        }

        foreach ($config['workflow']['jobs'] as $job) {
            if (array_key_exists('pipeline', $job)) {
                $pipeline = $this->compilePipeline($job);

                $repository->merge($pipeline);
                $workflow->addJob($pipeline->getBuilder());
            } else {
                throw new \LogicException('Not implemented');
            }
        }

        return $repository;
    }

    private function compilePipeline(array $config): Satellite\Builder\Repository\Pipeline
    {
        $pipeline = new Satellite\Builder\Pipeline();
        $repository = new Satellite\Builder\Repository\Pipeline($pipeline);

        $interpreter = new Satellite\ExpressionLanguage\ExpressionLanguage();

        $repository->addPackages(
            'php-etl/pipeline:^0.3.0',
            'monolog/monolog',
            'symfony/dependency-injection:^5.2',
        );

        if (array_key_exists('expression_language', $config['satellite']['pipeline'])
            && is_array($config['satellite']['pipeline']['expression_language'])
            && count($config['satellite']['pipeline']['expression_language'])
        ) {
            foreach ($config['pipeline']['expression_language'] as $provider) {
                $interpreter->registerProvider(new $provider);
            }
        }

        foreach ($config['satellite']['pipeline']['steps'] as $step) {
            if (array_key_exists('akeneo', $step)) {
                (new Satellite\Pipeline\ConfigurationApplier('akeneo', new Akeneo\Service(clone $interpreter)))
                    ->withPackages(
                        'akeneo/api-php-client-ee',
                        'laminas/laminas-diactoros',
                        'php-http/guzzle7-adapter',
                    )
                    ->withExtractor()
                    ->withTransformer('lookup')
                    ->withLoader()
                    ->appendTo($step, $repository);
            } elseif (array_key_exists('sylius', $step)) {
                (new Satellite\Pipeline\ConfigurationApplier('sylius', new Sylius\Service(clone $interpreter)))
                    ->withPackages(
                        'diglin/sylius-api-php-client',
                        'laminas/laminas-diactoros',
                        'php-http/guzzle7-adapter',
                    )
                    ->withExtractor()
                    ->withLoader()
                    ->appendTo($step, $repository);
            } elseif (array_key_exists('csv', $step)) {
                (new Satellite\Pipeline\ConfigurationApplier('csv', new CSV\Service(clone $interpreter)))
                    ->withPackages(
                        'php-etl/csv-flow:^0.2.0',
                    )
                    ->withExtractor()
                    ->withLoader()
                    ->appendTo($step, $repository);
            } elseif (array_key_exists('spreadsheet', $step)) {
                (new Satellite\Pipeline\ConfigurationApplier('spreadsheet', new Spreadsheet\Service(clone $interpreter)))
                    ->withExtractor()
                    ->withLoader()
                    ->appendTo($step, $repository);
            } elseif (array_key_exists('custom', $step)) {
                (new Satellite\Pipeline\ConfigurationApplier('custom', new Satellite\Plugin\Custom\Service(clone $interpreter)))
                    ->withExtractor()
                    ->withTransformer()
                    ->withLoader()
                    ->appendTo($step, $repository);
            } elseif (array_key_exists('stream', $step)) {
                (new Satellite\Pipeline\ConfigurationApplier('stream', new Satellite\Plugin\Stream\Service(clone $interpreter)))
                    ->withLoader()
                    ->appendTo($step, $repository);
            } elseif (array_key_exists('batch', $step)) {
                (new Satellite\Pipeline\ConfigurationApplier('batch', new Satellite\Plugin\Batching\Service(clone $interpreter)))
                    ->withTransformer('merge')
                    ->withTransformer('fork')
                    ->appendTo($step, $repository);
            } elseif (array_key_exists('fastmap', $step)) {
                (new Satellite\Pipeline\ConfigurationApplier('fastmap', new FastMap\Service(clone $interpreter)))
                    ->withPackages(
                        'php-etl/fast-map:^0.2.0',
                    )
                    ->withTransformer(null)
                    ->appendTo($step, $repository);
            } elseif (array_key_exists('sftp', $step)) {
                (new Satellite\Pipeline\ConfigurationApplier('sftp', new Satellite\Plugin\SFTP\Service(clone $interpreter)))
                    ->withPackages(
                        'ext-ssh2',
                    )
                    ->withLoader()
                    ->appendTo($step, $repository);
            } elseif (array_key_exists('ftp', $step)) {
                (new Satellite\Pipeline\ConfigurationApplier('ftp', new Satellite\Plugin\FTP\Service(clone $interpreter)))
                    ->withPackages(
                        'ext-ssh2',
                    )
                    ->withLoader()
                    ->appendTo($step, $repository);
            } elseif (array_key_exists('sql', $step)) {
                (new Satellite\Pipeline\ConfigurationApplier('sql', new SQL\Service(clone $interpreter)))
                    ->withExtractor()
                    ->withTransformer('lookup')
                    ->withLoader()
                    ->appendTo($step, $repository);
            }
        }

        return $repository;
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

    private function compileImports(array $config): Builder\Repository\Pipeline|Builder\Repository\Workflow
    {
        if (count($config['imports']) > 0) {
            foreach ($config['imports'] as $imports) {
                foreach ($imports as $import) {
                    $fileLocator = new FileLocator();
                    $loaderResolver = new LoaderResolver([
                        new Satellite\Console\Config\YamlFileLoader($fileLocator),
                        new Satellite\Console\Config\JsonFileLoader($fileLocator)
                    ]);

                    $fileConfig = $import(new DelegatingLoader($loaderResolver));

                    if (array_key_exists('satellite', $fileConfig)) {
                        if (array_key_exists('pipeline', $fileConfig['satellite'])) {
                            return $this->compilePipeline($import(new DelegatingLoader($loaderResolver)));
                        } elseif (array_key_exists('workflow', $fileConfig['satellite'])) {
                            return $this->compileWorkflow($import(new DelegatingLoader($loaderResolver)));
                        } else {
                            throw new Symfony\InvalidConfigurationException('Please, check your imported configuration files.');
                        }
                    }
//
//                    if (array_key_exists('pipeline', $fileConfig)) {
//                        return $this->compilePipeline($import(new DelegatingLoader($loaderResolver)));
//                    } elseif (array_key_exists('workflow', $fileConfig)) {
//                        return $this->compileWorkflow($import(new DelegatingLoader($loaderResolver)));
//                    } else {
//                        throw new Symfony\InvalidConfigurationException('Please, check your imported configuration files.');
//                    }
                }
            }
        }
    }

}
