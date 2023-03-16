<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Action\SFTP;

use Kiboko\Component\Satellite\ExpressionLanguage as Satellite;
use Kiboko\Component\Satellite\Plugin\SFTP\Factory\Repository\Repository;
use Kiboko\Contract\Configurator;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

#[Configurator\Action(
    name: 'sftp',
    dependencies: ['ext-ssh2'],
)]
final class Service implements Configurator\PipelineActionInterface
{
    private Processor $processor;
    private Configurator\ActionConfigurationInterface $configuration;
    private ExpressionLanguage $interpreter;

    public function __construct(
        ?ExpressionLanguage $interpreter = null
    ) {
        $this->processor = new Processor();
        $this->configuration = new Configuration();
        $this->interpreter = $interpreter ?? new Satellite\ExpressionLanguage();
    }

    public function interpreter(): ExpressionLanguage
    {
        return $this->interpreter;
    }

    public function configuration(): Configurator\ActionConfigurationInterface
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
        } catch (Symfony\InvalidTypeException|Symfony\InvalidConfigurationException) {
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

        if (\array_key_exists('execute', $config)) {
            $actionFactory = new Factory\Action($this->interpreter);

            $action = $actionFactory->compile($config);
            $actionBuilder = $action->getBuilder();

            return new Repository($actionBuilder);
        }

        throw new \RuntimeException('No suitable build with the provided configuration.');
    }
}
