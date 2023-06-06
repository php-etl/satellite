<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

final class Composer
{
    public function __construct(private readonly string $workdir, private ?LoggerInterface $logger = null)
    {
        $this->logger ??= new class() extends AbstractLogger {
            public function log($level, $message, array $context = []): void
            {
                $prefix = sprintf(\PHP_EOL.'[%s] ', strtoupper((string) $level));
                fwrite(\STDERR, $prefix.str_replace(\PHP_EOL, $prefix, rtrim($message, \PHP_EOL)));
            }
        };
    }

    private function execute(Process $process): void
    {
        $process->run(function ($type, $buffer): void {
            if (Process::ERR === $type) {
                $this->logger->info($buffer);
            } else {
                $this->logger->debug($buffer);
            }
        });

        if (0 !== $process->getExitCode()) {
            throw new ComposerFailureException($process->getCommandLine(), sprintf('Process exited unexpectedly with output: %s', $process->getErrorOutput()), $process->getExitCode());
        }
    }

    private function command(string ...$command): void
    {
        $process = new Process($command);
        $process->setWorkingDirectory($this->workdir);

        $process->setTimeout(300);

        $this->execute($process);
    }

    private function pipe(Process ...$processes): void
    {
        $process = Process::fromShellCommandline(implode('|', array_map(fn (Process $process) => $process->getCommandLine(), $processes)));
        $process->setWorkingDirectory($this->workdir);

        $process->setTimeout(300);

        $this->execute($process);
    }

    private function subcommand(string ...$command): Process
    {
        return new Process($command);
    }

    public function require(string ...$packages): void
    {
        $this->command(
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
        $this->command(
            'composer',
            'config',
            'minimum-stability',
            $stability,
        );
    }

    private function clearVendor(): void
    {
        $iterator = new \AppendIterator();

        try {
            $iterator->append(new \GlobIterator($this->workdir.'/composer.*', \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::KEY_AS_PATHNAME));
            $iterator->append(new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $this->workdir.'/vendor',
                    \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS
                ),
                \RecursiveIteratorIterator::CHILD_FIRST,
            ));
        } catch (\UnexpectedValueException $e) {
            $this->logger->warning($e->getMessage());
        }

        foreach ($iterator as $file) {
            $file->getType() === 'dir' ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
    }

    public function init(string $name): void
    {
        if (file_exists($this->workdir.'/composer.json')) {
            if (filesize($this->workdir.'/composer.json') <= 2) {
                $this->clearVendor();
            } else {
                try {
                    $this->allowPlugins('php-http/discovery');

                    return;
                } catch (ComposerFailureException) {
                    $this->clearVendor();
                }
            }
        }

        $this->command(
            'composer',
            'init',
            '--no-interaction',
            sprintf('--name=%s', $name),
        );

        $this->allowPlugins('php-http/discovery');
    }

    public function forcePlugins(string ...$plugins): void
    {
        foreach ($plugins as $packageName) {
            $this->command(
                'composer',
                'config',
                sprintf('allow-plugins.%s', $packageName),
                'true',
            );
        }
    }

    public function allowPlugins(string ...$plugins): void
    {
        foreach ($plugins as $packageName) {
            $this->command(
                'composer',
                'config',
                sprintf('allow-plugins.%s', $packageName),
                'true',
            );
        }
    }

    public function denyPlugins(string ...$plugins): void
    {
        foreach ($plugins as $packageName) {
            $this->command(
                'composer',
                'config',
                sprintf('allow-plugins.%s', $packageName),
                'false',
            );
        }
    }

    /**
     * @param array<string, array<string, string|list<string>>> $autoloads
     */
    public function autoload(array $autoloads): void
    {
        foreach ($autoloads as $type => $autoload) {
            match ($type) {
                'psr4' => $this->pipe(
                    $this->subcommand('cat', 'composer.json'),
                    $this->subcommand('jq', '--indent', '4', sprintf('.autoload."psr-4" |= . + %s', json_encode($autoload, \JSON_THROW_ON_ERROR))),
                    $this->subcommand('tee', 'composer.json'),
                ),
                'file' => $this->pipe(
                    $this->subcommand('cat', 'composer.json'),
                    $this->subcommand('jq', '--indent', '4', sprintf('.autoload."file" |= . + %s', json_encode($autoload, \JSON_THROW_ON_ERROR))),
                    $this->subcommand('tee', 'composer.json'),
                )
            };
        }
    }

    public function install(): void
    {
        $this->command(
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
        $this->command(
            'composer',
            'config',
            sprintf('repositories.%s', $name),
            'github',
            $url,
        );
    }

    public function addComposerRepository(string $name, string $url): void
    {
        $this->command(
            'composer',
            'config',
            sprintf('repositories.%s', $name),
            'composer',
            $url,
        );
    }

    public function addVCSRepository(string $name, string $url): void
    {
        $this->command(
            'composer',
            'config',
            sprintf('repositories.%s', $name),
            'vcs',
            $url,
        );
    }

    public function addAuthenticationToken(string $url, string $token): void
    {
        $this->command(
            'composer',
            'config',
            '--auth',
            $url,
            'token',
            $token
        );
    }
}
