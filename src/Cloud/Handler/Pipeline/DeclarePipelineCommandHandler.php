<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud;

final class DeclarePipelineCommandHandler
{
    public function __construct(private Api\Client $client)
    {}

    public function __invoke(Cloud\Command\Pipeline\DeclarePipelineCommand $command): Cloud\Event\PipelineDeclared
    {
        $response = $this->client->declarePipelinePipelineCollection(
            (new Api\Model\PipelineDeclarePipelineCommandInput())
                ->setLabel($command->label)
                ->setCode($command->code)
                ->setProject($command->project),
            Api\Client::FETCH_RESPONSE
        );

        if ($response !== null && $response->getStatusCode() !== 202) {
            throw throw new \RuntimeException($response->getReasonPhrase());
        }

        $result = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return new Cloud\Event\PipelineDeclared($result['id']);
    }
}
