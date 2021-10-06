<?php

namespace Kiboko\Component\Satellite\Feature\State\Builder;

use PhpParser\Builder;
use PhpParser\Node;

class RabbitMQBuilder implements Builder
{
    private ?Node\Expr $user = null;
    private ?Node\Expr $password = null;
    private ?Node\Expr $exchange = null;
    private ?Node\Expr $stepCode = null;
    private ?Node\Expr $stepName = null;
    private ?Node\Expr $lineThreshold = null;

    public function __construct(
        public Node\Expr $host,
        public Node\Expr $port,
        public Node\Expr $vhost,
        public Node\Expr $topic,
    ) {
    }

    public function withAuthentication(Node\Expr $user, Node\Expr $password): self
    {
        $this->user = $user;
        $this->password = $password;

        return $this;
    }

    public function withLineThreshold(Node\Expr $lineThreshold): self
    {
        $this->lineThreshold = $lineThreshold;

        return $this;
    }

    public function withExchange(Node\Expr $exchange): self
    {
        $this->exchange = $exchange;

        return $this;
    }

    public function withStepInfo(Node\Expr $stepName, Node\Expr $stepCode): self
    {
        $this->stepName = $stepName;
        $this->stepCode = $stepCode;

        return $this;
    }

    public function getNode(): Node\Expr
    {
        return new Node\Expr\New_(
            class: new Node\Name\FullyQualified('Kiboko\Component\Flow\RabbitMQ\State'),
            args: [
                new Node\Arg(
                    value: new Node\Expr\New_(
                        class: new Node\Name\FullyQualified('Kiboko\Component\Flow\RabbitMQ\StateManager'),
                        args: array_filter([
                            new Node\Arg(
                                value: new Node\Expr\MethodCall(
                                    var: new Node\Expr\New_(
                                        class: new Node\Name\FullyQualified('Bunny\Client'),
                                        args: [
                                            new Node\Arg(
                                                value: new Node\Expr\Array_(
                                                    items: [
                                                        new Node\Expr\ArrayItem(
                                                            value: $this->host,
                                                            key: new Node\Scalar\String_('host')
                                                        ),
                                                        new Node\Expr\ArrayItem(
                                                            value: $this->port,
                                                            key: new Node\Scalar\String_('port')
                                                        ),
                                                        new Node\Expr\ArrayItem(
                                                            value: $this->vhost,
                                                            key: new Node\Scalar\String_('vhost')
                                                        ),
                                                        new Node\Expr\ArrayItem(
                                                            value: $this->user,
                                                            key: new Node\Scalar\String_('user')
                                                        ),
                                                        new Node\Expr\ArrayItem(
                                                            value: $this->password,
                                                            key: new Node\Scalar\String_('password')
                                                        ),
                                                    ],
                                                    attributes: [
                                                        'kind' => Node\Expr\Array_::KIND_SHORT
                                                    ]
                                                ),
                                            ),
                                        ],
                                    ),
                                    name: new Node\Name('connect')
                                )
                            ),
                            new Node\Arg(
                                $this->topic
                            ),
                            $this->lineThreshold ? new Node\Arg(
                                $this->lineThreshold
                            ): null,
                            $this->exchange ? new Node\Arg(
                                $this->exchange
                            ) : null,
                        ])
                    ),
                ),
                new Node\Arg(
                    value: $this->stepCode
                ),
                new Node\Arg(
                    value: $this->stepName
                ),
            ],
        );
    }
}
