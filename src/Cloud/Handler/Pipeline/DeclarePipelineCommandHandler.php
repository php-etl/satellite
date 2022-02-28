<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud\Command\Pipeline\DeclarePipelineCommand;
use Kiboko\Component\Satellite\Cloud\Result;

final class DeclarePipelineCommandHandler
{
    public function __construct(private Api\Client $client)
    {}

    public function __invoke(DeclarePipelineCommand $command): Result
    {
        $response = $this->client->declarePipelinePipelineCollection(
            (new Api\Model\PipelineDeclarePipelineCommandInput())
                ->setLabel($command->label)
                ->setCode($command->code)
                ->setProject($command->project)
                ->setBuildPath('./build'),
            Api\Client::FETCH_RESPONSE
        );

        if ($response !== null && $response->getStatusCode() !== 202) {
            throw new \RuntimeException($response->getReasonPhrase());
        }

        return new Result($response->getStatusCode(), $response->getBody()->getContents());
    }
}
