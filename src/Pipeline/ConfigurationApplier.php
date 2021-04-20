<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Pipeline;

use Kiboko\Component\Satellite\Builder\Repository\Pipeline;
use Kiboko\Contract\Configurator\FactoryInterface;

final class ConfigurationApplier
{
    private array $steps = [];
    private array $packages = [];

    public function __construct(private FactoryInterface $service)
    {}

    public function withExtractor(?string $key = 'extractor'): self
    {
        $this->steps[] = new Extractor($key);

        return $this;
    }

    public function withTransformer(?string $key = 'transformer'): self
    {
        $this->steps[] = new Transformer($key);

        return $this;
    }

    public function withLoader(?string $key = 'loader'): self
    {
        $this->steps[] = new Loader($key);

        return $this;
    }

    public function withPackages(string ...$packages): self
    {
        array_push($this->packages, ...$packages);

        return $this;
    }

    public function appendTo(array $config, Pipeline $pipeline): void
    {
        $repository = $this->service->compile($config);

        /** @var StepInterface $step */
        foreach ($this->steps as $step) {
            $step($config, $pipeline->getBuilder(), $repository);
        }

        $pipeline->addPackages(...$this->packages);

        $pipeline->merge($repository);
    }
}
