<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\Logger\Builder\Monolog;

use PhpParser\Node;

final class SyslogBuilder implements MonologBuilderInterface
{
    private ?string $level = null;
    private ?int $facility = null;
    private ?int $logopts = null;
    private iterable $formatters = [];

    public function __construct(private readonly string $ident) {}

    public function withLevel(string $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function withFacility(int $facility): self
    {
        $this->facility = $facility;

        return $this;
    }

    public function withLogopts(int $logopts): self
    {
        $this->logopts = $logopts;

        return $this;
    }

    public function withFormatters(Node\Expr ...$formatters): self
    {
        array_push($this->formatters, ...$formatters);

        return $this;
    }

    public function getNode(): Node\Expr
    {
        $arguments = [
            new Node\Arg(
                value: new Node\Scalar\String_($this->ident),
                name: new Node\Identifier('ident'),
            ),
        ];

        if (null !== $this->level) {
            $arguments[] = new Node\Arg(
                value: new Node\Scalar\String_($this->level),
                name: new Node\Identifier('level'),
            );
        }

        if (null !== $this->facility) {
            $arguments[] = new Node\Arg(
                value: new Node\Scalar\LNumber($this->facility),
                name: new Node\Identifier('facility'),
            );
        }

        if (null !== $this->logopts) {
            $arguments[] = new Node\Arg(
                value: new Node\Scalar\LNumber($this->logopts),
                name: new Node\Identifier('logopts'),
            );
        }

        $instance = new Node\Expr\New_(
            class: new Node\Name\FullyQualified('Monolog\\Handler\\SyslogHandler'),
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
