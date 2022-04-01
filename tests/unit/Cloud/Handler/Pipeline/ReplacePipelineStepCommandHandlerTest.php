<?php

namespace unit\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use PHPUnit\Framework\TestCase;
use Kiboko\Component\Satellite\Cloud;

class ReplacePipelineStepCommandHandlerTest extends TestCase
{
    public function testHandlerIsSuccessful(): void
    {
        $client = $this->createMock(Api\Client::class);
        $client
            ->expects($this->once())
            ->method('replacePipelineStepPipelineCollection')
            ->willReturn(
                (object) [
                    "id" => "fa729c14-d075-4d19-8705-aa7056a7b6b9",
                    "former" => "csv.extractor",
                    "code" => "products_pipeline",
                    "label" => "Extract products from a xlsx file.",
                    "configuration" => [],
                    "probes" => [],
                ],
            );

        $handler = new Cloud\Handler\Pipeline\ReplacePipelineStepCommandHandler($client);

        $command = new Cloud\Command\Pipeline\ReplacePipelineStepCommand(
            new Cloud\DTO\PipelineId('fa729c14-d075-4d19-8705-aa7056a7b6b9'),
            new Cloud\DTO\StepCode('csv.extractor'),
            new Cloud\DTO\Step(
                'Extract products from a xlsx file.',
                new Cloud\DTO\StepCode('xlsx.extractor'),
                [],
                new Cloud\DTO\ProbeList(),
                1,
            ),
        );

        $event = $handler($command);

        $this->assertIsObject($event);
    }


    public function testHandlerThrowsAnException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Something went wrong wile replacing a step from the pipeline');

        $client = $this->createMock(Api\Client::class);
        $client
            ->expects($this->once())
            ->method('replacePipelineStepPipelineCollection')
            ->willReturn(null);

        $handler = new Cloud\Handler\Pipeline\ReplacePipelineStepCommandHandler($client);

        $command = new Cloud\Command\Pipeline\ReplacePipelineStepCommand(
            new Cloud\DTO\PipelineId('fa729c14-d075-4d19-8705-aa7056a7b6b9'),
            new Cloud\DTO\StepCode('csv.extractor'),
            new Cloud\DTO\Step(
                'Extract products from a xlsx file.',
                new Cloud\DTO\StepCode('xlsx.extractor'),
                [],
                new Cloud\DTO\ProbeList(),
                1,
            ),
        );

        $handler($command);
    }
}
