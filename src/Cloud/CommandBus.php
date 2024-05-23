<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud;

use Gyroscops\Api\Client;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

final class CommandBus
{
    /** @var array<int, array{ 0: object, 1: Deferred }> */
    private array $commands = [];

    public function __construct(
        private readonly array $handlers
    ) {
    }

    public static function withStandardHandlers(Client $client): self
    {
        return new self([
            Command\Pipeline\DeclarePipelineCommand::class => new Handler\Pipeline\DeclarePipelineCommandHandler($client),
            Command\Pipeline\AddPipelineComposerPSR4AutoloadCommand::class => new Handler\Pipeline\AddPipelineComposerPSR4AutoloadCommandHandler($client),
            Command\Pipeline\AppendPipelineStepCommand::class => new Handler\Pipeline\AppendPipelineStepCommandHandler($client),
            Command\Pipeline\RemovePipelineCommand::class => new Handler\Pipeline\RemovePipelineCommandHandler($client),
            Command\Pipeline\AddAfterPipelineStepCommand::class => new Handler\Pipeline\AddAfterPipelineStepCommandHandler($client),
            Command\Pipeline\AddBeforePipelineStepCommand::class => new Handler\Pipeline\AddBeforePipelineStepCommandHandler($client),
            Command\Pipeline\ReplacePipelineStepCommand::class => new Handler\Pipeline\ReplacePipelineStepCommandHandler($client),
            Command\Pipeline\RemovePipelineStepCommand::class => new Handler\Pipeline\RemovePipelineStepCommandHandler($client),
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
