<?php

declare(strict_types=1);
namespace functional\Kiboko\Component\Satellite\Feature\Logger\Builder;

use Kiboko\Component\PHPUnitExtension\Assert\PipelineBuilderAssertTrait;
use Kiboko\Component\Satellite\Feature\Logger\Builder\StderrLogger;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;
use Psr\Log\AbstractLogger;

abstract class StderrLoggerTestCase extends \PHPUnit\Framework\TestCase
{
    use PipelineBuilderAssertTrait;
    private ?vfsStreamDirectory $fs = null;
    protected function setUp(): void
    {
        $this->fs = vfsStream::setup();
    }
    protected function tearDown(): void
    {
        $this->fs = null;
        vfsStreamWrapper::unregister();
    }
    public function testStderrLogger(): void
    {
        $log = new StderrLogger();
        $this->assertBuilderProducesInstanceOf(AbstractLogger::class, $log);
    }
}
