<?php

namespace unit\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;

class AddPipelineComposerPSR4AutoloadCommandHandlerTest extends TestCase
{
    public function testHandlerIsCorrect(): void
    {
        $client = $this->createMock(Api\Client::class);
        $client
            ->expects($this->once())
            ->method('addComposerPipelinePipelineCollection')
            ->willReturn(
                new Response(
                    status: 202,
                    headers: [
                        'content-type' => 'application/json; charset=utf-8'
                    ],
                )
            );

        $handler = new Cloud\Handler\Pipeline\AddPipelineComposerPSR4AutoloadCommandHandler($client);

        $command = new Cloud\Command\Pipeline\AddPipelineComposerPSR4AutoloadCommand(
            new Cloud\DTO\PipelineId('0ee7d62c-81c5-4cb4-9614-4a73ff3df996'),
            'App\\',
            [
                'src/'
            ]
        );

        $event = $handler($command);

        $this->assertIsObject($event);
    }
}
