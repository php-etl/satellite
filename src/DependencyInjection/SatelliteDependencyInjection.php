<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\DependencyInjection;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

final class SatelliteDependencyInjection
{
    private readonly array $providers;
    public function __construct(
        ExpressionFunctionProviderInterface ...$providers,
    ){
        $this->providers = $providers;
    }

    public function __invoke(array $config): ContainerBuilder
    {
        $container = new ContainerBuilder();

        foreach ($this->providers as $provider) {
            $container->addExpressionLanguageProvider($provider);
        }

        if (\array_key_exists('parameters', $config)
            && \is_array($config['parameters'])
            && \count($config['parameters']) > 0
        ) {
            foreach ($config['parameters'] as $identifier => $parameter) {
                $container->setParameter($identifier, $parameter);
            }
        }

        $container->register('logger', LoggerInterface::class);

        if (\array_key_exists('services', $config)
            && \is_array($config['services'])
            && \count($config['services']) > 0
        ) {
            foreach ($config['services'] as $identifier => $service) {
                if (\array_key_exists('class', $service)) {
                    $class = $service['class'];
                }

                $definition = $container->register($identifier, $class ?? null);

                if (\array_key_exists('arguments', $service)
                    && \is_array($service['arguments'])
                    && \count($service['arguments']) > 0
                ) {
                    foreach ($service['arguments'] as $key => $argument) {
                        $argument = $this->resolveArgument($argument);

                        if (is_numeric($key)) {
                            $definition->addArgument($argument);
                        } else {
                            $definition->setArgument($key, $argument);
                        }
                    }
                }

                if (\array_key_exists('calls', $service)
                    && \is_array($service['calls'])
                    && \count($service['calls']) > 0
                ) {
                    foreach ($service['calls'] as $key => $arguments) {
                        $definition->addMethodCall($key, array_map(function ($argument) {
                            if (preg_match('/^@=[^@=]/', $argument)) {
                                return new Expression(substr($argument, 2));
                            }
                            if (preg_match('/^@[^@]/', $argument)) {
                                return new Reference(substr($argument, 1));
                            }
                            if (preg_match('/^%[^%].*[^%]%$/', $argument)) {
                                return new Parameter(substr($argument, 1, -1));
                            }

                            return $argument;
                        }, $arguments));
                    }
                }

                if (\array_key_exists('factory', $service)
                    && \is_array($service['factory'])
                    && \count($service['factory']) > 0
                ) {
                    $definition->setFactory([$service['factory']['class'], $service['factory']['method']]);
                }

                $definition->setPublic($service['public']);
            }
        }

        $container->compile();

        return $container;
    }

    private function resolveArgument(mixed $argument): mixed
    {
        if (\is_string($argument) && str_starts_with($argument, '@=')) {
            return new Expression(substr($argument, 2));
        }

        if (\is_string($argument) && str_starts_with($argument, '@')) {
            return new Reference(substr($argument, 1));
        }

        if (\is_array($argument)) {
            foreach ($argument as &$value) {
                $value = $this->resolveArgument($value);
            }
            unset($value);

            return $argument;
        }

        return $argument;
    }
}
