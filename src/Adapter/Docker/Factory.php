<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Docker;

use Kiboko\Component\Packaging;
use Kiboko\Contract\Configurator;

#[Configurator\Adapter(name: 'docker')]
final readonly class Factory implements Configurator\Adapter\FactoryInterface
{
    private Configuration $configuration;

    public function __construct()
    {
        $this->configuration = new Configuration();
    }

    public function configuration(): Configurator\AdapterConfigurationInterface
    {
        return $this->configuration;
    }

    public function __invoke(array $configuration): Configurator\SatelliteBuilderInterface
    {
        $builder = new SatelliteBuilder($configuration['docker']['from']);

        if (isset($configuration['docker']['workdir'])) {
            $builder->withWorkdir($configuration['docker']['workdir']);
        }
        if (isset($configuration['docker']['tags'])) {
            $builder->withTags(...$configuration['docker']['tags']);
        }
        if (isset($configuration['docker']['copy'])) {
            foreach ($configuration['docker']['copy'] as $copy) {
                if (!file_exists($copy['from'])) {
                    throw new FileOrDirectoryNotFoundException(strtr('Unable to find the file or the directory at path %path%', ['path' => $copy['from']]));
                }

                if (is_file($copy['from'])) {
                    $builder->withFile(new Packaging\File($copy['from'], new Packaging\Asset\LocalFile($copy['from'])), $copy['to']);
                } else {
                    $builder->withDirectory(new Packaging\Directory($copy['from']), $copy['to']);
                }
            }
        }

        if (\array_key_exists('composer', $configuration)) {
            if (\array_key_exists('from_local', $configuration['composer']) && true === $configuration['composer']['from_local']) {
                if (file_exists('composer.lock')) {
                    $builder->withComposerFile(new Packaging\Asset\LocalFile('composer.json'), new Packaging\Asset\LocalFile('composer.lock'));
                } else {
                    $builder->withComposerFile(new Packaging\Asset\LocalFile('composer.json'));
                }
                if (file_exists('vendor')) {
                    $builder->withDirectory(new Packaging\Directory('vendor/'));
                }
            }

            if (\array_key_exists('autoload', $configuration['composer']) && \array_key_exists('psr4', $configuration['composer']['autoload'])) {
                foreach ($configuration['composer']['autoload']['psr4'] as $namespace => $autoload) {
                    $builder->withComposerPSR4Autoload($namespace, ...$autoload['paths']);
                }
            }

            if (\array_key_exists('require', $configuration['composer'])) {
                $builder->withComposerRequire(...$configuration['composer']['require']);
            }

            if (\array_key_exists('repositories', $configuration['composer']) && (is_countable($configuration['composer']['repositories']) ? \count($configuration['composer']['repositories']) : 0) > 0) {
                foreach ($configuration['composer']['repositories'] as $repository) {
                    $builder->withComposerRepositories($repository['name'], $repository['type'], $repository['url']);
                }
            }

            if (\array_key_exists('auth', $configuration['composer']) && (is_countable($configuration['composer']['auth']) ? \count($configuration['composer']['auth']) : 0) > 0) {
                foreach ($configuration['composer']['auth'] as $auth) {
                    $builder->withComposerAuthenticationToken($auth['url'], $auth['token']);
                }
            }
        }

        return $builder;
    }
}
