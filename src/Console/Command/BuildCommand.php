<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console\Command;

use Kiboko\Component\Satellite;
use Psr\Log;
use Symfony\Component\Config;
use Symfony\Component\Config\Exception\LoaderLoadException;
use Symfony\Component\Console;

final class BuildCommand extends Console\Command\Command
{
    protected static $defaultName = 'build';
    protected static $defaultDescription = 'Build the satellite.';

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

        chdir(\dirname((string) $filename));

        if (\array_key_exists('satellite', $configuration)) {
            $output->writeln([
                '',
                '',
                '<info>Building Satellite<info>',
                '============',
            ]);

            $factory = new Satellite\Runtime\RuntimeChoice(
                $service,
                $service->adapterChoice(),
                new class() extends Log\AbstractLogger {
                    public function log($level, $message, array $context = []): void
                    {
                        $prefix = sprintf(\PHP_EOL.'[%s] ', strtoupper((string) $level));
                        fwrite(\STDERR, $prefix.str_replace(\PHP_EOL, $prefix, rtrim($message, \PHP_EOL)));
                    }
                },
            );

            $factory($configuration['satellite']);
        } elseif (\array_key_exists('satellites', $configuration)) {
            foreach ($configuration['satellites'] as $satellite) {
                $output->writeln([
                    '',
                    '',
                    '<info>Building Satellite <info>'.$satellite['label'],
                    '============',
                ]);

                $factory = new Satellite\Runtime\RuntimeChoice(
                    $service,
                    $service->adapterChoice(),
                    new class($output) extends Log\AbstractLogger {
                        public function __construct(
                            private readonly Console\Output\OutputInterface $output,
                        ) {
                        }

                        public function log($level, $message, array $context = []): void
                        {
                            $prefix = sprintf(\PHP_EOL.'[%s] ', strtoupper((string) $level));
                            $this->output->writeln($prefix.str_replace(\PHP_EOL, $prefix, rtrim($message, \PHP_EOL)));
                        }
                    },
                );

                $factory($satellite);
            }
        }

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }
}
