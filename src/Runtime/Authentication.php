<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Runtime;

use PhpParser\Node;

final class Authentication
{
    public function build(): Node\Expr\New_
    {
        if (getenv('JWT_SECRET')) {
            return new Node\Expr\New_(
                new Node\Name('Tuupola\\Middleware\\JwtAuthentication'),
                [
                    new Node\Arg(
                        value: new Node\Expr\Array_(
                            items: [
                                new Node\Expr\ArrayItem(
                                    value: new Node\Scalar\String_(
                                        getenv('JWT_SECRET')
                                    ),
                                    key: new Node\Scalar\String_('secret')
                                )
                            ]
                        )
                    )
                ]
            );
        }

        if (getenv('BASIC_USER') && getenv('BASIC_PASSWORD')) {
            return new Node\Expr\New_(
                new Node\Name('Tuupola\\Middleware\\HttpBasicAuthentication'),
                [
                    new Node\Arg(
                        value: new Node\Expr\Array_(
                            items: [
                                new Node\Expr\ArrayItem(
                                    value: new Node\Expr\Array_(
                                        items: [
                                            new Node\Expr\ArrayItem(
                                                value: new Node\Scalar\String_(
                                                    getenv('BASIC_PASSWORD')
                                                ),
                                                key: new Node\Scalar\String_(
                                                    getenv('BASIC_USER')
                                                )
                                            )
                                        ]
                                    ),
                                    key: new Node\Scalar\String_('users')
                                )
                            ]
                        )
                    )
                ]
            );
        }

        throw new \Exception(
            'Environment variable "JWT_SECRET" must be set, or "BASIC_USER" + "BASIC_PASSWORD" must be set.'
        );
    }
}
