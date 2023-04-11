<?php

declare(strict_types=1);
namespace functional\Kiboko\Component\Satellite\Feature\Logger\Builder;

use Kiboko\Component\PHPUnitExtension\Assert\PipelineBuilderAssertTrait;
use Kiboko\Component\Satellite\Feature\Logger\Builder\NullLogger;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;

abstract class NullLoggerTestCase extends TestCase
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
    public function testNullLogger(): void
    {
        $log = new NullLogger();
        $this->assertBuilderProducesInstanceOf(\Psr\Log\NullLogger::class, $log);
    }
}
