<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Composer;

use Composer\Package\Package;
use Composer\Package\PackageInterface;

final class Accumulator implements \IteratorAggregate, \Stringable
{
    /** @var iterable|PackageInterface[] */
    private iterable $packages;

    public function __construct()
    {
        $this->packages = [];
    }

    public function append(PackageInterface ...$packages): self
    {
        array_push($this->packages, ...$packages);

        return $this;
    }

    public function getIterator(): iterable
    {
        return new \ArrayIterator($this->packages);
    }

    public function formatPluginInstance(): \Generator
    {
        /** @var Package $package */
        foreach ($this as $package) {
            yield <<<PHP
                fn (ExpressionLanguage \$interpreter) => new {$package->getExtra()['satellite']['class']}(\$interpreter)
                PHP;
        }
    }

    public function __toString()
    {
        return sprintf(
            <<<PHP
            <?php declare(strict_types=1);
            use \\Symfony\\Component\\ExpressionLanguage\\ExpressionLanguage;
            return fn (string \$buildPath) => new \Kiboko\Component\Satellite\Service(
                \$buildPath,
                %s
            );
            PHP,
            implode(",\n".str_pad('', 4), iterator_to_array($this->formatPluginInstance()))
        );
    }
}
