<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console\Command\Migrate;

use Kiboko\Component\Satellite\Exception\ConfigurationEncodeException;
use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Convertit jobs (ancien format avec code) en format v1 avec activationRule et inputBindings.
 *
 * @see documentation/requirements/GUIDE-MIGRATION-COMPILATEUR-V1.md
 */
#[Console\Attribute\AsCommand(
    name: 'migrate:workflow-jobs-to-graph',
    description: 'Migrate workflow jobs to v1 format with activationRule and inputBindings',
)]
final class MigrateWorkflowJobsToGraphCommand extends Console\Command\Command
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

        $workflow = $config['workflow'] ?? $config['satellite']['workflow'] ?? null;
        if ($workflow === null) {
            foreach ($config['satellites'] ?? [] as $sat) {
                if (isset($sat['workflow'])) {
                    $workflow = $sat['workflow'];
                    break;
                }
            }
        }

        if ($workflow === null || !\array_key_exists('jobs', $workflow)) {
            $style->error('No workflow with jobs found.');
            return self::FAILURE;
        }

        $jobs = $workflow['jobs'];
        $lastJobId = null;

        if (isset($jobs[0])) {
            $v1Jobs = [];
            $prevId = 'input';
            foreach (array_values($jobs) as $i => $job) {
                $id = $job['id'] ?? $job['code'] ?? 'n' . ($i + 1);
                $v1Jobs[] = array_merge($job, [
                    'id' => $id,
                    'activationRule' => $job['activationRule'] ?? [
                        'condition' => 'true',
                        'inputBindings' => [['inputName' => 'data', 'sourceNodeId' => $prevId, 'outputKey' => 'out']],
                    ],
                ]);
                $prevId = $id;
                $lastJobId = $id;
            }
            $workflow['jobs'] = $v1Jobs;
        } else {
            $v1Jobs = [];
            $keys = array_keys($jobs);
            $prevId = 'input';
            foreach ($keys as $key) {
                $job = $jobs[$key];
                $id = $job['id'] ?? $job['code'] ?? $key;
                $v1Jobs[] = array_merge($job, [
                    'id' => $id,
                    'activationRule' => $job['activationRule'] ?? [
                        'condition' => 'true',
                        'inputBindings' => [['inputName' => 'data', 'sourceNodeId' => $prevId, 'outputKey' => 'out']],
                    ],
                ]);
                $prevId = $id;
                $lastJobId = $id;
            }
            $workflow['jobs'] = $v1Jobs;
        }

        if (!isset($workflow['inputs'])) {
            $workflow['inputs'] = [
                ['id' => 'input', 'label' => 'Entrée', 'activationRule' => ['condition' => 'true', 'inputBindings' => []]],
            ];
        }
        if (!isset($workflow['outputs']) && $lastJobId !== null) {
            $workflow['outputs'] = [
                [
                    'id' => 'output',
                    'label' => 'Résultat',
                    'activationRule' => [
                        'condition' => 'true',
                        'inputBindings' => [['inputName' => 'data', 'sourceNodeId' => $lastJobId, 'outputKey' => 'out']],
                    ],
                ],
            ];
        }

        if (isset($config['workflow'])) {
            $config['workflow'] = $workflow;
        } elseif (isset($config['satellite']['workflow'])) {
            $config['satellite']['workflow'] = $workflow;
        } else {
            foreach ($config['satellites'] ?? [] as $k => $sat) {
                if (isset($sat['workflow'])) {
                    $config['satellites'][$k]['workflow'] = $workflow;
                    break;
                }
            }
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
