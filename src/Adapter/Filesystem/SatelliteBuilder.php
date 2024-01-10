<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Filesystem;

use Kiboko\Component\Packaging;
use Kiboko\Component\Satellite;
use Kiboko\Contract\Configurator;
use Kiboko\Contract\Packaging as PackagingContract;

final class SatelliteBuilder implements Configurator\SatelliteBuilderInterface
{
    /** @var iterable<string> */
    private iterable $composerRequire = [];
    private array $composerAutoload = [
        'psr4' => [
            'GyroscopsGenerated\\' => './',
        ],
    ];
    private array $authenticationTokens = [];
    private array $repositories = [];
    private null|PackagingContract\AssetInterface|PackagingContract\FileInterface $composerJsonFile = null;
    private null|PackagingContract\AssetInterface|PackagingContract\FileInterface $composerLockFile = null;
    /** @var iterable<array<string, string>> */
    private iterable $paths = [];
    /** @var \AppendIterator<string,PackagingContract\FileInterface, \Iterator<string,PackagingContract\FileInterface>> */
    private readonly iterable $files;

    public function __construct(private string $workdir)
    {
        $this->files = new \AppendIterator();
    }

    public function withWorkdir(string $path): self
    {
        $this->workdir = $path;

        return $this;
    }

    public function withComposerPSR4Autoload(string $namespace, string ...$paths): Configurator\SatelliteBuilderInterface
    {
        $this->composerAutoload['psr4'][$namespace] = $paths;

        return $this;
    }

    public function withComposerRequire(string ...$package): self
    {
        array_push($this->composerRequire, ...$package);

        return $this;
    }

    public function withComposerFile(
        PackagingContract\AssetInterface|PackagingContract\FileInterface $composerJsonFile,
        PackagingContract\AssetInterface|PackagingContract\FileInterface $composerLockFile = null
    ): self {
        $this->composerJsonFile = $composerJsonFile;
        $this->composerLockFile = $composerLockFile;

        return $this;
    }

    public function withFile(
        PackagingContract\AssetInterface|PackagingContract\FileInterface $source,
        string $destinationPath = null
    ): self {
        if (!$source instanceof PackagingContract\FileInterface) {
            $source = new Packaging\VirtualFile($source);
        }

        $this->paths[] = [$source->getPath(), $destinationPath ?? $source->getPath()];

        $this->files->append(new \ArrayIterator([
            new Packaging\File($destinationPath, $source),
        ]));

        return $this;
    }

    public function withDirectory(PackagingContract\DirectoryInterface $source, string $destinationPath = null): self
    {
        $this->paths[] = [$source->getPath(), $destinationPath ?? $source->getPath()];

        $this->files->append(new \RecursiveIteratorIterator($source, \RecursiveIteratorIterator::SELF_FIRST));

        return $this;
    }

    public function withRepositories(string $name, string $type, string $url): self
    {
        $this->repositories[$name] = [
            'type' => $type,
            'url' => $url,
        ];

        return $this;
    }

    public function withAuthenticationToken(string $domain, string $auth): self
    {
        $this->authenticationTokens[$domain] = $auth;

        return $this;
    }

    public function build(): Configurator\SatelliteInterface
    {
        if (!file_exists($this->workdir)) {
            if (!mkdir($concurrentDirectory = $this->workdir, 0o775, true) && !is_dir($concurrentDirectory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
        }

        if (!file_exists($this->workdir.'/.env') && file_exists(getcwd().'/.env')) {
            symlink(getcwd().'/.env', $this->workdir.'/.env');
        }

        $composer = new Satellite\Adapter\Composer($this->workdir);
        $satellite = new Satellite\Adapter\Filesystem\Satellite(
            $this->workdir,
            $composer,
        );

        if (null !== $this->composerJsonFile) {
            $satellite->withFile($this->composerJsonFile);
            if (null !== $this->composerLockFile) {
                $satellite->withFile($this->composerLockFile);
            }
        } else {
            $composer->init(sprintf('satellite/%s', substr(hash('sha512', random_bytes(64)), 0, 64)));
            $composer->minimumStability('dev');

            $composer->autoload($this->composerAutoload);
        }

        if (\count($this->repositories) > 0) {
            foreach ($this->repositories as $name => $repository) {
                if ('composer' === $repository['type']) {
                    $composer->addComposerRepository($name, $repository['url']);
                }

                if ('vcs' === $repository['type']) {
                    $composer->addVCSRepository($name, $repository['url']);
                }

                if ('github' === $repository['type']) {
                    $composer->addGithubRepository($name, $repository['url']);
                }
            }
        }

        if (\count($this->authenticationTokens) > 0) {
            foreach ($this->authenticationTokens as $url => $token) {
                $composer->addAuthenticationToken($url, $token);
            }
        }

        $satellite->dependsOn(...$this->composerRequire);

        $this->clearPreviousFiles();

        return $satellite;
    }

    private function clearPreviousFiles(): void
    {
        $iterator = new \AppendIterator();

        $iterator->append(new \GlobIterator($this->workdir.'/pipeline*.php', \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS));
        $iterator->append(new \GlobIterator($this->workdir.'/action*.php', \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS));
        $iterator->append(new \GlobIterator($this->workdir.'/ProjectServiceContainer*.php', \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if (is_file($file->getPathname())) {
                unlink($file->getPathname());
            }
        }
    }
}
