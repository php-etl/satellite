<?php

declare(strict_types=1);
namespace functional\Kiboko\Component\Satellite\Feature\Logger\Builder;

abstract class NullLoggerTestCase extends \PHPUnit\Framework\TestCase
{
    use \Kiboko\Component\PHPUnitExtension\Assert\PipelineBuilderAssertTrait;
    private ?\Vfs\FileSystem $fs = null;
    protected function setUp(): void
    {
        $this->fs = \Vfs\FileSystem::factory('vfs://');
        $this->fs->mount();
    }
    protected function tearDown(): void
    {
        $this->fs->unmount();
        $this->fs = null;
    }
    public function testNullLogger(): void
    {
        $log = new \Kiboko\Component\Satellite\Feature\Logger\Builder\NullLogger();
        $this->assertBuilderProducesInstanceOf(\Psr\Log\NullLogger::class, $log);
    }
}
