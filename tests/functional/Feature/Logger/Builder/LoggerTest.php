<?php declare(strict_types=1);

namespace functional\Kiboko\Component\Satellite\Feature\Logger\Builder;

use Kiboko\Component\PHPUnitExtension\Assert\PipelineBuilderAssertTrait;
use Kiboko\Component\Satellite\Feature\Logger\Builder;
use PHPUnit\Framework\TestCase;
use Vfs\FileSystem;

final class LoggerTest extends TestCase
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

    public function testWithStderrLogger(): void
    {
        $log = new Builder\Logger(
            (new Builder\StderrLogger())->getNode()
        );

        $this->assertBuilderProducesInstanceOf(
            'Psr\\Log\\AbstractLogger',
            $log
        );
    }

    public function testWithoutSpecifiedLogger(): void
    {
        $log = new Builder\Logger();

        $this->assertBuilderProducesInstanceOf(
            'Psr\\Log\\NullLogger',
            $log
        );
    }

    public function testAddingStderrLogger(): void
    {
        $log = new Builder\Logger();

        $log->withLogger(
            (new Builder\StderrLogger())->getNode()
        );

        $this->assertBuilderProducesInstanceOf(
            'Psr\\Log\\AbstractLogger',
            $log
        );
    }
}
