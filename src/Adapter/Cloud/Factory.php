<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Cloud;

use Gyroscops\Api;
use Jane\Component\OpenApiRuntime\Client\Plugin\AuthenticationRegistry;
use Kiboko\Component\Satellite;
use Kiboko\Contract\Configurator\Adapter;
use Kiboko\Contract\Configurator\AdapterConfigurationInterface;

#[Adapter(name: "cloud")]
final class Factory implements Satellite\Adapter\CloudFactoryInterface
{
    private Configuration $configuration;

    public function __construct()
    {
        $this->configuration = new Configuration();
    }

    public function configuration(): AdapterConfigurationInterface
    {
        return $this->configuration;
    }

    public function __invoke(array $configuration): \Psr\Http\Message\ResponseInterface
    {
        $token = json_decode(file_get_contents(getcwd() . '/.gyroscops/auth.json'), true)["token"];

        $authenticationRegistry = new AuthenticationRegistry([new Api\Authentication\ApiKeyAuthentication($token)]);
        $client = Api\Client::create(null, [$authenticationRegistry]);

        $data = new Api\Model\PipelineDeclarePipelineCommandInput();
        $data->setLabel($configuration["cloud"]["name"]);
        $data->setCode($configuration["cloud"]["name"]);
        $data->setProject($configuration["cloud"]["project"]);

        if (array_key_exists('filesystem', $configuration["cloud"])) {
            $data->setBuildPath($configuration["cloud"]["filesystem"]["path"]);
        } else {
            $data->setFromImage($configuration["cloud"]["docker"]["from"]);
            $data->setTargetImage($configuration["cloud"]["docker"]["workdir"]);
        }

        $response = $client->declarePipelinePipelineCollection($data);

        foreach($configuration["pipeline"]["steps"] as $step) {
            $stepInput = new Api\Model\PipelineStepAppendPipelineStepCommandInput();
            $stepInput->setLabel('');
            $stepInput->setCode('');
            $stepInput->setConfiguration($step);
            $stepInput->setProbes([]);

            $client->appendPipelineStepPipelineStepCollection($stepInput);
        }
    }
}
