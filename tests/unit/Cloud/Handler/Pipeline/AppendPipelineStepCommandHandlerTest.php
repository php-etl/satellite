<?php

namespace unit\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use unit\Kiboko\Component\Satellite\Cloud\Diff\ProbeListDiffTest;

class AppendPipelineStepCommandHandlerTest extends TestCase
{
    public function testHandlerIsCorrect(): void
    {
        $client = $this->createMock(Api\Client::class);
        $client
            ->expects($this->once())
            ->method('appendPipelineStepPipelineCollection')
            ->willReturn(
                new Response(
                    status: 202,
                    headers: [
                        'content-type' => 'application/json; charset=utf-8'
                    ],
                )
            );

        $handler = new Cloud\Handler\Pipeline\AppendPipelineStepCommandHandler($client);

        $command = new Cloud\Command\Pipeline\AppendPipelineStepCommand(
            new Cloud\DTO\PipelineId('0ee7d62c-81c5-4cb4-9614-4a73ff3df996'),
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
}
