<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Custom\Builder;

use Kiboko\Contract\Configurator\StepBuilderInterface;
use PhpParser\Node;

final class Loader implements StepBuilderInterface
{
    private ?Node\Expr $logger;
    private ?Node\Expr $rejection;
    private ?Node\Expr $state;

    public function withLogger(Node\Expr $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function withRejection(Node\Expr $rejection): self
    {
        $this->rejection = $rejection;

        return $this;
    }

    public function withState(Node\Expr $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getNode(): Node
    {


        $loadStmts = [];

//        foreach ($this->parameters as $key => $parameter){
//            $loadStmts[]= new Node\Stmt\Expression(
//                expr: new Node\Expr\MethodCall(
//                    new Node\Expr\Variable('containerBuilder'),
//                    'setParameter',
//                    [
//                        new Node\Arg(
//                            new Node\Scalar\String_($key),
//                        ),
//                        new Node\Arg(
//                            new Node\Scalar\String_($parameter),
//                        ),
//                    ]
//                )
//            );
//        }
//        foreach ($this->services as $key => $service) {
//            $loadStmts[]= new Node\Stmt\Expression(
//                new Node\Expr\Assign(
//                    var: new Node\Expr\Variable('service'),
//                    expr: new Node\Expr\MethodCall(
//                        new Node\Expr\Variable('containerBuilder'),
//                        'setParameter',
//                        [
//                            new Node\Arg(
//                                new Node\Scalar\String_($key),
//                            ),
//                            new Node\Arg(
//                                new Node\Scalar\String_($key),
//                            ),
//                        ]
//                    )
//                ),
//            );
//
//            foreach ($service['arguments'] as $argument) {
//                $loadStmts[] = new Node\Stmt\Expression(
//                    new Node\Expr\Assign(
//                        var: new Node\Expr\Variable('service'),
//                        expr: new Node\Expr\MethodCall(
//                            new Node\Expr\Variable('containerBuilder'),
//                            'addArgument',
//                            [
//                                new Node\Arg(
//                                    new Node\Scalar\String_($argument),
//                                ),
//                            ]
//                        ),
//                    ),
//                );
//            }
//        }
//
//            foreach ($this->services as $key => $service){
//                $containerBuilder->register($key, $key);
//                if (array_key_exists('arguments', $service)){
//                    foreach ($service['arguments'] as $argument){
//                        $containerBuilder->addArgument($argument);
//                    }
//                }
//
//            $containerBuilder = new \Symfony\Component\DependencyInjection\ContainerBuilder();
//            $service = $containerBuilder
//                ->register('App\Security\Encrypt', 'App\Security\Encrypt')
//                ->addArgument('$cipher','%cipher%');
//            $service = $containerBuilder
//                ->$services = register('App\Loader', 'App\Loader');
//
//                $service = $containerBuilder->addArgument(['$client', '$client'])
//                $service = $containerBuilder->addArgument(['$client', '$client']);
//
//            foreach($parameters as $key => $parameter){
//                $containerBuilder->setParameter('key','parameter');
//            }
//
//            $arguments = [
//                new Node\Expr\Variable('services'),
//                new Node\Arg(
//                    new Node\Scalar\String_($this->use)
//                )
//            ];
            return new Node\Expr\New_(
                class: new Node\Stmt\Class_(
                name: null,
                subNodes: [
                    'stmts' => [
                        new Node\Stmt\Property(
                            flags: Node\Stmt\Class_::MODIFIER_PRIVATE,
                            props: [
                                new Node\Stmt\PropertyProperty(
                                    name: new Node\Identifier('container'),
                                )
                            ],
                            type: new Node\Name\FullyQualified('Symfony\\Component\\DependencyInjection\\ContainerInterface'),
                        ),
                        new Node\Stmt\ClassMethod(
                            name: new Node\Identifier('__construct'),
                            subNodes: [
                                'flags' => Node\Stmt\Class_::MODIFIER_PUBLIC,
                                'stmts' => [
                                    new Node\Stmt\Expression(
                                        new Node\Expr\Assign(
                                            var: new Node\Expr\Variable('containerBuilder'),
                                            expr: new Node\Expr\New_(
                                                class: new Node\Name\FullyQualified('Symfony\Component\DependencyInjection\ContainerBuilder')
                                            ),
                                        ),
                                    ),
//                                    new Node\Stmt\Expression(
//                                        new Node\Expr\Assign(
//                                            new Node\Expr\PropertyFetch(
//                                                new Node\Expr\Variable('this'),
//                                                'container'
//                                            )
//                                        ),
//                                    ),
                                ],
                            ],
                        ),
                        new Node\Stmt\ClassMethod(
                            name: new Node\Identifier('load'),
                            subNodes: [
                                'stmts' => $loadStmts,
                                'returnType' => new Node\Name\FullyQualified('Generator'),
                            ],
                        ),
                    ],
                    'implements' => [
                        new Node\Name\FullyQualified('Kiboko\\Contract\\Pipeline\\LoaderInterface'),
                    ],
                ],
            ),
        );
    }
}
