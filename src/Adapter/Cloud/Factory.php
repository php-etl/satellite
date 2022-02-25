<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Cloud;

use Gyroscops\Api;
use Kiboko\Component\Satellite;
use Kiboko\Contract\Configurator\Adapter;
use Kiboko\Contract\Configurator\AdapterConfigurationInterface;

#[Adapter(name: "cloud")]
final class Factory implements Satellite\Adapter\CloudFactoryInterface
{
    private Configuration $configuration;

    public function __construct(private Api\Client $client)
    {
        $this->configuration = new Configuration();
    }

    public function configuration(): AdapterConfigurationInterface
    {
        return $this->configuration;
    }

    public function create(array $configuration): void
    {
        $response = $this->declarePipeline($configuration);

        if ($response !== null && $response->getStatusCode() === 202) {
            $pipelineId = json_decode($response->getBody()->getContents(), true)["id"];

            foreach($configuration["pipeline"]["steps"] as $step) {
                $response = $this->appendPipelineStep($pipelineId, $step);

                if ($response === null || $response->getStatusCode() === 202) {
                    throw new \RuntimeException('Impossible to send step config to the API.');
                }
            }
        }
    }

    public function update(array $configuration): void
    {
        // Use the code to find the corresponding pipeline
        $pipeline = $this->client->getPipelineCollection([
            'code' => $configuration["pipeline"]["code"]
        ]);

        // Compare steps in Gyroscops and steps in folder, check for differences and call addPipelineStepBefore,
        // addPipelineStepAfter, replacePipelineStep in the good case
    }

    public function remove(array $configuration): void
    {
        // TODO: Implement remove() method.
    }

    private function declarePipeline(array $configuration): void
    {
        $declarePipelineData = new Api\Model\PipelineDeclarePipelineCommandInputJsonld();
        $declarePipelineData->setLabel($configuration["pipeline"]["label"]);
        $declarePipelineData->setCode($configuration["pipeline"]["code"]);
        $declarePipelineData->setProject($configuration["cloud"]["project"]);


        $this->client->declarePipelinePipelineCollection($declarePipelineData, Api\Client::FETCH_RESPONSE);
    }

    private function appendPipelineStep(string $pipelineId, array $configuration): \Psr\Http\Message\ResponseInterface
    {
        $appendStepData = new Api\Model\PipelineStepAppendPipelineStepCommandInputJsonld();
        $appendStepData->setId($pipelineId);
        $appendStepData->setLabel($configuration["label"]);
        $appendStepData->setCode($configuration["code"]);
        $appendStepData->setConfiguration($configuration);
        $appendStepData->setProbes([]);

        return $this->client->appendPipelineStepPipelineStepCollection($appendStepData, Api\Client::FETCH_RESPONSE);
    }

    private function addPipelineStepBefore(string $next, array $configuration): \Psr\Http\Message\ResponseInterface
    {
        $addStepBeforeData = new Api\Model\PipelineStepAddBeforePipelineStepCommandInputJsonld();
        $addStepBeforeData->setNext($next);
        $addStepBeforeData->setLabel($configuration["label"]);
        $addStepBeforeData->setCode($configuration["code"]);
        $addStepBeforeData->setConfiguration($configuration);
        $addStepBeforeData->setProbes([]);

        return $this->client->addBeforePipelineStepPipelineStepCollection($addStepBeforeData, Api\Client::FETCH_RESPONSE);
    }

    private function addPipelineStepAfter(string $previous, array $configuration): \Psr\Http\Message\ResponseInterface
    {
        $addStepAfterData = new Api\Model\PipelineStepAddAfterPipelineStepCommandInputJsonld();
        $addStepAfterData->setPrevious($previous);
        $addStepAfterData->setLabel($configuration["label"]);
        $addStepAfterData->setCode($configuration["code"]);
        $addStepAfterData->setConfiguration($configuration);
        $addStepAfterData->setProbes([]);

        return $this->client->addAfterPipelineStepPipelineStepCollection($addStepAfterData, Api\Client::FETCH_RESPONSE);
    }

    private function replacePipelineStep(string $former, array $configuration): \Psr\Http\Message\ResponseInterface
    {
        $replaceStepData = new Api\Model\PipelineStepReplacePipelineStepCommandInputJsonld();
        $replaceStepData->setFormer($former);
        $replaceStepData->setLabel($configuration["label"]);
        $replaceStepData->setCode($configuration["code"]);
        $replaceStepData->setConfiguration($configuration);
        $replaceStepData->setProbes([]);

        return $this->client->replacePipelineStepPipelineStepCollection($replaceStepData, Api\Client::FETCH_RESPONSE);
    }

    private function deleteStep(\Gyroscops\Api\Model\PipelineRead $pipeline, array $configuration): \Psr\Http\Message\ResponseInterface
    {
        $removeStep = new Api\Model\PipelineStepRemovePipelineStepCommandInputJsonld();
        $removeStep->setId($pipeline->getId());
        $removeStep->setCode($configuration["pipeline"]["code"]);

        return $this->client->removePipelineStepPipelineStepCollection($removeStep, Api\Client::FETCH_RESPONSE);
    }

    private function deleteStepProb(\Gyroscops\Api\Model\PipelineRead $pipeline, array $configuration): \Psr\Http\Message\ResponseInterface
    {
        $removeStepProbe = new Api\Model\PipelineStepProbeRemovePipelineStepProbCommandInputJsonld();
        $removeStepProbe->setId($pipeline->getId());
        $removeStepProbe->setCode($configuration["pipeline"]["code"]);
//        $removeStepProbe->setProbe(
//            (new Api\Model\ProbeJsonld())
//                ->setCode()
//                ->setLabel()
//        );

        return $this->client->removePipelineStepProbePipelineStepProbeCollection($removeStepProbe, Api\Client::FETCH_RESPONSE);
    }
}
