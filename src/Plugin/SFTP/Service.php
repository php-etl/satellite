<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\SFTP;

use Kiboko\Component\Satellite\ExpressionLanguage as Satellite;
use Kiboko\Component\Satellite\Plugin\SFTP\Factory\Repository\Repository;
use Kiboko\Contract\Configurator;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

#[Configurator\Pipeline(
    name: 'sftp',
    dependencies: ['ext-ssh2'],
    steps: [
        new Configurator\Pipeline\StepLoader(),
    ],
)] final readonly class Service implements Configurator\PipelinePluginInterface
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
        if (\array_key_exists('expression_language', $config)
            && \is_array($config['expression_language'])
            && \count($config['expression_language'])
        ) {
            foreach ($config['expression_language'] as $provider) {
                $this->interpreter->registerProvider(new $provider());
            }
        }

        if (\array_key_exists('loader', $config)) {
            $loaderFactory = new Factory\Loader($this->interpreter);

            $loaderCompilation = $loaderFactory->compile($config);
            $loaderBuilder = $loaderCompilation->getBuilder();

            return new Repository($loaderBuilder);
        }

        throw new \RuntimeException('No suitable build with the provided configuration.');
    }
}
