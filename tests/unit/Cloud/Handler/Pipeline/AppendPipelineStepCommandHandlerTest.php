<?php

namespace unit\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud;
use PHPUnit\Framework\TestCase;

class AppendPipelineStepCommandHandlerTest extends TestCase
{
    public function testHandlerIsSuccessful(): void
    {
        $client = $this->createMock(Api\Client::class);
        $client
            ->expects($this->once())
            ->method('appendPipelineStepPipelineCollection')
            ->willReturn(
                (object) [
                    "id" => "fa729c14-d075-4d19-8705-aa7056a7b6b9",
                    "code" => "xlsx.loader",
                    "label" => "Loads products",
                    "configuration" => [],
                    "probes" => [],
                ]
            );

        $handler = new Cloud\Handler\Pipeline\AppendPipelineStepCommandHandler($client);

        $command = new Cloud\Command\Pipeline\AppendPipelineStepCommand(
            new Cloud\DTO\PipelineId('fa729c14-d075-4d19-8705-aa7056a7b6b9'),
            new Cloud\DTO\Step(
                'Loads products',
                new Cloud\DTO\StepCode('xlsx.loader'),
                [],
                new Cloud\DTO\ProbeList(),
                2
            ),
        );

        $event = $handler($command);

        $this->assertInstanceOf(Cloud\Event\AppendedPipelineStep::class, $event);
        $this->assertEquals('fa729c14-d075-4d19-8705-aa7056a7b6b9', $event->getId());
    }

    public function testHandlerThrowsAnException(): void
    {
        $this->expectException(Cloud\AppendPipelineStepFailedException::class);

        $client = $this->createMock(Api\Client::class);
        $client
            ->expects($this->once())
            ->method('appendPipelineStepPipelineCollection')
            ->willReturn(null);

        $handler = new Cloud\Handler\Pipeline\AppendPipelineStepCommandHandler($client);

        $command = new Cloud\Command\Pipeline\AppendPipelineStepCommand(
            new Cloud\DTO\PipelineId('fa729c14-d075-4d19-8705-aa7056a7b6b9'),
            new Cloud\DTO\Step(
                'Loads products',
                new Cloud\DTO\StepCode('xlsx.loader'),
                [],
                new Cloud\DTO\ProbeList(),
                2
            ),
        );

        $handler($command);
    }

    public function testHandlerThrowsABadRequestException(): void
    {
        $this->expectException(Cloud\AppendPipelineStepFailedException::class);
        $this->expectExceptionMessage('Something went wrong while trying to append a pipeline step. Maybe your client is not up to date, you may want to update your Gyroscops client.');

        $client = $this->createMock(Api\Client::class);
        $client
            ->expects($this->once())
            ->method('appendPipelineStepPipelineCollection')
            ->willThrowException(
                new Api\Exception\AppendPipelineStepPipelineCollectionBadRequestException()
            );

        $handler = new Cloud\Handler\Pipeline\AppendPipelineStepCommandHandler($client);

        $command = new Cloud\Command\Pipeline\AppendPipelineStepCommand(
            new Cloud\DTO\PipelineId('fa729c14-d075-4d19-8705-aa7056a7b6b9'),
            new Cloud\DTO\Step(
                'Loads products',
                new Cloud\DTO\StepCode('xlsx.loader'),
                [],
                new Cloud\DTO\ProbeList(),
                2
            ),
        );

        $handler($command);
    }

    public function testHandlerThrowsAnUnprocessableEntityException(): void
    {
        $this->expectException(Cloud\AppendPipelineStepFailedException::class);
        $this->expectExceptionMessage('Something went wrong while trying to append a pipeline step. It seems the data you sent was invalid, please check your input.');

        $client = $this->createMock(Api\Client::class);
        $client
            ->expects($this->once())
            ->method('appendPipelineStepPipelineCollection')
            ->willThrowException(
                new Api\Exception\AppendPipelineStepPipelineCollectionUnprocessableEntityException()
            );

        $handler = new Cloud\Handler\Pipeline\AppendPipelineStepCommandHandler($client);

        $command = new Cloud\Command\Pipeline\AppendPipelineStepCommand(
            new Cloud\DTO\PipelineId('fa729c14-d075-4d19-8705-aa7056a7b6b9'),
            new Cloud\DTO\Step(
                'Loads products',
                new Cloud\DTO\StepCode('xlsx.loader'),
                [],
                new Cloud\DTO\ProbeList(),
                2
            ),
        );

        $handler($command);
    }
}
