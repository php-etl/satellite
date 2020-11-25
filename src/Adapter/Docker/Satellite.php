<?php

declare(strict_types=1);

namespace Kiboko\Component\ETL\Satellite\Adapter\Docker;

use Docker\API\Model\ContainersCreatePostBody;
use Docker\API\Model\ContainersCreatePostBodyNetworkingConfig;
use Docker\API\Model\EndpointSettings;
use Docker\Docker;
use Kiboko\Component\ETL\Promise\DeferredInterface;
use Kiboko\Component\ETL\Satellite\ProducerInterface;
use Kiboko\Component\ETL\Satellite\SatelliteInterface;
use Kiboko\Component\ETL\Satellite\ZMQ;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

final class Satellite implements SatelliteInterface
{
    private string $imageTag;
    private Dockerfile $dockerfile;
    private iterable $files;
    private Docker $docker;
    private ProducerInterface $messenger;

    public function __construct(
        string $imageTag,
        Dockerfile $dockerfile,
        FileInterface ...$files
    ) {
        $this->imageTag = $imageTag;
        $this->dockerfile = $dockerfile;
        $this->files = $files;
        $this->messenger = new ZMQ\Producer();
        $this->docker = Docker::create();
    }

    public function build(LoggerInterface $logger): void
    {
        $archive = new TarArchive($this->dockerfile, ...$this->files);

        $this->docker->imageBuild(
            $archive->asResource(),
            [
                't' => $this->imageTag,
                'rm' => true,
                'labels' => []
            ]
        );
    }

    public function start(LoggerInterface $logger, NetworkInterface $network): void
    {
        $container = new ContainersCreatePostBody();
        $container->setImage($this->imageTag);
        $container->setNetworkingConfig(
            (new ContainersCreatePostBodyNetworkingConfig())
                ->setEndpointsConfig(new \ArrayObject([
                    (new EndpointSettings())
                        ->setNetworkID((string) $network),
                ]))
        );
        $container->setExposedPorts();
        $container->setEnv();
        $container->setAttachStdout(true);
        $container->setLabels(new \ArrayObject(['docker-php-test' => 'true']));

        $this->docker->containerCreate($container);

        $logger->debug('Starting satellite...'.PHP_EOL);

        $this->daemon->start(function ($type, $buffer) use ($logger) {
            if (Process::ERR === $type) {
                $logger->error($buffer);
            } else {
                $logger->info($buffer);
            }
        });

        $logger->debug('Satellite started.'.PHP_EOL);
    }

    public function poll(LoggerInterface $logger): void
    {
        $logger->debug('Polling.'.PHP_EOL);
//        $this->daemon->waitUntil(function ($type, $buffer) use ($logger) {
//            if (Process::ERR === $type) {
//                $logger->error($buffer);
//            } else {
//                $logger->info($buffer);
//            }
//            return true;
//        });

//        $logger->debug('Polling.'.PHP_EOL);
        $this->messenger->poll();
    }

    public function send(\JsonSerializable $payload): DeferredInterface
    {
        return $this->messenger->send(new ZMQ\Producer\Request($payload));
    }

    public function stop(): void
    {
        $this->daemon->stop();
    }
}