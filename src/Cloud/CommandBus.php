<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud;

final class CommandBus
{
    public function __construct(
        private array $handlers
    ) {
    }

    public function execute(object $command)
    {
        $commandClass = get_class($command);
        $handler = $this->handlers[$commandClass] ?? throw new \RuntimeException(sprintf('No handler for %s command', $commandClass));

        return $handler($command);
    }
}
