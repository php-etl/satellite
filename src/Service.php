<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite;

use Kiboko\Component\Packaging;
use Kiboko\Component\Satellite;
use Kiboko\Contract\Configurator;
use Kiboko\Plugin\CSV;
use Kiboko\Plugin\Akeneo;
use Kiboko\Plugin\Sylius;
use Kiboko\Plugin\FastMap;
use Kiboko\Plugin\Spreadsheet;
use Kiboko\Plugin\SQL;
use PhpParser\Node;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Symfony\Component\Config\Definition\Processor;
use Kiboko\Component\SatelliteToolbox;

final class Service implements Configurator\FactoryInterface
{
    private Processor $processor;
    private ConfigurationInterface $configuration;

    public function __construct()
    {
        $this->processor = new Processor();

        $this->configuration = (new Satellite\Configuration())
            ->addAdapters(
                new Adapter\Docker\Configuration(),
                new Adapter\Filesystem\Configuration(),
//                new Adapter\Serverless\Configuration(),
            )
            ->addRuntimes(
                new Runtime\Api\Configuration(),
                new Runtime\HttpHook\Configuration(),
                new Runtime\Pipeline\Configuration(),
                new Runtime\Workflow\Configuration(),
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
        } catch (Symfony\InvalidTypeException | Symfony\InvalidConfigurationException $exception) {
            throw new Configurator\InvalidConfigurationException($exception->getMessage(), 0, $exception);
        }
    }

    public function validate(array $config): bool
    {
        try {
            $this->processor->processConfiguration($this->configuration, $config);

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
        if (array_key_exists('workflow', $config)) {
            return $this->compileWorkflow($config);
        } elseif (array_key_exists('pipeline', $config)) {
            return $this->compilePipeline($config);
        } elseif (array_key_exists('http_hook', $config)) {
            return $this->compileHook($config);
        } elseif (array_key_exists('http_api', $config)) {
            return $this->compileApi($config);
        }

        throw new \LogicException('Not implemented');
    }

    private function compileWorkflow(array $config): Satellite\Builder\Repository\Workflow
    {
        $workflow = new Satellite\Builder\Workflow(
            new Node\Expr\Variable('runtime')
        );

        $repository = new Satellite\Builder\Repository\Workflow($workflow);

        $repository->addFiles(
            new Packaging\File(
                'main.php',
                new Packaging\Asset\InMemory(
                    <<<PHP
                    <?php
    
                    use Kiboko\Component\Satellite\Console\WorkflowConsoleRuntime;
                    
                    require __DIR__ . '/vendor/autoload.php';
                    
                    /** @var WorkflowConsoleRuntime \$runtime */
                    \$runtime = require __DIR__ . '/runtime.php';
                    
                    /** @var callable(runtime: WorkflowConsoleRuntime): WorkflowConsoleRuntime \$workflow */
                    \$workflow = require __DIR__ . '/workflow.php';
                    
                    \$workflow(\$runtime)->run();
                    PHP
                )
            )
        );

        $repository->addFiles(
            new Packaging\File(
                'runtime.php',
                new Packaging\Asset\AST(
                    new Node\Stmt\Expression(
                        (new Satellite\Builder\Workflow\WorkflowRuntime())->getNode()
                    )
                )
            )
        );

        foreach ($config['workflow']['jobs'] as $job) {
            if (array_key_exists('pipeline', $job)) {
                $pipeline = $this->compilePipeline($job);
                $pipelineFilename = sprintf('%s.php', uniqid('pipeline'));

                $repository->addFiles(
                    new Packaging\File(
                        $pipelineFilename,
                        new Packaging\Asset\AST(
                            new Node\Stmt\Return_(
                                (new Satellite\Builder\Workflow\PipelineBuilder($pipeline->getBuilder()))->getNode()
                            )
                        )
                    )
                );

                $workflow->addPipeline($pipelineFilename);
            } else {
                throw new \LogicException('Not implemented');
            }
        }

        return $repository;
    }

    private function compilePipeline(array $config): Satellite\Builder\Repository\Pipeline
    {
        $pipeline = new Satellite\Builder\Pipeline(
            new Node\Expr\Variable('runtime'),
        );

        $repository = new Satellite\Builder\Repository\Pipeline($pipeline);

        $interpreter = new Satellite\ExpressionLanguage\ExpressionLanguage();

        $repository->addPackages(
            'php-etl/pipeline-contracts:~0.3.0@dev',
            'php-etl/pipeline:~0.4.0@dev',
            'psr/log:^1.1',
            'monolog/monolog',
            'symfony/console:^5.2',
            'symfony/dependency-injection:^5.2',
        );

        $repository->addFiles(
            new Packaging\File(
                'main.php',
                new Packaging\Asset\InMemory(
                    <<<PHP
                    <?php

                    use Kiboko\Component\Satellite\Console\PipelineRuntimeInterface;

                    require __DIR__ . '/vendor/autoload.php';

                    /** @var PipelineRuntimeInterface \$runtime */
                    \$runtime = require __DIR__ . '/runtime.php';

                    /** @var callable(runtime: RuntimeInterface): RuntimeInterface \$pipeline */
                    \$pipeline = require __DIR__ . '/pipeline.php';

                    \$pipeline(\$runtime)->run();
                    PHP
                )
            )
        );

        $repository->addFiles(
            new Packaging\File(
                'runtime.php',
                new Packaging\Asset\AST(
                    new Node\Stmt\Expression(
                        (new Satellite\Builder\Pipeline\ConsoleRuntime())->getNode()
                    )
                )
            )
        );

        if (array_key_exists('expression_language', $config['pipeline'])
            && is_array($config['pipeline']['expression_language'])
            && count($config['pipeline']['expression_language'])
        ) {
            foreach ($config['pipeline']['expression_language'] as $provider) {
                $interpreter->registerProvider(new $provider);
            }
        }

        foreach ($config['pipeline']['steps'] as $step) {
            if (array_key_exists('akeneo', $step)) {
                $clone = clone $interpreter;
                (new Satellite\Pipeline\ConfigurationApplier('akeneo', new Akeneo\Service($clone), $clone))
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
                $clone = clone $interpreter;
                (new Satellite\Pipeline\ConfigurationApplier('sylius', new Sylius\Service(clone $clone), $clone))
                    ->withPackages(
                        'diglin/sylius-api-php-client',
                        'laminas/laminas-diactoros',
                        'php-http/guzzle7-adapter',
                    )
                    ->withExtractor()
                    ->withLoader()
                    ->appendTo($step, $repository);
            } elseif (array_key_exists('csv', $step)) {
                $clone = clone $interpreter;
                (new Satellite\Pipeline\ConfigurationApplier('csv', new CSV\Service(clone $clone), $clone))
                    ->withPackages(
                        'php-etl/pipeline-contracts:~0.3.0@dev',
                        'php-etl/bucket-contracts:~0.1.0@dev',
                        'php-etl/bucket:~0.2.0@dev',
                        'php-etl/csv-flow:~0.2.0@dev',
                    )
                    ->withExtractor()
                    ->withLoader()
                    ->appendTo($step, $repository);
            } elseif (array_key_exists('spreadsheet', $step)) {
                $clone = clone $interpreter;
                (new Satellite\Pipeline\ConfigurationApplier('spreadsheet', new Spreadsheet\Service(clone $clone), $clone))
                    ->withExtractor()
                    ->withLoader()
                    ->appendTo($step, $repository);
            } elseif (array_key_exists('custom', $step)) {
                $clone = clone $interpreter;
                (new Satellite\Pipeline\ConfigurationApplier('custom', new Satellite\Plugin\Custom\Service(), $clone))
                    ->withExtractor()
                    ->withTransformer()
                    ->withLoader()
                    ->appendTo($step, $repository);
            } elseif (array_key_exists('stream', $step)) {
                $clone = clone $interpreter;
                (new Satellite\Pipeline\ConfigurationApplier('stream', new Satellite\Plugin\Stream\Service(), $clone))
                    ->withLoader()
                    ->appendTo($step, $repository);
            } elseif (array_key_exists('batch', $step)) {
                $clone = clone $interpreter;
                (new Satellite\Pipeline\ConfigurationApplier('batch', new Satellite\Plugin\Batching\Service(clone $clone), $clone))
                    ->withTransformer('merge')
                    ->withTransformer('fork')
                    ->appendTo($step, $repository);
            } elseif (array_key_exists('fastmap', $step)) {
                $clone = clone $interpreter;
                (new Satellite\Pipeline\ConfigurationApplier('fastmap', new FastMap\Service(clone $clone), $clone))
                    ->withPackages(
                        'php-etl/pipeline-contracts:~0.3.0@dev',
                        'php-etl/bucket-contracts:~0.1.0@dev',
                        'php-etl/bucket:~0.2.0@dev',
                        'php-etl/fast-map:~0.2.0@dev',
                    )
                    ->withTransformer(null)
                    ->appendTo($step, $repository);
            } elseif (array_key_exists('sftp', $step)) {
                $clone = clone $interpreter;
                (new Satellite\Pipeline\ConfigurationApplier('sftp', new Satellite\Plugin\SFTP\Service(clone $clone), $clone))
                    ->withPackages(
                        'ext-ssh2',
                    )
                    ->withLoader()
                    ->appendTo($step, $repository);
            } elseif (array_key_exists('ftp', $step)) {
                $clone = clone $interpreter;
                (new Satellite\Pipeline\ConfigurationApplier('ftp', new Satellite\Plugin\FTP\Service(clone $clone), $clone))
                    ->withPackages(
                        'ext-ssh2',
                    )
                    ->withLoader()
                    ->appendTo($step, $repository);
            } elseif (array_key_exists('sql', $step)) {
                $clone = clone $interpreter;
                (new Satellite\Pipeline\ConfigurationApplier('sql', new SQL\Service(clone $clone), $clone))
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
}
