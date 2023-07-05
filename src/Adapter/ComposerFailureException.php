<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter;

final class ComposerFailureException extends \RuntimeException
{
    public function __construct(private readonly string $command = '', string $message = '', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getCommand(): string
    {
        return $this->command;
    }
}
