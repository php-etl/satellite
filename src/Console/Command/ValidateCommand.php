<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console\Command;

use Kiboko\Component\Satellite;
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

        $filename = $input->getArgument('config');
        if (null !== $filename) {
            $configuration = (new Satellite\ConfigLoader(getcwd()))->loadFile($filename);
        } else {
            $possibleFiles = ['satellite.yaml', 'satellite.yml', 'satellite.json'];

            foreach ($possibleFiles as $filename) {
                try {
                    $configuration = (new Satellite\ConfigLoader(getcwd()))->loadFile($filename);
                    break;
                } catch (LoaderLoadException) {
                }
            }

            if (!isset($configuration)) {
                throw new \RuntimeException('Could not find configuration file.');
            }
        }

        for ($directory = getcwd(); '/' !== $directory; $directory = \dirname($directory)) {
            if (file_exists($directory.'/.gyro.php')) {
                break;
            }
        }

        if (!file_exists($directory.'/.gyro.php')) {
            throw new \RuntimeException('Could not load Gyroscops Satellite plugins.');
        }

        $context = new Satellite\Console\RuntimeContext(
            $input->getOption('output') ?? 'php://fd/3',
            new Satellite\ExpressionLanguage\ExpressionLanguage(),
        );

        $factory = require $directory.'/.gyro.php';
        $service = $factory($context);

        try {
            $configuration = $service->normalize($configuration);
        } catch (Config\Definition\Exception\InvalidTypeException|Config\Definition\Exception\InvalidConfigurationException $exception) {
            $style->error($exception->getMessage());

            return 255;
        }

        $style->success('The configuration is valid.');

        $style->writeln(json_encode($configuration, \JSON_PRETTY_PRINT));

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }
}
