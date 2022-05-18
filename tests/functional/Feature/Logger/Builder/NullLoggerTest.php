<?php declare(strict_types=1);

namespace functional\Kiboko\Component\Satellite\Feature\Logger\Builder;

use Kiboko\Component\PHPUnitExtension\Assert\PipelineBuilderAssertTrait;
use Kiboko\Component\Satellite\Feature\Logger\Builder;
use PHPUnit\Framework\TestCase;
use Vfs\FileSystem;

abstract class NullLoggerTest extends TestCase
{
    use PipelineBuilderAssertTrait;

    private ?FileSystem $fs = null;

    protected function setUp(): void
    {
        $this->fs = FileSystem::factory('vfs://');
        $this->fs->mount();
    }

    protected function tearDown(): void
    {
        $this->fs->unmount();
        $this->fs = null;
    }

    public function testNullLogger(): void
    {
        $log = new Builder\NullLogger();

        $this->assertBuilderProducesInstanceOf(
            'Psr\\Log\\NullLogger',
            $log
        );
    }
}
