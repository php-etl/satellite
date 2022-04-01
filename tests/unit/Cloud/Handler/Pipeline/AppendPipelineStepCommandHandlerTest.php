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

        $this->assertIsObject($event);
    }

    public function testHandlerThrowsAnException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Something went wrong wile appending a step into the pipeline');

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
}
