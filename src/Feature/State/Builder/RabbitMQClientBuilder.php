<?php

namespace Kiboko\Component\Satellite\Feature\State\Builder;

use PhpParser\Builder;
use PhpParser\Node;

class RabbitMQClientBuilder implements Builder
{
    public function __construct(
        private Node\Expr $host,
        private Node\Expr $vhost,
        private Node\Expr $user,
        private Node\Expr $password,
        private Node\Expr $port,
    ) {
    }

    public function getNode(): Node\Expr
    {
        return new Node\Expr\Assign(
            var: new Node\Expr\Variable('connection'),
            expr: new Node\Expr\New_(
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
                            ],
                        ),
                    ),
                ],
            ),
        );
    }
}
