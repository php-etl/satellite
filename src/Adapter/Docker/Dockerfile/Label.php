<?php declare(strict_types=1);

namespace Kiboko\Component\ETL\Satellite\Adapter\Docker\Dockerfile;

final class Label implements LayerInterface
{
    private string $key;
    private string $value;

    public function __construct(string $key, string $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    public function __toString()
    {
        return sprintf('LABEL %s="%s"', $this->key, $this->value);
    }
}