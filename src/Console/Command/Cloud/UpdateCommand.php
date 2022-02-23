<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console\Command\Cloud;

use Kiboko\Component\Satellite;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Exception\LoaderLoadException;
use Symfony\Component\Console;

final class UpdateCommand extends Console\Command\Command
{
    protected static $defaultName = 'update';

    private Processor $processor;
    private ConfigurationInterface $configuration;

    public function __construct(string $name = null)
    {
        parent::__construct($name);

        $this->processor = new Processor();
        $this->configuration = new Satellite\Adapter\Cloud\Configuration();
    }

    protected function configure(): void
    {
        $this->setDescription('Connects to the Gyroscops API.');
        $this->addArgument('config', Console\Input\InputArgument::REQUIRED);
    }

    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output): int
    {
        $style = new Console\Style\SymfonyStyle(
            $input,
            $output,
        );

        $filename = $input->getArgument('config');
        if ($filename !== null) {
            $configs = (new Satellite\ConfigLoader(getcwd()))->loadFile($filename);
        } else {
            $possibleFiles = ['satellite.yaml', 'satellite.yml', 'satellite.json'];

            foreach ($possibleFiles as $filename) {
                try {
                    $configs = (new Satellite\ConfigLoader(getcwd()))->loadFile($filename);
                    break;
                } catch (LoaderLoadException) {
                }
            }

            if (!isset($configs)) {
                throw new \RuntimeException('Could not find configuration file.');
            }
        }

        $configuration = $this->processor->processConfiguration($this->configuration, $configs);

        $style->success('Authentication token successfully recovered.');

        return Console\Command\Command::SUCCESS;
    }
}
