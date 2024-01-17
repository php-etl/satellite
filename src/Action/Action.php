<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Action;

use Kiboko\Component\Satellite\Action\SFTP\Builder\ActionBuilderInterface;
use Kiboko\Component\Satellite\Builder\Action as ActionBuilder;
use Kiboko\Component\Satellite\ExpressionLanguage as Satellite;
use Kiboko\Component\Satellite\Feature\Logger;
use Kiboko\Component\Satellite\Feature\State;
use Kiboko\Contract\Configurator\RepositoryInterface;
use PhpParser\Node;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

final readonly class Action
{
    public function __construct(private ?string $plugin, private ExpressionLanguage $interpreter = new Satellite\ExpressionLanguage()) {}

    public function __invoke(array $config, ActionBuilder $action, RepositoryInterface $repository): void
    {
        if (!\array_key_exists($this->plugin, $config)) {
            return;
        }

        if (\array_key_exists('logger', $config)) {
            $service = new Logger\Service($this->interpreter);

            $compiled = $service->compile($config['logger']);
            $repository->merge($compiled);
            $logger = $compiled->getBuilder()->getNode();
        } else {
            $logger = new Node\Expr\New_(
                new Node\Name\FullyQualified('Psr\\Log\\NullLogger'),
            );
        }

        if (\array_key_exists('state', $config)) {
            $service = new State\Service($this->interpreter);

            $compiled = $service->compile($config['state']);
            $repository->merge($compiled);
            $state = $compiled->getBuilder()->getNode();
        } else {
            $state = new Node\Expr\New_(
                new Node\Name\FullyQualified('Kiboko\\Contract\\Action\\NullState'),
            );
        }

        /** @var ActionBuilderInterface $builder */
        $builder = $repository->getBuilder();

        $action->addAction(
            $builder
                ->withLogger($logger)
                ->withState($state),
            $state,
        );
    }
}
