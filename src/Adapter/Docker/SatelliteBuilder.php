<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Docker;

use Kiboko\Component\Dockerfile;
use Kiboko\Component\Packaging;
use Kiboko\Component\Satellite;
use Kiboko\Contract\Configurator;
use Kiboko\Contract\Packaging as PackagingContract;

final class SatelliteBuilder implements Configurator\SatelliteBuilderInterface
{
    private string $workdir = '/var/www/html/';
    /** @var iterable<string> */
    private iterable $composerRequire = [];
    private iterable $repositories = [];
    private iterable $authenticationTokens = [];
    /** @var iterable<string> */
    private iterable $entrypoint = [];
    /** @var iterable<string> */
    private iterable $command = [];
    /** @var iterable<string> */
    private iterable $tags = [];
    private null|PackagingContract\FileInterface|PackagingContract\AssetInterface $composerJsonFile = null;
    private null|PackagingContract\FileInterface|PackagingContract\AssetInterface $composerLockFile = null;
    /** @var iterable<array<string, string>> */
    private iterable $paths = [];
    /** @var \AppendIterator<string,PackagingContract\FileInterface, \Iterator<string,PackagingContract\FileInterface>> */
    private readonly iterable $files;
    /** @var array<string, array<string, string>> */
    private array $composerAutoload = [
        'psr4' => [
            'GyroscopsGenerated\\' => './',
        ],
    ];

    public function __construct(private readonly string $fromImage)
    {
        $this->files = new \AppendIterator();
    }

    public function withWorkdir(string $path): self
    {
        $this->workdir = $path;

        return $this;
    }

    public function withComposerPSR4Autoload(string $namespace, string ...$paths): self
    {
        if (!\array_key_exists('psr4', $this->composerAutoload)) {
            $this->composerAutoload['psr4'] = [];
        }
        $this->composerAutoload['psr4'][$namespace] = $paths;

        return $this;
    }

    public function withComposerRequire(string ...$package): self
    {
        array_push($this->composerRequire, ...$package);

        return $this;
    }

    public function withComposerFile(
        PackagingContract\FileInterface|PackagingContract\AssetInterface $composerJsonFile,
        null|PackagingContract\FileInterface|PackagingContract\AssetInterface $composerLockFile = null
    ): self {
        $this->composerJsonFile = $composerJsonFile;
        $this->composerLockFile = $composerLockFile;

        return $this;
    }

    public function withFile(
        PackagingContract\FileInterface|PackagingContract\AssetInterface $source,
        null|string $destinationPath = null
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

    public function withDirectory(PackagingContract\DirectoryInterface $source, null|string $destinationPath = null): self
    {
        $this->paths[] = [$source->getPath(), $destinationPath ?? $source->getPath()];

        $this->files->append(new \RecursiveIteratorIterator($source, \RecursiveIteratorIterator::SELF_FIRST));

        return $this;
    }

    public function withEntrypoint(string ...$entrypoint): self
    {
        $this->entrypoint = $entrypoint;

        return $this;
    }

    public function withCommand(string ...$command): self
    {
        $this->command = $command;

        return $this;
    }

    public function withComposerRepositories(string $name, string $type, string $url): self
    {
        $this->repositories[$name] = [
            'type' => $type,
            'url' => $url,
        ];

        return $this;
    }

    public function withComposerAuthenticationToken(string $url, string $auth): self
    {
        $this->authenticationTokens[$url] = $auth;

        return $this;
    }

    public function withTags(string ...$tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    public function build(): Configurator\SatelliteInterface
    {
        $dockerfile = new Dockerfile\Dockerfile(
            new Dockerfile\Dockerfile\From($this->fromImage),
            new Dockerfile\Dockerfile\Workdir($this->workdir),
        );

        foreach ($this->paths as [$from, $to]) {
            $dockerfile->push(new Dockerfile\Dockerfile\Copy($from, $to));
        }

        if (null !== $this->composerJsonFile) {
            $dockerfile->push(new Dockerfile\Dockerfile\Copy('composer.json', 'composer.json'));
            $this->files->append(new \ArrayIterator([
                new Packaging\File('composer.json', $this->composerJsonFile),
            ]));

            if (null !== $this->composerLockFile) {
                $dockerfile->push(new Dockerfile\Dockerfile\Copy('composer.json', 'composer.lock'));
                $this->files->append(new \ArrayIterator([
                    new Packaging\File('composer.lock', $this->composerLockFile),
                ]));
            }

            $dockerfile->push(new Dockerfile\PHP\Composer());
            $dockerfile->push(new Dockerfile\PHP\ComposerInstall());
        } else {
            $dockerfile->push(new Dockerfile\PHP\Composer());
            $dockerfile->push(new Dockerfile\PHP\ComposerInit(sprintf('satellite/%s', substr(hash('sha512', random_bytes(64)), 0, 64))));
            $dockerfile->push(new Dockerfile\PHP\ComposerMinimumStability('dev'));
            if (\array_key_exists('psr4', $this->composerAutoload)
                && \is_array($this->composerAutoload['psr4'])
                && \count($this->composerAutoload['psr4']) > 0
            ) {
                $dockerfile->push(new Dockerfile\PHP\ComposerAutoload($this->composerAutoload));
            }
        }

        if (\count($this->entrypoint) > 0) {
            $dockerfile->push(new Dockerfile\Dockerfile\Entrypoint(...$this->entrypoint));
        }

        if (\count($this->command) > 0) {
            $dockerfile->push(new Dockerfile\Dockerfile\Cmd(...$this->command));
        }

        if (\count($this->repositories) > 0) {
            foreach ($this->repositories as $name => $repository) {
                if ('github' === $repository['type']) {
                    $dockerfile->push(new Dockerfile\PHP\ComposerAddGithubRepository($name, $repository['url']));
                }

                if ('vcs' === $repository['type']) {
                    $dockerfile->push(new Dockerfile\PHP\ComposerAddVcsRepository($name, $repository['url']));
                }

                if ('composer' === $repository['type']) {
                    $dockerfile->push(new Dockerfile\PHP\ComposerAddComposerRepository($name, $repository['url']));
                }
            }
        }

        if (\count($this->authenticationTokens) > 0) {
            foreach ($this->authenticationTokens as $url => $token) {
                $dockerfile->push(new Dockerfile\PHP\ComposerAuthenticationToken($url, $token));
            }
        }

        if (\count($this->composerRequire) > 0) {
            $dockerfile->push(new Dockerfile\PHP\ComposerRequire(...$this->composerRequire));
        }

        $satellite = new Satellite\Adapter\Docker\Satellite(
            $dockerfile,
            $this->workdir,
            ...$this->files
        );

        $satellite->addTags(...$this->tags);

        return $satellite;
    }
}
