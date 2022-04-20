<?php

namespace unit\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use PHPUnit\Framework\TestCase;
use Kiboko\Component\Satellite\Cloud;

class RemovePipelineStepCommandHandlerTest extends TestCase
{
    public function testHandlerIsSuccessful(): void
    {
        $client = $this->createMock(Api\Client::class);
        $client
            ->expects($this->once())
            ->method('deletePipelineStepPipelineItem')
            ->willReturn(
                (object) [],
            );

        $handler = new Cloud\Handler\Pipeline\RemovePipelineStepCommandHandler($client);

        $command = new Cloud\Command\Pipeline\RemovePipelineStepCommand(
            new Cloud\DTO\PipelineId('fa729c14-d075-4d19-8705-aa7056a7b6b9'),
            new Cloud\DTO\StepCode('xlsx.loader'),
        );

        $event = $handler($command);

        $this->assertIsObject($event);
    }

    public function testHandlerThrowsAnException(): void
    {
        $this->expectException(Cloud\SendPipelineConfigurationException::class);

        $client = $this->createMock(Api\Client::class);
        $client
            ->expects($this->once())
            ->method('deletePipelineStepPipelineItem')
            ->willReturn(null);

        $handler = new Cloud\Handler\Pipeline\RemovePipelineStepCommandHandler($client);

        $command = new Cloud\Command\Pipeline\RemovePipelineStepCommand(
            new Cloud\DTO\PipelineId('fa729c14-d075-4d19-8705-aa7056a7b6b9'),
            new Cloud\DTO\StepCode('xlsx.loader'),
        );

        $handler($command);
    }
}
