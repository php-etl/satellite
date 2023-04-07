<?php

declare(strict_types=1);
namespace functional\Kiboko\Component\Satellite\Feature\Logger\Builder;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;

abstract class LoggerTestCase extends \PHPUnit\Framework\TestCase
{
    use \Kiboko\Component\PHPUnitExtension\Assert\PipelineBuilderAssertTrait;
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
        $log = new \Kiboko\Component\Satellite\Feature\Logger\Builder\Logger((new \Kiboko\Component\Satellite\Feature\Logger\Builder\StderrLogger())->getNode());
        $this->assertBuilderProducesInstanceOf(\Psr\Log\AbstractLogger::class, $log);
    }
    public function testWithoutSpecifiedLogger(): void
    {
        $log = new \Kiboko\Component\Satellite\Feature\Logger\Builder\Logger();
        $this->assertBuilderProducesInstanceOf(\Psr\Log\NullLogger::class, $log);
    }
    public function testAddingStderrLogger(): void
    {
        $log = new \Kiboko\Component\Satellite\Feature\Logger\Builder\Logger();
        $log->withLogger((new \Kiboko\Component\Satellite\Feature\Logger\Builder\StderrLogger())->getNode());
        $this->assertBuilderProducesInstanceOf(\Psr\Log\AbstractLogger::class, $log);
    }
}
