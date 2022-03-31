<?php

namespace unit\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Kiboko\Component\Satellite\Cloud;

class RemovePipelineStepCommandHandlerTest extends TestCase
{
    public function testHandlerIsCorrect(): void
    {
        $client = $this->createMock(Api\Client::class);
        $client
            ->expects($this->once())
            ->method('deletePipelineStepPipelineItem')
            ->willReturn(
                new Response(
                    status: 204,
                    headers: [
                        'content-type' => 'application/json; charset=utf-8'
                    ],
                )
            );

        $handler = new Cloud\Handler\Pipeline\RemovePipelineStepCommandHandler($client);

        $command = new Cloud\Command\Pipeline\RemovePipelineStepCommand(
            new Cloud\DTO\PipelineId('0ee7d62c-81c5-4cb4-9614-4a73ff3df996'),
            new Cloud\DTO\StepCode('csv.extractor'),
        );

        $event = $handler($command);

        $this->assertIsObject($event);
    }
}
