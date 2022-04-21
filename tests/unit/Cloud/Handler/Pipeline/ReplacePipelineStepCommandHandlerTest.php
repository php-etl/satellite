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

        $this->assertEquals(Cloud\Event\ReplacedPipelineStep::class, $event::class);
        $this->assertEquals('fa729c14-d075-4d19-8705-aa7056a7b6b9', $event->getId());
    }


    public function testHandlerThrowsAnException(): void
    {
        $this->expectException(Cloud\ReplacePipelineStepFailedException::class);

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

    public function testHandlerThrowsABadRequestException(): void
    {
        $this->expectException(Cloud\ReplacePipelineStepFailedException::class);
        $this->expectExceptionMessage('Something went wrong while replacing a step from the pipeline. Maybe your client is not up to date, you may want to update your Gyroscops client.');

        $client = $this->createMock(Api\Client::class);
        $client
            ->expects($this->once())
            ->method('replacePipelineStepPipelineCollection')
            ->willThrowException(
                new Api\Exception\ReplacePipelineStepPipelineCollectionBadRequestException()
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

        $handler($command);
    }

    public function testHandlerThrowsAnUnprocessableEntityException(): void
    {
        $this->expectException(Cloud\ReplacePipelineStepFailedException::class);
        $this->expectExceptionMessage('Something went wrong while replacing a step from the pipeline. It seems the data you sent was invalid, please check your input.');

        $client = $this->createMock(Api\Client::class);
        $client
            ->expects($this->once())
            ->method('replacePipelineStepPipelineCollection')
            ->willThrowException(
                new Api\Exception\ReplacePipelineStepPipelineCollectionUnprocessableEntityException()
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

        $handler($command);
    }
}
