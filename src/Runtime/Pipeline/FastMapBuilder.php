<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Runtime\Pipeline;

use Kiboko\Component\FastMap\Compiler\Builder\PropertyPathBuilder;
use Kiboko\Component\FastMap\Compiler\Strategy\StrategyInterface;
use Kiboko\Component\FastMap\Contracts\CompilableMapperInterface;
use Kiboko\Component\FastMap\Contracts\CompiledMapperInterface;
use Kiboko\Component\Metadata\ClassMetadataInterface;
use PhpParser\Builder;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

final class FastMapBuilder implements Builder
{
    private PropertyPathInterface $outputPath;
    /** @var iterable|CompilableMapperInterface[] */
    private iterable $mappers;

    public function __construct(PropertyPathInterface $outputPath, CompilableMapperInterface ...$mappers)
    {
        $this->outputPath = $outputPath;
        $this->mappers = $mappers;
    }

    public function getNode(): Node
    {
        $factory = new BuilderFactory();

        $stmts = [];
        foreach ($this->mappers as $mapper) {
            array_push($stmts, ...$mapper->compile(
                (new PropertyPathBuilder($this->outputPath, $output = new Node\Expr\Variable('output')))->getNode()
            ));
        }

        return $factory->class('Anonymous')
            ->implement(new Node\Name\FullyQualified(CompiledMapperInterface::class))
            ->addStmt($factory->method('__invoke')
                ->makePublic()
                ->addParam($factory->param('input'))
                ->addParam($factory->param('output')->setDefault(null))
                ->addStmts($stmts)
                ->addStmt(new Node\Stmt\Return_($output))
            )->getNode();
    }
}
