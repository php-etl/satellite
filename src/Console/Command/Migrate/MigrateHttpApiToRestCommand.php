<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console\Command\Migrate;

use Kiboko\Component\Satellite\Exception\ConfigurationEncodeException;
use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Convertit http_api en rest (array).
 *
 * @see documentation/requirements/GUIDE-MIGRATION-COMPILATEUR-V1.md
 */
#[Console\Attribute\AsCommand(
    name: 'migrate:http-api-to-rest',
    description: 'Migrate http_api to rest format',
)]
final class MigrateHttpApiToRestCommand extends Console\Command\Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('file', Console\Input\InputArgument::REQUIRED, 'Path to satellite config (YAML or JSON)')
            ->addOption('output', 'o', Console\Input\InputOption::VALUE_REQUIRED, 'Output file path (default: overwrite input)')
            ->addOption('dry-run', null, Console\Input\InputOption::VALUE_NONE, 'Show result without writing');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new Console\Style\SymfonyStyle($input, $output);

        try {
            return $this->doExecute($input, $style);
        } catch (ConfigurationEncodeException $e) {
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

    private function doExecute(InputInterface $input, Console\Style\SymfonyStyle $style): int
    {
        $path = $input->getArgument('file');

        if (!is_readable($path)) {
            $style->error(sprintf('File not found or not readable: %s', $path));
            return self::FAILURE;
        }

        $content = file_get_contents($path);
        $config = str_ends_with(strtolower($path), '.json')
            ? json_decode($content, true, 512, \JSON_THROW_ON_ERROR)
            : Yaml::parse($content);

        $target = $config['satellite'] ?? null;
        if ($target === null && isset($config['satellites'])) {
            foreach ($config['satellites'] as &$sat) {
                if (\array_key_exists('http_api', $sat)) {
                    $sat['rest'] = [$sat['http_api']];
                    unset($sat['http_api']);
                }
            }
            unset($sat);
        } elseif ($target !== null && \array_key_exists('http_api', $target)) {
            $config['satellite']['rest'] = [$config['satellite']['http_api']];
            unset($config['satellite']['http_api']);
        } else {
            $style->warning('No http_api found in config.');
            return self::SUCCESS;
        }

        $result = $config;

        if ($input->getOption('dry-run')) {
            $output = str_ends_with(strtolower($path), '.json')
                ? json_encode($result, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE)
                : Yaml::dump($result, 4, 2);
            $style->writeln(\is_string($output) ? $output : '');
            return self::SUCCESS;
        }

        $outPath = $input->getOption('output') ?? $path;
        $dump = str_ends_with(strtolower($outPath), '.json')
            ? json_encode($result, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE)
            : Yaml::dump($result, 4, 2);
        if (!\is_string($dump)) {
            throw ConfigurationEncodeException::failed();
        }

        file_put_contents($outPath, $dump);
        $style->success(sprintf('Migrated to %s', $outPath));

        return self::SUCCESS;
    }
}
