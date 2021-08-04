<?php

namespace Kiboko\Component\Satellite;

use Kiboko\Component\Satellite;
use Kiboko\Component\SatelliteToolbox\Configuration\ImportConfiguration;
use Symfony\Component\Config;
use Symfony\Component\Config\Definition\Processor;

class ConfigLoader implements ConfigLoaderInterface
{
    private Processor $processor;
    private array $loadedFiles;

    public function __construct()
    {
        $this->processor = new Processor();
        $this->loadedFiles = [];
    }

    public function loadFile(string $file): array
    {
        $locator = new Config\FileLocator([getcwd()]);

        $loaderResolver = new Config\Loader\LoaderResolver([
            new Satellite\Console\Config\YamlFileLoader($locator),
            new Satellite\Console\Config\JsonFileLoader($locator),
        ]);

        $delegatingLoader = new Config\Loader\DelegatingLoader($loaderResolver);

        $configs[] = $delegatingLoader->load($file);
        $this->loadedFiles[] = $file;

        $arrayIterator = new \RecursiveArrayIterator($configs);
        $recursiveIterator = new \RecursiveIteratorIterator($arrayIterator, \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($recursiveIterator as $value) {
            if (is_array($value) && array_key_exists('imports', $value)) {
                $imports = $this->processor->processConfiguration(new ImportConfiguration(), ['imports' => $value['imports']]);

                $fileConfig = [];
                foreach ($imports as $import) {
                    array_push($fileConfig, ...$this->loadFile($import["resource"]));
                }

//                foreach ($fileConfig as $config) {
//                    if (!array_key_exists('version', $config)) {
//                        $value = array_merge($value, $config);
//                    }
//                }

                $configs = array_merge($configs, $fileConfig);
            }
        }

        return $configs;
    }
}
