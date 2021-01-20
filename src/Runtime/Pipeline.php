<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Runtime;

use Kiboko\Component\FastMapConfig\ArrayBuilder;
use Kiboko\Component\FastMap;
use Kiboko\Component\Satellite\SatelliteInterface;
use Kiboko\Component\Satellite\Service;
use PhpParser\Node;

final class Pipeline implements RuntimeInterface
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function prepare(SatelliteInterface $satellite): void
    {
    }

    public function build(): array
    {
        $service = new Service();

        return [
            new Node\Stmt\Expression(
                new Node\Expr\Include_(
                    new Node\Expr\BinaryOp\Concat(
                        new Node\Scalar\MagicConst\Dir(),
                        new Node\Scalar\String_('/vendor/autoload.php')
                    ),
                    Node\Expr\Include_::TYPE_REQUIRE
                ),
            ),
            $service->compile($this->config)->getNode(),
        ];
    }
}
