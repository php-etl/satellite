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

    public function formatPluginInstance(): iterable
    {
        /** @var Package $package */
        foreach ($this as $package) {
            yield 'new ' . $package->getExtra()['satellite']['class'] . '()';
        }
    }

    public function __toString()
    {
        return "<?php return [\n    " . implode(",\n    ", iterator_to_array($this->formatPluginInstance())) . "\n];";
    }
}
