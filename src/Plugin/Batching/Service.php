<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Batching;

use Kiboko\Component\FastMap\Compiler\Builder\PropertyPathBuilder;
use Kiboko\Component\Satellite\Plugin\Batching\Builder\Fork;
use Kiboko\Component\Satellite\Plugin\Batching\Builder\Merge;
use Kiboko\Contract\Configurator;
use PhpParser\Node\Expr\Variable;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\PropertyAccess\PropertyPath;
use function Kiboko\Component\SatelliteToolbox\Configuration\compileExpression;

#[Configurator\PipelineStepTransformer(name: "merge")]
#[Configurator\PipelineStepTransformer(name: "fork")]
final class Service implements Configurator\FactoryInterface
{
    private Processor $processor;
    private ConfigurationInterface $configuration;
    private ExpressionLanguage $interpreter;

    public function __construct(
        ?ExpressionLanguage $interpreter = null
    ) {
        $this->processor = new Processor();
        $this->configuration = new Configuration();
        $this->interpreter = $interpreter ?? new ExpressionLanguage();
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
        if (array_key_exists('expression_language', $config)
            && is_array($config['expression_language'])
            && count($config['expression_language'])
        ) {
            foreach ($config['expression_language'] as $provider) {
                $this->interpreter->registerProvider(new $provider);
            }
        }

        if (array_key_exists('merge', $config)) {
            $builder = new Merge($config['merge']['size']);
            return new Repository($builder);
        } elseif (array_key_exists('fork', $config)) {
            $builder = new Fork(
                $config['fork']['foreach'] instanceof Expression ?
                    compileExpression($this->interpreter, $config['fork']['foreach'], 'item', 'key') :
                    (new PropertyPathBuilder(new PropertyPath($config['fork']['foreach']), new Variable('input')))->getNode(),
                compileExpression($this->interpreter, $config['fork']['do'], 'item', 'key'),
            );

            return new Repository($builder);
        }

        throw new \RuntimeException('Unsupported configuration.');
    }
}
