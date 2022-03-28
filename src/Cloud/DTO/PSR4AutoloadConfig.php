<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\DTO;

final class PSR4AutoloadConfig
{
    public array $paths;

    public function __construct(
        public string $namespace,
        string ...$paths,
    ) {
        $this->paths = $paths;
    }
}
