<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud;

final class CommandBus
{
    public function __construct(
        private array $handlers
    ) {
    }

    public function execute(object $command): Result
    {
        $commandClass = get_class($command);
        $handler = $this->handlers[$commandClass] ?? throw new \RuntimeException("No handler for $commandClass command.");

        return $handler($command);
    }
}
