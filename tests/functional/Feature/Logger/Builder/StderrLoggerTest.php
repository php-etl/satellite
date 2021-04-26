<?php declare(strict_types=1);

namespace functional\Kiboko\Component\Satellite\Feature\Logger\Builder;

use Kiboko\Component\PHPUnitExtension\BuilderAssertTrait;
use Kiboko\Component\Satellite\Feature\Logger\Builder;
use PHPUnit\Framework\TestCase;
use Vfs\FileSystem;

final class StderrLoggerTest extends TestCase
{
    use BuilderAssertTrait;

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

    public function testStderrLogger(): void
    {
        $log = new Builder\StderrLogger();

        $this->assertBuilderProducesInstanceOf(
            'Psr\\Log\\AbstractLogger',
            $log
        );
    }
}
