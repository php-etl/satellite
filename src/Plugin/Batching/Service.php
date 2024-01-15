<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Batching;

use Kiboko\Component\Satellite\ExpressionLanguage as Satellite;
use Kiboko\Component\Satellite\Plugin\Batching\Builder\Fork;
use Kiboko\Component\Satellite\Plugin\Batching\Builder\Merge;
use Kiboko\Component\SatelliteToolbox\Builder\PropertyPathBuilder;
use Kiboko\Contract\Configurator;
use PhpParser\Node\Expr\Variable;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\PropertyAccess\PropertyPath;

use function Kiboko\Component\SatelliteToolbox\Configuration\compileExpression;

#[Configurator\Pipeline(
    name: 'batch',
    steps: [
        new Configurator\Pipeline\StepTransformer('merge'),
        new Configurator\Pipeline\StepTransformer('fork'),
    ],
)]
final readonly class Service implements Configurator\PipelinePluginInterface
{
    private Processor $processor;
    private Configurator\PluginConfigurationInterface $configuration;

    public function __construct(
        private ExpressionLanguage $interpreter = new Satellite\ExpressionLanguage()
    ) {
        $this->processor = new Processor();
        $this->configuration = new Configuration();
    }

    public function interpreter(): ExpressionLanguage
    {
        return $this->interpreter;
    }

    public function configuration(): Configurator\PluginConfigurationInterface
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
        } catch (Symfony\InvalidConfigurationException|Symfony\InvalidTypeException $exception) {
            throw new Configurator\InvalidConfigurationException($exception->getMessage(), 0, $exception);
        }
    }

    public function validate(array $config): bool
    {
        try {
            $this->processor->processConfiguration($this->configuration, $config);

            return true;
        } catch (Symfony\InvalidConfigurationException|Symfony\InvalidTypeException) {
            return false;
        }
    }

    /**
     * @throws Configurator\ConfigurationExceptionInterface
     */
    public function compile(array $config): Configurator\RepositoryInterface
    {
        $interpreter = clone $this->interpreter;

        if (\array_key_exists('expression_language', $config)
            && \is_array($config['expression_language'])
            && \count($config['expression_language'])
        ) {
            foreach ($config['expression_language'] as $provider) {
                $interpreter->registerProvider(new $provider());
            }
        }

        if (\array_key_exists('merge', $config)) {
            $builder = new Merge($config['merge']['size']);

            return new Repository($builder);
        }
        if (\array_key_exists('fork', $config)) {
            $builder = new Fork(
                $config['fork']['foreach'] instanceof Expression ?
                    compileExpression($interpreter, $config['fork']['foreach'], 'item', 'key') :
                    (new PropertyPathBuilder(new PropertyPath($config['fork']['foreach']), new Variable('input')))->getNode(),
                compileExpression($interpreter, $config['fork']['do'], 'item', 'key'),
            );

            return new Repository($builder);
        }

        throw new \RuntimeException('Unsupported configuration.');
    }
}
