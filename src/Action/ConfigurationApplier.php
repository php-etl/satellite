<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Action;

use Kiboko\Contract\Configurator\FactoryInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Kiboko\Component\Satellite;

final class ConfigurationApplier
{
    private ?Action $action = null;
    private array $packages = [];

    public function __construct(
        private string $plugin,
        private FactoryInterface $service,
        private ExpressionLanguage $interpreter,
    ) {
    }

    public function withAction(): self
    {
        $this->action = new Action($this->plugin, clone $this->interpreter);

        return $this;
    }

    public function withPackages(string ...$packages): self
    {
        array_push($this->packages, ...$packages);

        return $this;
    }

    public function appendTo(array $config, Satellite\Builder\Repository\Action $action): void
    {
        $repository = $this->service->compile($config[$this->plugin]);

        ($this->action)($config, $action->getBuilder(), $repository);

        $action->addPackages(...$this->packages);

        $action->merge($repository);
    }
}
