<?php

namespace unit\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud;
use PHPUnit\Framework\TestCase;

class DeclarePipelineCommandHandlerTest extends TestCase
{
    public function testHandlerIsSuccessful(): void
    {
        $client = $this->createMock(Api\Client::class);
        $client
            ->expects($this->atLeastOnce())
            ->method('declarePipelinePipelineCollection')
            ->willReturn(
                (object)[
                    "id" => "73906092-387e-4d7c-b02d-f0c8f8844286",
                    "label" => "Products pipeline",
                    "code" => "products_pipeline",
                    "fromImage" => "php:8.1-cli-alpine",
                    "targetImage" => "php-8.1:73906092-387e-4d7c-b02d-f0c8f8844286-fa4fca49-d0bf-49db-bc9a-279a7641c83c-3fa85f64-5717-4562-b3fc-2c963f66afa6-3fa85f64-5717-4562-b3fc-2c963f66afa6",
                    "user" => "f11e7b9b-dcbc-47d7-9216-31dccc1ff544",
                    "project" => "5d83bad4-f376-4568-8467-9176b3983117",
                    "organization" => "3fa85f64-5717-4562-b3fc-2c963f66afa6",
                    "autoloads" => [],
                    "steps" => []
                ],
            );

        $handler = new Cloud\Handler\Pipeline\DeclarePipelineCommandHandler($client);

        $command = new Cloud\Command\Pipeline\DeclarePipelineCommand(
            'products_pipeline',
            'Products pipeline',
            new Cloud\DTO\StepList(),
            new Cloud\DTO\Autoload(),
            new Cloud\DTO\OrganizationId('d9cf1074-9f34-4387-92b0-689c8b9aefe1'),
            new Cloud\DTO\WorkspaceId('65f9d659-a42d-4111-a90b-135574f4f752'),
        );

        $event = $handler($command);

        $this->assertEquals(Cloud\Event\PipelineDeclared::class, $event::class);
        $this->assertEquals('73906092-387e-4d7c-b02d-f0c8f8844286', $event->getId());
    }

    public function testHandlerThrowsAnException(): void
    {
        $this->expectException(Cloud\DeclarePipelineFailedException::class);

        $client = $this->createMock(Api\Client::class);
        $client
            ->expects($this->once())
            ->method('declarePipelinePipelineCollection')
            ->willReturn(null);

        $handler = new Cloud\Handler\Pipeline\DeclarePipelineCommandHandler($client);

        $command = new Cloud\Command\Pipeline\DeclarePipelineCommand(
            'products_pipeline',
            'Products pipeline',
            new Cloud\DTO\StepList(),
            new Cloud\DTO\Autoload(),
            new Cloud\DTO\OrganizationId('d9cf1074-9f34-4387-92b0-689c8b9aefe1'),
            new Cloud\DTO\WorkspaceId('65f9d659-a42d-4111-a90b-135574f4f752'),
        );

        $handler($command);
    }

    public function testHandlerThrowsABadRequestException(): void
    {
        $this->expectException(Cloud\DeclarePipelineFailedException::class);
        $this->expectExceptionMessage('Something went wrong while declaring the pipeline. Maybe your client is not up to date, you may want to update your Gyroscops client.');

        $client = $this->createMock(Api\Client::class);
        $client
            ->expects($this->once())
            ->method('declarePipelinePipelineCollection')
            ->willThrowException(
                new Api\Exception\DeclarePipelinePipelineCollectionBadRequestException()
            );

        $handler = new Cloud\Handler\Pipeline\DeclarePipelineCommandHandler($client);

        $command = new Cloud\Command\Pipeline\DeclarePipelineCommand(
            'products_pipeline',
            'Products pipeline',
            new Cloud\DTO\StepList(),
            new Cloud\DTO\Autoload(),
            new Cloud\DTO\OrganizationId('d9cf1074-9f34-4387-92b0-689c8b9aefe1'),
            new Cloud\DTO\WorkspaceId('65f9d659-a42d-4111-a90b-135574f4f752'),
        );

        $handler($command);
    }

    public function testHandlerThrowsAnUnprocessableEntityException(): void
    {
        $this->expectException(Cloud\DeclarePipelineFailedException::class);
        $this->expectExceptionMessage('Something went wrong while declaring the pipeline. It seems the data you sent was invalid, please check your input.');

        $client = $this->createMock(Api\Client::class);
        $client
            ->expects($this->once())
            ->method('declarePipelinePipelineCollection')
            ->willThrowException(
                new Api\Exception\DeclarePipelinePipelineCollectionUnprocessableEntityException()
            );

        $handler = new Cloud\Handler\Pipeline\DeclarePipelineCommandHandler($client);

        $command = new Cloud\Command\Pipeline\DeclarePipelineCommand(
            'products_pipeline',
            'Products pipeline',
            new Cloud\DTO\StepList(),
            new Cloud\DTO\Autoload(),
            new Cloud\DTO\OrganizationId('d9cf1074-9f34-4387-92b0-689c8b9aefe1'),
            new Cloud\DTO\WorkspaceId('65f9d659-a42d-4111-a90b-135574f4f752'),
        );

        $handler($command);
    }
}
