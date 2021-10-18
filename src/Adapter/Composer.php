<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

final class Composer
{
    public function __construct(private string $workdir, private ?LoggerInterface $logger = null)
    {
        $this->logger = $this->logger ?? new class() extends AbstractLogger {
            public function log($level, $message, array $context = array())
            {
                $prefix = sprintf(PHP_EOL . "[%s] ", strtoupper($level));
                fwrite(STDERR, $prefix . str_replace(PHP_EOL, $prefix, rtrim($message, PHP_EOL)));
            }
        };
    }

    private function execute(string ...$command): void
    {
        $process = new Process($command);
        $process->setWorkingDirectory($this->workdir);

        $process->setTimeout(300);

        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                $this->logger->info($buffer);
            } else {
                $this->logger->debug($buffer);
            }
        });

        if ($process->getExitCode() !== 0) {
            throw new \RuntimeException(sprintf('Process exited unexpectedly. %s', $process->getCommandLine()));
        }
    }

    public function require(string ...$packages): void
    {
        $this->execute(
            'composer',
            'require',
            '--with-dependencies',
            '--prefer-dist',
            '--no-progress',
            '--prefer-stable',
            '--sort-packages',
            '--optimize-autoloader',
            ...$packages,
        );
    }

    public function minimumStability(string $stability): void
    {
        $this->execute(
            'composer',
            'config',
            'minimum-stability',
            $stability,
        );
    }

    public function init(string $name): void
    {
        $this->execute(
            'composer',
            'init',
            '--no-interaction',
            sprintf('--name=%s', $name),
        );
    }

    public function install(): void
    {
        $this->execute(
            'composer',
            'install',
            '--prefer-dist',
            '--no-progress',
            '--prefer-stable',
            '--sort-packages',
            '--optimize-autoloader',
            '--no-dev',
        );
    }

    public function addGithubRepository(string $name, string $url): void
    {
        $this->execute(
            'composer',
            'config',
            sprintf('repositories.%s', $name),
            'github',
            $url,
        );
    }
}
