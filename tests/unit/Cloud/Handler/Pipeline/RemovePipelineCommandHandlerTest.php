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

        $this->assertEquals(Cloud\Event\RemovedPipeline::class, $event::class);
        $this->assertEquals('fa729c14-d075-4d19-8705-aa7056a7b6b9', $event->getId());
    }

    public function testHandlerThrowsAnException(): void
    {
        $this->expectException(Cloud\RemovePipelineFailedException::class);

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

    public function testHandlerThrowsANotFoundException(): void
    {
        $this->expectException(Cloud\RemovePipelineFailedException::class);
        $this->expectExceptionMessage('Something went wrong while trying to remove a step from the pipeline. Maybe you are trying to delete a pipeline that never existed or has already been deleted.');

        $client = $this->createMock(Api\Client::class);
        $client
            ->expects($this->once())
            ->method('deletePipelinePipelineItem')
            ->willThrowException(
                new Api\Exception\DeletePipelinePipelineItemNotFoundException()
            );

        $handler = new Cloud\Handler\Pipeline\RemovePipelineCommandHandler($client);

        $command = new Cloud\Command\Pipeline\RemovePipelineCommand(
            new Cloud\DTO\PipelineId('fa729c14-d075-4d19-8705-aa7056a7b6b9'),
        );

        $handler($command);
    }
}
