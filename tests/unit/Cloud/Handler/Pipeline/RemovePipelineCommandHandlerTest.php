<?php

namespace unit\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use PHPUnit\Framework\TestCase;
use Kiboko\Component\Satellite\Cloud;

class RemovePipelineCommandHandlerTest extends TestCase
{
    public function testHandlerIsSuccessful(): void
    {
        $client = $this->createMock(Api\Client::class);
        $client
            ->expects($this->once())
            ->method('deletePipelinePipelineItem')
            ->willReturn(
                (object) [],
            );

        $handler = new Cloud\Handler\Pipeline\RemovePipelineCommandHandler($client);

        $command = new Cloud\Command\Pipeline\RemovePipelineCommand(
            new Cloud\DTO\PipelineId('fa729c14-d075-4d19-8705-aa7056a7b6b9'),
        );

        $event = $handler($command);

        $this->assertIsObject($event);
    }

    public function testHandlerThrowsAnException(): void
    {
        $this->expectException(Cloud\RemovePipelineConfigurationException::class);

        $client = $this->createMock(Api\Client::class);
        $client
            ->expects($this->once())
            ->method('deletePipelinePipelineItem')
            ->willReturn(null);

        $handler = new Cloud\Handler\Pipeline\RemovePipelineCommandHandler($client);

        $command = new Cloud\Command\Pipeline\RemovePipelineCommand(
            new Cloud\DTO\PipelineId('fa729c14-d075-4d19-8705-aa7056a7b6b9'),
        );

        $handler($command);
    }
}
