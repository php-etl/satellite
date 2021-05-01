<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Pipeline;

use Kiboko\Component\Satellite\Builder\Pipeline;
use Kiboko\Component\Satellite\Feature\Logger;
use Kiboko\Component\Satellite\Feature\Rejection;
use Kiboko\Component\Satellite\Feature\State;
use Kiboko\Contract\Configurator\StepRepositoryInterface;
use PhpParser\Node;

final class Loader implements StepInterface
{
    public function __construct(private ?string $plugin, private ?string $key)
    {}

    public function __invoke(array $config, Pipeline $pipeline, StepRepositoryInterface $repository): void
    {
        if ($this->key !== null
            && !array_key_exists($this->plugin, $config)
            && !array_key_exists($this->key, $config[$this->plugin])
        ) {
            return;
        }

        if (array_key_exists('logger', $config)) {
            $service = new Logger\Service();

            $compiled = $service->compile($config['logger']);
            $repository->merge($compiled);
            $logger = $compiled->getBuilder()->getNode();
        } else {
            $logger = new Node\Expr\New_(
                new Node\Name\FullyQualified('Psr\Log\NullLogger'),
            );
        }

        if (array_key_exists('rejection', $config)) {
            $service = new Rejection\Service();

            $compiled = $service->compile($config['rejection']);
            $repository->merge($compiled);
            $rejection = $compiled->getBuilder()->getNode();
        } else {
            $rejection = new Node\Expr\New_(
                new Node\Name\FullyQualified('Kiboko\Contract\Pipeline\NullRejection'),
            );
        }

        if (array_key_exists('state', $config)) {
            $service = new State\Service();

            $compiled = $service->compile($config['state']);
            $repository->merge($compiled);
            $state = $compiled->getBuilder()->getNode();
        } else {
            $state = new Node\Expr\New_(
                new Node\Name\FullyQualified('Kiboko\Contract\Pipeline\NullState'),
            );
        }

        $pipeline->addLoader(
            $repository->getBuilder()
                ->withLogger($logger)
                ->withRejection($rejection)
                ->withState($state),
            $rejection,
            $state,
        );
    }
}
