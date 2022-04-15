<?php

namespace Kiboko\Component\Satellite;

use Kiboko\Component\Satellite;
use Kiboko\Contract\Configurator\InvalidConfigurationException;
use Symfony\Component\Config;

class ConfigLoader implements ConfigLoaderInterface
{
    public function __construct(private string $basePath)
    {
    }

    /** @return \Generator<array> */
    private function load(
        Config\Loader\LoaderInterface $loader,
        string $path,
    ): \Generator {
        $config = $loader->load($path);

        if ($config === null) {
            throw new InvalidConfigurationException('Provided configuration seems to be empty, supported formats are YAML and JSON.');
        }

        if (array_key_exists('imports', $config)) {
            $imports = $config['imports'];
            unset($config['imports']);

            foreach ($imports as $import) {
                yield from $this->load($loader, $import['resource']);
            }
        }

        if (array_key_exists('satellites', $config)) {
            if (array_key_exists('imports', $config['satellites'])) {
                $imports = $config['satellites']['imports'];
                unset($config['satellites']['imports']);

                foreach ($imports as $import) {
                    $config['satellites'] = array_merge($config['satellites'], $loader->load($import['resource']));
                }
            }

            foreach ($config['satellites'] as &$satellite) {
                if (array_key_exists('imports', $satellite)) {
                    $imports = $satellite['imports'];
                    unset($satellite['imports']);

                    foreach ($imports as $import) {
                        $satellite = array_merge($satellite, $loader->load($import['resource']));
                    }
                }

                if (array_key_exists('workflow', $satellite)) {
                    if (array_key_exists('imports', $satellite['workflow'])) {
                        $imports = $satellite['workflow']['imports'];
                        unset($satellite['workflow']['imports']);

                        foreach ($imports as $import) {
                            $satellite['workflow'] = array_merge($satellite['workflow'], $loader->load($import['resource']));
                        }
                    }


                    if (array_key_exists('imports', $satellite['workflow']['jobs'])) {
                        $imports = $satellite['workflow']['jobs']['imports'];
                        unset($satellite['workflow']['jobs']['imports']);

                        foreach ($imports as $import) {
                            $satellite['workflow']['jobs'] = array_merge($satellite['workflow']['jobs'], $loader->load($import['resource']));
                        }
                    }
                }

                if (array_key_exists('pipeline', $satellite)
                    && array_key_exists('imports', $satellite['pipeline'])
                ) {
                    $imports = $satellite['pipeline']['imports'];
                    unset($satellite['pipeline']['imports']);

                    foreach ($imports as $import) {
                        $satellite['pipeline'] = array_merge($satellite['pipeline'], $loader->load($import['resource']));
                    }
                }
            }
        }

        yield $config;
    }

    public function loadFile(string $file): array
    {
        $locator = new Config\FileLocator([$this->basePath]);

        $loaderResolver = new Config\Loader\LoaderResolver([
            new Satellite\Console\Config\YamlFileLoader($locator),
            new Satellite\Console\Config\JsonFileLoader($locator),
        ]);

        $delegatingLoader = new Config\Loader\DelegatingLoader($loaderResolver);

        return iterator_to_array($this->load($delegatingLoader, $file), false);
    }
}
