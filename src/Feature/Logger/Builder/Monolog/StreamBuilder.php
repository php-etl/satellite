<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\Logger\Builder\Monolog;

use PhpParser\Node;

final class StreamBuilder implements MonologBuilderInterface
{
    private ?string $level;
    private ?int $filePermissions;
    private ?bool $useLocking;
    private iterable $formatters;

    public function __construct(private string $path)
    {
        $this->level = null;
        $this->formatters = [];
        $this->filePermissions = 0755;
        $this->useLocking = false;
    }

    public function withLevel(string $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function withFilePermissions(int $filePermissions): self
    {
        $this->filePermissions = $filePermissions;

        return $this;
    }

    public function withLocking(bool $useLocking): self
    {
        $this->useLocking = $useLocking;

        return $this;
    }

    public function withFormatters(Node\Expr ...$formatters): self
    {
        array_push($this->formatters, ...$formatters);

        return $this;
    }

    public function getNode(): \PhpParser\Node\Expr
    {
        $arguments = [
            new Node\Arg(
                value: new Node\Scalar\String_($this->path),
                name: new Node\Identifier('stream'),
            ),
        ];

        if ($this->level !== null) {
            $arguments[] = new Node\Arg(
                value: new Node\Scalar\String_($this->level),
                name: new Node\Identifier('level'),
            );
        }

        if ($this->filePermissions !== null) {
            $arguments[] = new Node\Arg(
                value: new Node\Scalar\LNumber($this->filePermissions, ['kind' => Node\Scalar\LNumber::KIND_OCT]),
                name: new Node\Identifier('filePermission'),
            );
        }

        if ($this->useLocking !== null) {
            $arguments[] = new Node\Arg(
                value: new Node\Expr\ConstFetch(new Node\Name($this->useLocking ? 'true' : 'false')),
                name: new Node\Identifier('useLocking'),
            );
        }

        $instance = new Node\Expr\New_(
            class: new Node\Name\FullyQualified('Monolog\\Handler\\StreamHandler'),
            args: $arguments,
        );

        foreach ($this->formatters as $formatter) {
            $instance = new Node\Expr\MethodCall(
                var: $instance,
                name: new Node\Identifier('setFormatter'),
                args: [
                    new Node\Arg($formatter),
                ],
            );
        }

        return $instance;
    }
}
