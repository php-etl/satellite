<?php declare(strict_types=1);

namespace functional\Kiboko\Component\Satellite\Feature\Logger;

use PhpParser\Builder;
use PhpParser\Node;
use PhpParser\PrettyPrinter;
use PHPUnit\Framework\Constraint\Constraint;
use function sprintf;

final class BuilderHasLogger extends Constraint
{
    private string $className;

    public function __construct(string $className)
    {
        $this->className = $className;
    }

    public function toString(): string
    {
        return sprintf(
            'has a logger of instance %s "%s"',
            $this->getType(),
            $this->className
        );
    }

    /**
     * @param Builder $other value or object to evaluate
     */
    protected function matches($other): bool
    {
        $printer = new PrettyPrinter\Standard();

        try {
            $filename = 'vfs://' . hash('sha512', random_bytes(512)) .'.php';

            file_put_contents($filename, $printer->prettyPrintFile([
                new Node\Stmt\Return_($other->getNode()),
            ]));

            $instance = include $filename;
        } catch (\Error $exception) {
            $this->fail($other, $exception->getMessage());
        }

        return $instance->getLogger() instanceof $this->className;
    }
}
