<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Pipeline;

use Kiboko\Component\Satellite\Builder\Repository\Pipeline;
use Kiboko\Contract\Configurator\FactoryInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

final class ConfigurationApplier
{
    private array $steps = [];
    private array $packages = [];

    public function __construct(
        private string $plugin,
        private FactoryInterface $service,
        private ExpressionLanguage $interpreter,
    ) {
    }

    public function withExtractor(?string $key = 'extractor'): self
    {
        $this->steps[] = new Extractor($this->plugin, $key, $this->interpreter);

        return $this;
    }

    public function withTransformer(?string $key = 'transformer'): self
    {
        $this->steps[] = new Transformer($this->plugin, $key, $this->interpreter);

        return $this;
    }

    public function withLoader(?string $key = 'loader'): self
    {
        $this->steps[] = new Loader($this->plugin, $key, $this->interpreter);

        return $this;
    }

    public function withPackages(string ...$packages): self
    {
        array_push($this->packages, ...$packages);

        return $this;
    }

    public function appendTo(array $config, Pipeline $pipeline): void
    {
        $repository = $this->service->compile($config[$this->plugin]);

        /** @var StepInterface $step */
        foreach ($this->steps as $step) {
            $step($config, $pipeline->getBuilder(), $repository);
        }

        $pipeline->addPackages(...$this->packages);

        $pipeline->merge($repository);
    }
}
