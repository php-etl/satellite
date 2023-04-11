<?php

declare(strict_types=1);
namespace functional\Kiboko\Component\Satellite\Feature\Logger\Builder;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;

use Kiboko\Component\PHPUnitExtension\Assert\PipelineBuilderAssertTrait;
use Kiboko\Component\Satellite\Feature\Logger\Builder\Logger;
use Kiboko\Component\Satellite\Feature\Logger\Builder\StderrLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\NullLogger;

abstract class LoggerTestCase extends TestCase
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
    public function testWithStderrLogger(): void
    {
        $log = new Logger((new StderrLogger())->getNode());
        $this->assertBuilderProducesInstanceOf(AbstractLogger::class, $log);
    }
    public function testWithoutSpecifiedLogger(): void
    {
        $log = new Logger();
        $this->assertBuilderProducesInstanceOf(NullLogger::class, $log);
    }
    public function testAddingStderrLogger(): void
    {
        $log = new Logger();
        $log->withLogger((new StderrLogger())->getNode());
        $this->assertBuilderProducesInstanceOf(AbstractLogger::class, $log);
    }
}
