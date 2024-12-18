<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Filesystem;

use Kiboko\Component\Packaging;
use Kiboko\Contract\Configurator;

#[Configurator\Adapter(name: 'filesystem')]
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
        $builder = new SatelliteBuilder($configuration['filesystem']['path']);

        if (\array_key_exists('composer', $configuration)) {
            if (\array_key_exists('from_local', $configuration['composer']) && true === $configuration['composer']['from_local']) {
                if (file_exists('composer.lock')) {
                    $builder->withComposerFile(
                        new Packaging\File('composer.json', new Packaging\Asset\LocalFile('composer.json')),
                        new Packaging\File('composer.lock', new Packaging\Asset\LocalFile('composer.lock')),
                    );
                } else {
                    $builder->withComposerFile(
                        new Packaging\File('composer.json', new Packaging\Asset\LocalFile('composer.json')),
                    );
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
                    $builder->withRepositories($repository['name'], $repository['type'], $repository['url']);
                }
            }

            if (\array_key_exists('auth', $configuration['composer']) && (is_countable($configuration['composer']['auth']) ? \count($configuration['composer']['auth']) : 0) > 0) {
                foreach ($configuration['composer']['auth'] as $auth) {
                    match ($auth['type']) {
                        'gitlab-oauth' => $builder->withGitlabOauthAuthentication($auth['token'], $auth['url'] ?? 'gitlab.com'),
                        'gitlab-token' => $builder->withGitlabTokenAuthentication($auth['token'], $auth['url'] ?? 'gitlab.com'),
                        'github-oauth' => $builder->withGithubOauthAuthentication($auth['token'], $auth['url'] ?? 'github.com'),
                        'http-basic' => $builder->withHttpBasicAuthentication($auth['url'], $auth['username'], $auth['password']),
                        'http-bearer' => $builder->withHttpBearerAuthentication($auth['url'], $auth['token']),
                        default => throw new \LogicException(),
                    };
                }
            }
        }

        return $builder;
    }
}
