<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\Logger;

use Kiboko\Component\Satellite\ExpressionLanguage as Satellite;
use Kiboko\Component\Satellite\Feature\Logger\Builder\LogstashFormatterBuilder;
use Kiboko\Contract\Configurator;
use Kiboko\Contract\Configurator\Feature;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

#[Feature(name: 'logger')]
final readonly class Service implements Configurator\PipelineFeatureInterface
{
    private Processor $processor;
    private Configurator\FeatureConfigurationInterface $configuration;

    public function __construct(
        private ExpressionLanguage $interpreter = new Satellite\ExpressionLanguage(),
    ) {
        $this->processor = new Processor();
        $this->configuration = new Configuration();
    }

    public function interpreter(): ExpressionLanguage
    {
        return $this->interpreter;
    }

    public function configuration(): Configurator\FeatureConfigurationInterface
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
            if ($this->processor->processConfiguration($this->configuration, $config)) {
                return true;
            }
        } catch (\Exception) {
        }

        return false;
    }

    public function compile(array $config): Repository
    {
        $builder = new Builder\Logger();
        $repository = new Repository($builder);

        try {
            if (\array_key_exists('inherit', $config)) {
                $builder->withLogger((new Builder\InheritBuilder())->getNode());

                return $repository;
            }
            if (\array_key_exists('stderr', $config)
                || (\array_key_exists('type', $config) && 'stderr' === $config['type'])
            ) {
                $builder->withLogger((new Builder\StderrLogger())->getNode());
                $repository->addPackages('psr/log:*');

                return $repository;
            }
            if (\array_key_exists('blackhole', $config)
                || (\array_key_exists('type', $config) && 'null' === $config['type'])
            ) {
                $builder->withLogger((new Builder\NullLogger())->getNode());
                $repository->addPackages('psr/log:*');

                return $repository;
            }

            if (!\array_key_exists('destinations', $config)
                || !\array_key_exists('channel', $config)
                || (is_countable($config['destinations']) ? \count($config['destinations']) : 0) <= 0
            ) {
                return $repository;
            }

            $monologBuilder = new Builder\MonologLogger($config['channel']);

            $repository->addPackages('monolog/monolog:^2.5');

            foreach ($config['destinations'] as $destination) {
                if (\array_key_exists('stream', $destination)) {
                    $factory = new Factory\StreamFactory();

                    $streamRepository = $factory->compile($destination['stream']);

                    $repository->merge($streamRepository);
                    $monologBuilder->withHandlers($streamRepository->getBuilder()->getNode());
                }

                if (\array_key_exists('syslog', $destination)) {
                    $factory = new Factory\SyslogFactory();

                    $syslogRepository = $factory->compile($destination['syslog']);

                    $repository->merge($syslogRepository);
                    $monologBuilder->withHandlers($syslogRepository->getBuilder()->getNode());
                }

                if (\array_key_exists('logstash', $destination)) {
                    $factory = new Factory\GelfFactory();

                    $gelfRepository = $factory->compile($destination['logstash']);

                    $gelfRepository->getBuilder()->withFormatters(
                        (new LogstashFormatterBuilder($destination['logstash']['application_name']))->getNode()
                    );

                    $repository->merge($gelfRepository);
                    $monologBuilder->withHandlers($gelfRepository->getBuilder()->getNode());

                    $repository->addPackages('graylog2/gelf-php:0.1.*');
                }

                if (\array_key_exists('gelf', $destination)) {
                    $factory = new Factory\GelfFactory();

                    $gelfRepository = $factory->compile($destination['gelf']);

                    $repository->merge($gelfRepository);
                    $monologBuilder->withHandlers($gelfRepository->getBuilder()->getNode());

                    $repository->addPackages('graylog2/gelf-php:1.7.*');
                }

                if (\array_key_exists('elasticsearch', $destination)) {
                    $factory = new Factory\ElasticSearchFactory($this->interpreter);

                    $gelfRepository = $factory->compile($destination['elasticsearch']);

                    $repository->merge($gelfRepository);
                    $monologBuilder->withHandlers($gelfRepository->getBuilder()->getNode());

                    $repository->addPackages('elasticsearch/elasticsearch:~7.0');
                }
            }

            $builder->withLogger($monologBuilder->getNode());

            return $repository;
        } catch (Symfony\InvalidConfigurationException|Symfony\InvalidTypeException $exception) {
            throw new Configurator\InvalidConfigurationException(message: $exception->getMessage(), previous: $exception);
        }
    }
}
