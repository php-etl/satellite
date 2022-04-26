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

        $this->assertInstanceOf(Cloud\Event\AddedPipelineComposerPSR4Autoload::class, $event);
        $this->assertEquals('fa729c14-d075-4d19-8705-aa7056a7b6b9', $event->getId());
        $this->assertEquals('App\\', $event->getNamespace());
        $this->assertEquals(['src/'], $event->getPaths());
    }

    public function testHandlerThrowsAnException(): void
    {
        $this->expectException(Cloud\AddPipelineComposerPSR4AutoloadFailedException::class);

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

    public function testHandlerThrowsABadRequestException(): void
    {
        $this->expectException(Cloud\AddPipelineComposerPSR4AutoloadFailedException::class);
        $this->expectExceptionMessage('Something went wrong while trying to add PSR4 autoloads into the pipeline. Maybe your client is not up to date, you may want to update your Gyroscops client.');

        $client = $this->createMock(Api\Client::class);
        $client
            ->expects($this->once())
            ->method('addComposerPipelinePipelineCollection')
            ->willThrowException(
                new Api\Exception\AddComposerPipelinePipelineCollectionBadRequestException()
            );

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

    public function testHandlerThrowsAnUnprocessableEntityException(): void
    {
        $this->expectException(Cloud\AddPipelineComposerPSR4AutoloadFailedException::class);
        $this->expectExceptionMessage('Something went wrong while trying to add PSR4 autoloads into the pipeline. It seems the data you sent was invalid, please check your input.');

        $client = $this->createMock(Api\Client::class);
        $client
            ->expects($this->once())
            ->method('addComposerPipelinePipelineCollection')
            ->willThrowException(
                new Api\Exception\AddComposerPipelinePipelineCollectionUnprocessableEntityException()
            );

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
