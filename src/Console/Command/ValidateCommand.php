<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console\Command;

use Kiboko\Component\Satellite;
use Kiboko\Component\Satellite\Exception\ConfigurationNotFoundException;
use Kiboko\Component\Satellite\Exception\PluginNotFoundException;
use Kiboko\Component\Satellite\Exception\WorkingDirectoryException;
use Symfony\Component\Config;
use Symfony\Component\Config\Exception\LoaderLoadException;
use Symfony\Component\Console;

final class ValidateCommand extends Console\Command\Command
{
    protected static $defaultName = 'validate';
    protected static $defaultDescription = 'Validate the satellite configuration.';

    protected function configure(): void
    {
        $this->addArgument('config', Console\Input\InputArgument::REQUIRED);
        $this->addOption('output', 'o', Console\Input\InputOption::VALUE_REQUIRED);
    }

    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output): int
    {
        $style = new Console\Style\SymfonyStyle(
            $input,
            $output,
        );

        try {
            return $this->doExecute($input, $output, $style);
        } catch (ConfigurationNotFoundException|PluginNotFoundException|WorkingDirectoryException $e) {
            $style->error($e->getMessage());

            return self::FAILURE;
        } catch (\Throwable $e) {
            $style->error($e->getMessage());
            if ($output->isVerbose()) {
                $style->writeln($e->getTraceAsString());
            }

            return self::FAILURE;
        }
    }

    private function doExecute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output, Console\Style\SymfonyStyle $style): int
    {
        $basePath = getcwd();
        if (false === $basePath) {
            throw WorkingDirectoryException::couldNotGet();
        }

        $filename = $input->getArgument('config');
        if (null !== $filename) {
            $configuration = (new Satellite\ConfigLoader($basePath))->loadFile($filename);
        } else {
            $possibleFiles = ['satellite.yaml', 'satellite.yml', 'satellite.json'];

            foreach ($possibleFiles as $filename) {
                try {
                    $configuration = (new Satellite\ConfigLoader($basePath))->loadFile($filename);
                    break;
                } catch (LoaderLoadException) {
                }
            }

            if (!isset($configuration)) {
                throw ConfigurationNotFoundException::fileNotFound();
            }
        }

        for ($directory = $basePath; '/' !== $directory; $directory = \dirname($directory)) {
            if (file_exists($directory.'/.gyro.php')) {
                break;
            }
        }

        if (!file_exists($directory.'/.gyro.php')) {
            throw PluginNotFoundException::gyroscopsPlugins();
        }

        $context = new Satellite\Console\RuntimeContext(
            $input->getOption('output') ?? 'php://fd/3',
            new Satellite\ExpressionLanguage\ExpressionLanguage(),
        );

        $factory = require $directory.'/.gyro.php';
        $service = $factory($context);

        try {
            $configuration = $service->normalize($configuration);
        } catch (Config\Definition\Exception\InvalidConfigurationException|Config\Definition\Exception\InvalidTypeException $exception) {
            $style->error($exception->getMessage());

            return 255;
        }

        $style->success('The configuration is valid.');

        $json = json_encode($configuration, \JSON_PRETTY_PRINT);
        $style->writeln(\is_string($json) ? $json : '{}');

        return Console\Command\Command::SUCCESS;
    }
}
