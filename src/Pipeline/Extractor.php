<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Pipeline;

use Kiboko\Component\Satellite\Builder\Pipeline;
use Kiboko\Component\Satellite\ExpressionLanguage as Satellite;
use Kiboko\Component\Satellite\Feature\Logger;
use Kiboko\Component\Satellite\Feature\Rejection;
use Kiboko\Component\Satellite\Feature\State;
use Kiboko\Contract\Configurator\StepRepositoryInterface;
use PhpParser\Node;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

final readonly class Extractor implements StepInterface
{
    public function __construct(
        private ?string $plugin,
        private ?string $key,
        private ExpressionLanguage $interpreter = new Satellite\ExpressionLanguage()
    ) {
    }

    public function __invoke(array $config, Pipeline $pipeline, StepRepositoryInterface $repository): void
    {
        if (null !== $this->key
            && (!\array_key_exists($this->plugin, $config) || !\array_key_exists($this->key, $config[$this->plugin]))
        ) {
            return;
        }

        if (\array_key_exists('logger', $config)) {
            $service = new Logger\Service($this->interpreter);

            $compiled = $service->compile($config['logger']);
            $repository->merge($compiled);
            $logger = $compiled->getBuilder()->getNode();
        } else {
            $logger = new Node\Expr\New_(
                new Node\Name\FullyQualified(\Psr\Log\NullLogger::class),
            );
        }

        if (\array_key_exists('rejection', $config)) {
            $service = new Rejection\Service($this->interpreter);

            $compiled = $service->compile($config['rejection']);
            $repository->merge($compiled);
            $rejection = $compiled->getBuilder()->getNode();
        } else {
            $rejection = new Node\Expr\New_(
                new Node\Name\FullyQualified(\Kiboko\Contract\Pipeline\NullRejection::class),
            );
        }

        if (\array_key_exists('state', $config)) {
            $service = new State\Service($this->interpreter);

            $compiled = $service->compile($config['state']);
            $repository->merge($compiled);
            $state = $compiled->getBuilder()->getNode();
        } else {
            $state = new Node\Expr\New_(
                new Node\Name\FullyQualified(\Kiboko\Contract\Pipeline\NullState::class),
            );
        }

        if (array_key_exists('code', $config)) {
            $code = new Node\Scalar\String_($config['code']);
        } else {
            $code = new node\Scalar\String_(sprintf('%s.%s', $this->plugin, $this->key));
        }

        $pipeline->addExtractor(
            $code,
            $repository->getBuilder()
                ->withLogger($logger)
                ->withRejection($rejection)
                ->withState($state),
            $rejection,
            $state,
        );
    }
}
