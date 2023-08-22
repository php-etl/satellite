<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Runtime;

use PhpParser\Node;

final class Authorization
{
    public function build(array $config): Node\Expr\New_
    {
        if ($basicUsers = $config['authorization']['basic']) {
            return $this->buildBasic($basicUsers);
        }
        if ($secret = $config['authorization']['jwt']['secret']) {
            return $this->buildJwt($secret);
        }

        throw new \Exception('"authorization" must be set.');
    }

    private function buildBasic(array $users)
    {
        $array = [];
        foreach ($users as $credentials) {
            $array[] = new Node\Expr\ArrayItem(
                value: new Node\Scalar\String_(
                    $credentials['password']
                ),
                key: new Node\Scalar\String_(
                    $credentials['user']
                )
            );
        }

        return new Node\Expr\New_(
            new Node\Name('Tuupola\\Middleware\\HttpBasicAuthentication'),
            [
                new Node\Arg(
                    value: new Node\Expr\Array_(
                        items: [
                            new Node\Expr\ArrayItem(
                                value: new Node\Expr\Array_(
                                    items: $array
                                ),
                                key: new Node\Scalar\String_('users')
                            ),
                        ]
                    )
                ),
            ]
        );
    }

    private function buildJwt(string $secret)
    {
        return new Node\Expr\New_(
            new Node\Name('Tuupola\\Middleware\\JwtAuthentication'),
            [
                new Node\Arg(
                    value: new Node\Expr\Array_(
                        items: [
                            new Node\Expr\ArrayItem(
                                value: new Node\Scalar\String_($secret),
                                key: new Node\Scalar\String_('secret')
                            ),
                        ]
                    )
                ),
            ]
        );
    }
}
