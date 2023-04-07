<?php

declare(strict_types=1);
namespace functional\Kiboko\Component\Satellite\Feature\Logger\Builder;

use Kiboko\Component\PHPUnitExtension\Assert\PipelineBuilderAssertTrait;
use Kiboko\Component\Satellite\Feature\Logger\Builder\StderrLogger;
use Psr\Log\AbstractLogger;
use Vfs\FileSystem;

abstract class StderrLoggerTestCase extends \PHPUnit\Framework\TestCase
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
    public function testStderrLogger(): void
    {
        $log = new StderrLogger();
        $this->assertBuilderProducesInstanceOf(AbstractLogger::class, $log);
    }
}
