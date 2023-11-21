<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use React\ChildProcess\Process;
use React\Promise\Deferred;
use function React\Async\await;
use function React\Promise\Timer\timeout;

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

    private function execute(Process $process, float $timeout = 300): void
    {
        $process->start();

        $process->stdout->on('data', function ($chunk) {
            $this->logger->debug($chunk);
        });
        $process->stderr->on('data', function ($chunk) {
            $this->logger->info($chunk);
        });

        $deferred = new Deferred();

        $process->on('exit', function () use ($deferred) {
            $deferred->resolve();
        });

        $this->logger->notice(sprintf('Starting process "%s".', $process->getCommand()));

        await(timeout($deferred->promise(), $timeout));

        if (0 !== $process->getExitCode()) {
            throw new ComposerFailureException($process->getCommand(), sprintf('Process exited unexpectedly with output: %s', $process->getExitCode()), $process->getExitCode());
        }
    }

    private function command(string ...$command): void
    {
        $process = new Process(
            implode (' ', array_map(fn ($part) => escapeshellarg($part), $command)),
            $this->workdir,
        );

        $this->execute($process);
    }

    public function require(string ...$packages): void
    {
        $this->command(
            'composer',
            'require',
            '--with-dependencies',
            '--with-all-dependencies',
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
            'dir' === $file->getType() ? rmdir($file->getPathname()) : unlink($file->getPathname());
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
            '--require=php:^8.2',
        );

        $this->allowPlugins('php-http/discovery');
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
        $composer = json_decode(file_get_contents($this->workdir.'/composer.json'), true, 512, \JSON_THROW_ON_ERROR);
        foreach ($autoloads as $type => $autoload) {
            match ($type) {
                'psr4' => $composer['autoload']['psr-4'] = $autoload,
                'file' => $composer['autoload']['file'] = $autoload,
            };
        }
        file_put_contents($this->workdir.'/composer.json', json_encode($composer, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES));
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
