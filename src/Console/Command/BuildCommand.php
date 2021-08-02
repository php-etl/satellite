<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console\Command;

use Kiboko\Component\Satellite;
use Psr\Log;
use Symfony\Component\Config;
use Symfony\Component\Config\Exception\LoaderLoadException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Console;
use Symfony\Component\Console\Command\Command;
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

        if (array_key_exists('satellite', $configuration)) {
            $output->writeln([
                '',
                '',
                '<info>Building Pipeline<info>',
                '============',
            ]);

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

            $factory($configuration['satellite']);
        }

        if (array_key_exists('imports', $configuration)) {
            foreach ($configuration['imports'] as $imports) {
                foreach ($imports as $import) {
                    $output->writeln([
                        '',
                        '',
                        '<info>Building Pipeline<info>',
                        '============',
                    ]);

                    $fileLocator = new FileLocator();
                    $loaderResolver = new LoaderResolver([
                        new Satellite\Console\Config\YamlFileLoader($fileLocator),
                        new Satellite\Console\Config\JsonFileLoader($fileLocator)
                    ]);

                    $fileConfig = $import(new DelegatingLoader($loaderResolver));

                    if (array_key_exists('satellite', $configuration)) {
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

                        $factory($fileConfig['satellite']);
                    }
                }
            }
        }

        return 0;
    }
}
