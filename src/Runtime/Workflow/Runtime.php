<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Runtime\Workflow;

use Kiboko\Component\Satellite;
use PhpParser\Node;
use PhpParser\PrettyPrinter;
use Psr\Log\LoggerInterface;

final class Runtime implements Satellite\Runtime\RuntimeInterface
{
    public function __construct(private array $config, private string $filename = 'function.php')
    {
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function prepare(Satellite\SatelliteInterface $satellite, LoggerInterface $logger): void
    {
        $satellite->withFile(
            new Satellite\File($this->filename, new Satellite\Asset\InMemory(
                '<?php' . PHP_EOL . (new PrettyPrinter\Standard())->prettyPrint($this->build())
            )),
        );
    }

    public function build(): array
    {
        $service = new Satellite\Service();

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
            new Node\Stmt\Expression(
                new Node\Expr\MethodCall(
                    var: $service->compile($this->config)->getBuilder()->getNode(),
                    name: 'run'
                ),
            )
        ];
    }
}
