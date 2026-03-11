<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console\Command\Migrate;

use Kiboko\Component\Satellite\Exception\ConfigurationEncodeException;
use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Convertit satellite.pipeline en satellite.workflows (array à 1 workflow).
 *
 * @see documentation/requirements/GUIDE-MIGRATION-COMPILATEUR-V1.md
 */
#[Console\Attribute\AsCommand(
    name: 'migrate:pipeline-to-workflow',
    description: 'Migrate satellite.pipeline to satellite.workflows format',
)]
final class MigratePipelineToWorkflowCommand extends Console\Command\Command
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

        if (!\array_key_exists('satellite', $config) && !\array_key_exists('satellites', $config)) {
            $style->error('Config must contain "satellite" or "satellites" key.');
            return self::FAILURE;
        }

        $satellites = $config['satellites'] ?? [$config['satellite'] ?? []];

        foreach ($satellites as $key => &$satellite) {
            if (!\array_key_exists('pipeline', $satellite)) {
                $style->warning(sprintf('No pipeline in satellite "%s", skipping.', is_string($key) ? $key : 'default'));
                continue;
            }

            $pipeline = $satellite['pipeline'];
            unset($satellite['pipeline']);

            $satellite['workflows'] = [
                [
                    'name' => $satellite['label'] ?? 'Migrated workflow',
                    'inputs' => [
                        [
                            'id' => 'input',
                            'label' => 'Entrée',
                            'activationRule' => ['condition' => 'true', 'inputBindings' => []],
                        ],
                    ],
                    'jobs' => [
                        [
                            'id' => 'n1',
                            'type' => 'pipeline',
                            'label' => $satellite['label'] ?? 'Pipeline',
                            'pipeline' => $pipeline,
                            'activationRule' => [
                                'condition' => 'true',
                                'inputBindings' => [
                                    ['inputName' => 'data', 'sourceNodeId' => 'input', 'outputKey' => 'out'],
                                ],
                            ],
                        ],
                    ],
                    'outputs' => [
                        [
                            'id' => 'output',
                            'label' => 'Résultat',
                            'activationRule' => [
                                'condition' => 'true',
                                'inputBindings' => [
                                    ['inputName' => 'data', 'sourceNodeId' => 'n1', 'outputKey' => 'out'],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }

        unset($satellite);

        if (\array_key_exists('satellite', $config)) {
            $config['satellite'] = array_values($satellites)[0] ?? $config['satellite'];
        } else {
            $config['satellites'] = $satellites;
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
