<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud;

use Gyroscops\Api\Client;
use Kiboko\Component\Satellite;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

final class CommandBus
{
    /** @var array<int, array{ 0: object, 1: Deferred }> */
    private array $commands = [];

    public function __construct(
        private readonly array $handlers
    ) {}

    public static function withStandardHandlers(Client $client): self
    {
        return new self([
            Satellite\Cloud\Command\Pipeline\DeclarePipelineCommand::class => new Satellite\Cloud\Handler\Pipeline\DeclarePipelineCommandHandler($client),
            Satellite\Cloud\Command\Pipeline\AddPipelineComposerPSR4AutoloadCommand::class => new Satellite\Cloud\Handler\Pipeline\AddPipelineComposerPSR4AutoloadCommandHandler($client),
            Satellite\Cloud\Command\Pipeline\AppendPipelineStepCommand::class => new Satellite\Cloud\Handler\Pipeline\AppendPipelineStepCommandHandler($client),
            Satellite\Cloud\Command\Pipeline\RemovePipelineCommand::class => new Satellite\Cloud\Handler\Pipeline\RemovePipelineCommandHandler($client),
            Satellite\Cloud\Command\Pipeline\AddAfterPipelineStepCommand::class => new Satellite\Cloud\Handler\Pipeline\AddAfterPipelineStepCommandHandler($client),
            Satellite\Cloud\Command\Pipeline\AddBeforePipelineStepCommand::class => new Satellite\Cloud\Handler\Pipeline\AddBeforePipelineStepCommandHandler($client),
            Satellite\Cloud\Command\Pipeline\ReplacePipelineStepCommand::class => new Satellite\Cloud\Handler\Pipeline\ReplacePipelineStepCommandHandler($client),
            Satellite\Cloud\Command\Pipeline\RemovePipelineStepCommand::class => new Satellite\Cloud\Handler\Pipeline\RemovePipelineStepCommandHandler($client),
            Satellite\Cloud\Command\Workflow\DeclareWorkflowCommand::class => new Satellite\Cloud\Handler\Workflow\DeclareWorkflowCommandHandler($client),
            Satellite\Cloud\Command\Workflow\RemoveWorkflowCommand::class => new Satellite\Cloud\Handler\Workflow\RemoveWorkflowCommandHandler($client),
        ]);
    }

    public function push(object $command): PromiseInterface
    {
        $deferred = new Deferred();
        $this->commands[] = [$command, $deferred];

        return $deferred->promise();
    }

    public function execute(): void
    {
        $commands = $this->commands;
        $this->commands = [];

        /**
         * @var object   $command
         * @var Deferred $deferred
         */
        foreach ($commands as [$command, $deferred]) {
            try {
                $commandClass = $command::class;
                $handler = $this->handlers[$commandClass] ?? throw new \RuntimeException(sprintf('No handler for %s command', $commandClass));

                $deferred->resolve($handler($command));
            } catch (\Throwable $exception) {
                $deferred->reject($exception);
            }
        }
    }
}
