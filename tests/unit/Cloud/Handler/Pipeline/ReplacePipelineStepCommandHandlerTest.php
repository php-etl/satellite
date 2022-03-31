<?php

namespace unit\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Kiboko\Component\Satellite\Cloud;

class ReplacePipelineStepCommandHandlerTest extends TestCase
{
    public function testHandlerIsCorrect(): void
    {
        $client = $this->createMock(Api\Client::class);
        $client
            ->expects($this->once())
            ->method('replacePipelineStepPipelineCollection')
            ->willReturn(
                new Response(
                    status: 202,
                    headers: [
                        'content-type' => 'application/json; charset=utf-8'
                    ],
                )
            );

        $handler = new Cloud\Handler\Pipeline\ReplacePipelineStepCommandHandler($client);

        $command = new Cloud\Command\Pipeline\ReplacePipelineStepCommand(
            new Cloud\DTO\PipelineId('0ee7d62c-81c5-4cb4-9614-4a73ff3df996'),
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
}
