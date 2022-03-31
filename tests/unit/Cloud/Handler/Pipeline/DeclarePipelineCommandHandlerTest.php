<?php

namespace unit\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Kiboko\Component\Satellite\Cloud\Event\PipelineDeclared;
use Symfony\Component\HttpClient\Response\MockResponse;

class DeclarePipelineCommandHandlerTest extends TestCase
{
    public function testHandlerIsSuccessful(): void
    {
//        $client = $this->createMock(Api\Client::class);
//        $client
//            ->expects($this->once())
//            ->method('declarePipelinePipelineCollection')
//            ->willReturn(
//                new MockResponse(
//                    json_encode(
//                        [
//                            "id" => "73906092-387e-4d7c-b02d-f0c8f8844286",
//                            "label" => "Products pipeline",
//                            "code" => "products_pipeline",
//                            "fromImage" => "php:8.1-cli-alpine",
//                            "targetImage" => "php-8.1:73906092-387e-4d7c-b02d-f0c8f8844286-fa4fca49-d0bf-49db-bc9a-279a7641c83c-3fa85f64-5717-4562-b3fc-2c963f66afa6-3fa85f64-5717-4562-b3fc-2c963f66afa6",
//                            "user" => "f11e7b9b-dcbc-47d7-9216-31dccc1ff544",
//                            "project" => "5d83bad4-f376-4568-8467-9176b3983117",
//                            "organization" => "3fa85f64-5717-4562-b3fc-2c963f66afa6",
//                            "autoloads" => [],
//                            "steps" => []
//                        ], JSON_THROW_ON_ERROR
//                    )
//                )
//            );


        $httpClient = HttpClient::createForBaseUri(
            'https://localhost',
            [
                'verify_peer' => false,
                'auth_bearer' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE2NDg3MzU1MTMsImV4cCI6MTY0ODczOTExMywicm9sZXMiOlsiUk9MRV9BRE1JTiJdLCJ1c2VybmFtZSI6ImFkbWluIn0.zfUyZtlSlx-tObgJxW1PkcCBJPujBM9koI5dUJzW63-93LgdE57P8UCksJa_NyUJkbEeNYxCz5ZMviFDK5UNHcaO1_r0YojQzVQOTsftK2-Ez0BQ4ldA0bTmeORpX0Iqk1kM9Hk4YbuMXGR-PDmmmgbDEcVTwp4DDqsy4rCYASYKa17EWKPAMk3w6DnykHPK-r8e1Mf9mhpANxOyNKq8Lv06-71fKEFbXvh5pflYXZxBshk3FEQkfVuxpmwMMwsoPCrDleMwEzsOpKma1MFwbf383UiSJ6XIrC8fccsiwri3RuFBcP42Q30ZzeNjkYNEWS535rwqsXPVcEPi7nnLRw'
            ]
        );

        $psr18Client = new Psr18Client($httpClient);
        $client = Api\Client::create($psr18Client);

        $handler = new Cloud\Handler\Pipeline\DeclarePipelineCommandHandler($client);

        $command = new Cloud\Command\Pipeline\DeclarePipelineCommand(
            'products_pipeline',
            'Products pipeline',
            new Cloud\DTO\StepList(),
            new Cloud\DTO\Autoload(),
            new Cloud\DTO\OrganizationId('4dcaed4d-1753-41e9-abf4-f6ad83dd4bfb'),
            new Cloud\DTO\ProjectId('5d83bad4-f376-4568-8467-9176b3983117'),
        );

        $event = $handler($command);

        $this->assertIsObject($event);
        $this->assertEquals(PipelineDeclared::class, $event::class);
    }
}