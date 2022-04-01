<?php

namespace unit\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud;
use PHPUnit\Framework\TestCase;

class AddPipelineComposerPSR4AutoloadCommandHandlerTest extends TestCase
{
    public function testHandlerIsSuccessful(): void
    {
        $client = $this->createMock(Api\Client::class);
        $client
            ->expects($this->once())
            ->method('addComposerPipelinePipelineCollection')
            ->willReturn(
                (object) [
                    "id" => "fa729c14-d075-4d19-8705-aa7056a7b6b9",
                    "namespace" => 'App\\',
                    "paths" => [
                        'src/'
                    ],
                ]
            );

        $handler = new Cloud\Handler\Pipeline\AddPipelineComposerPSR4AutoloadCommandHandler($client);

        $command = new Cloud\Command\Pipeline\AddPipelineComposerPSR4AutoloadCommand(
            new Cloud\DTO\PipelineId('fa729c14-d075-4d19-8705-aa7056a7b6b9'),
            'App\\',
            [
                'src/'
            ]
        );

        $event = $handler($command);

        $this->assertIsObject($event);
    }

    public function testHandlerThrowsAnException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Something went wrong wile adding composer PSR4 into the pipeline');

        $client = $this->createMock(Api\Client::class);
        $client
            ->expects($this->once())
            ->method('addComposerPipelinePipelineCollection')
            ->willReturn(null);

        $handler = new Cloud\Handler\Pipeline\AddPipelineComposerPSR4AutoloadCommandHandler($client);

        $command = new Cloud\Command\Pipeline\AddPipelineComposerPSR4AutoloadCommand(
            new Cloud\DTO\PipelineId('fa729c14-d075-4d19-8705-aa7056a7b6b9'),
            'App\\',
            [
                'src/'
            ]
        );

        $handler($command);
    }
}
