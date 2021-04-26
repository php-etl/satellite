<?php declare(strict_types=1);

namespace functional\Kiboko\Component\Satellite\Feature\Logger;

use PhpParser\Builder;
use PhpParser\Node;
use PhpParser\PrettyPrinter;
use function sprintf;
use PHPUnit\Framework\Constraint\Constraint;
use ReflectionClass;
use ReflectionException;

final class BuilderProducesAnInstanceOf extends Constraint
{
    private string $className;

    public function __construct(string $className)
    {
        $this->className = $className;
    }

    /**
     * Returns a string representation of the constraint.
     */
    public function toString(): string
    {
        return sprintf(
            'is instance of %s "%s"',
            $this->getType(),
            $this->className
        );
    }

    /**
     * Evaluates the constraint for parameter $other. Returns true if the
     * constraint is met, false otherwise.
     *
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

        return $instance instanceof $this->className;
    }

    /**
     * Returns the description of the failure.
     *
     * The beginning of failure messages is "Failed asserting that" in most
     * cases. This method should return the second part of that sentence.
     *
     * @param Builder $other evaluated value or object
     *
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    protected function failureDescription($other): string
    {
        return sprintf(
            'The following generated code should be an instance of %s "%s"'.PHP_EOL.'%s',
            $this->getType(),
            $this->className,
            $this->exporter()->export((new PrettyPrinter\Standard())->prettyPrint([$other->getNode()])),
        );
    }

    private function getType(): string
    {
        try {
            $reflection = new ReflectionClass($this->className);

            if ($reflection->isInterface()) {
                return 'interface';
            }
        } catch (ReflectionException $e) {
        }

        return 'class';
    }
}
