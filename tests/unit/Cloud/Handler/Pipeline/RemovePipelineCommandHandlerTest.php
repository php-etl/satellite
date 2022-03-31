<?php

namespace unit\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Kiboko\Component\Satellite\Cloud;

class RemovePipelineCommandHandlerTest extends TestCase
{
    public function testHandlerIsCorrect(): void
    {
        $client = $this->createMock(Api\Client::class);
        $client
            ->expects($this->once())
            ->method('deletePipelinePipelineItem')
            ->willReturn(
                new Response(
                    status: 204,
                    headers: [
                        'content-type' => 'application/json; charset=utf-8'
                    ],
                )
            );

        $handler = new Cloud\Handler\Pipeline\RemovePipelineCommandHandler($client);

        $command = new Cloud\Command\Pipeline\RemovePipelineCommand(
            new Cloud\DTO\PipelineId('0ee7d62c-81c5-4cb4-9614-4a73ff3df996'),
        );

        $event = $handler($command);

        $this->assertIsObject($event);
    }
}
