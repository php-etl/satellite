<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Pipeline;

use Kiboko\Component\Satellite\Builder\Pipeline;
use Kiboko\Component\Satellite\Feature\Logger;
use Kiboko\Component\Satellite\Feature\Rejection;
use Kiboko\Component\Satellite\Feature\State;
use Kiboko\Contract\Configurator\StepRepositoryInterface;
use PhpParser\Node;

final class Transformer implements StepInterface
{
    public function __construct(private ?string $key)
    {}

    public function __invoke(array $config, Pipeline $pipeline, StepRepositoryInterface $repository): void
    {
        if ($this->key !== null && !array_key_exists($this->key, $config)) {
            return;
        }

        if (array_key_exists('logger', $config)) {
            $service = new Logger\Service();

            $compiled = $service->compile($config['logger']);
            $repository->merge($compiled);
            $logger = $compiled->getBuilder();
        } else {
            $logger = new Node\Expr\New_(
                new Node\Name\FullyQualified('Psr\Log\NullLogger'),
            );
        }

        if (array_key_exists('rejection', $config)) {
            $service = new Rejection\Service();

            $compiled = $service->compile($config['rejection']);
            $repository->merge($compiled);
            $rejection = $compiled->getBuilder();
        } else {
            $rejection = new Node\Expr\New_(
                new Node\Name\FullyQualified('Kiboko\Contract\Pipeline\NullRejection'),
            );
        }

        if (array_key_exists('state', $config)) {
            $service = new State\Service();

            $compiled = $service->compile($config['state']);
            $repository->merge($compiled);
            $state = $compiled->getBuilder();
        } else {
            $state = new Node\Expr\New_(
                new Node\Name\FullyQualified('Kiboko\Contract\Pipeline\NullState'),
            );
        }

        $pipeline->addTransformer(
            $repository->getBuilder()
                ->withLogger($logger)
                ->withRejection($rejection)
                ->withState($state),
            $rejection,
            $state,
        );
    }
}
