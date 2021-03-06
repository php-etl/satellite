<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console\Command;

use Kiboko\Component\Satellite;
use Psr\Log;
use Symfony\Component\Config;
use Symfony\Component\Config\Exception\LoaderLoadException;
use Symfony\Component\Console;
use Symfony\Component\Yaml;

final class BuildCommand extends Console\Command\Command
{
    protected static $defaultName = 'build';

    protected function configure()
    {
        $this->setDescription('Build the satellite docker image.');
        $this->addArgument('config', Console\Input\InputArgument::REQUIRED);
    }

    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output)
    {
        $service = new Satellite\Service();

        $style = new Console\Style\SymfonyStyle(
            $input,
            $output,
        );

        $locator = new Config\FileLocator([getcwd()]);

        $loaderResolver = new Config\Loader\LoaderResolver([
            new Satellite\Console\Config\YamlFileLoader($locator),
            new Satellite\Console\Config\JsonFileLoader($locator),
        ]);

        $delegatingLoader = new Config\Loader\DelegatingLoader($loaderResolver);

        $filename = $input->getArgument('config');
        if ($filename !== null) {
            $configuration = $delegatingLoader->load($filename);
        } else {
            $possibleFiles = ['satellite.yaml', 'satellite.yml', 'satellite.json'];

            foreach ($possibleFiles as $filename) {
                try {
                    $configuration = $delegatingLoader->load($filename);
                    break;
                } catch (LoaderLoadException) {
                }
            }

            if (!isset($configuration)) {
                throw new \RuntimeException('Could not find configuration file.');
            }
        }

        try {
            $configuration = $service->normalize($configuration);
        } catch (Config\Definition\Exception\InvalidTypeException|Config\Definition\Exception\InvalidConfigurationException $exception) {
            $style->error($exception->getMessage());
            return 255;
        }

        \chdir(\dirname($filename));

        $factory = new Satellite\Runtime\Factory(
            new Satellite\Adapter\Factory(),
            new class() extends Log\AbstractLogger {
                public function log($level, $message, array $context = array())
                {
                    $prefix = sprintf(PHP_EOL . "[%s] ", strtoupper($level));
                    fwrite(STDERR, $prefix . str_replace(PHP_EOL, $prefix, rtrim($message, PHP_EOL)));
                }
            },
        );

        $factory($configuration);

        return 0;
    }
}
