<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\DTO;

final class Pipeline
{
    public function __construct(
        private string $code,
        private string $name,
        private StepList $steps,
    ) {}

    public function code(): string
    {
        return $this->code;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function steps(): StepList
    {
        return $this->steps;
    }
}
