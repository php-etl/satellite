<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Runtime;

use Kiboko\Component\Satellite;
use Psr\Log\LoggerInterface;

final class Factory implements FactoryInterface
{
    public function __construct(
        private Satellite\Adapter\FactoryInterface $adapterFactory,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(array $configuration): RuntimeInterface
    {
        $satellite = ($this->adapterFactory)($configuration)->build();

        if (array_key_exists('http_api', $configuration)) {
            $factory = new Satellite\Runtime\Api\Factory();
        } elseif (array_key_exists('http_hook', $configuration)) {
            $factory = new Satellite\Runtime\HttpHook\Factory();
        } elseif (array_key_exists('pipeline', $configuration)) {
            $factory = new Satellite\Runtime\Pipeline\Factory();
        } elseif (array_key_exists('workflow', $configuration)) {
            $factory = new Satellite\Runtime\Workflow\Factory();
        } else {
            throw new \RuntimeException('No compatible runtime was found for your satellite configuration.');
        }

        $runtime = $factory($configuration);

        $runtime->prepare($satellite, $this->logger);

        $satellite->build($this->logger);

        return $factory($configuration);
    }
}
