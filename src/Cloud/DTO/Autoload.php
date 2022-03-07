<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\DTO;

final class Autoload
{
    public array $autoloads;

    public function __construct(
        PSR4AutoloadConfig ...$autoloads,
    ) {
        $this->autoloads = $autoloads;
    }
}
