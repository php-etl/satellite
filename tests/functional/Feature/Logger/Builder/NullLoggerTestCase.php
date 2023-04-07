<?php

declare(strict_types=1);
namespace functional\Kiboko\Component\Satellite\Feature\Logger\Builder;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamWrapper;

abstract class NullLoggerTestCase extends \PHPUnit\Framework\TestCase
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
    public function testNullLogger(): void
    {
        $log = new \Kiboko\Component\Satellite\Feature\Logger\Builder\NullLogger();
        $this->assertBuilderProducesInstanceOf(\Psr\Log\NullLogger::class, $log);
    }
}
