<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Event;

final class AddedPipelineComposerPSR4Autoload
{
    public function __construct(
        private string $id,
        private string $namespace,
        private array $paths,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function getPaths(): array
    {
        return $this->paths;
    }
}
