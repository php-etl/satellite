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

    public function __construct(
        string $imageTag,
        Dockerfile $dockerfile,
        FileInterface ...$files
    ) {
        $this->imageTag = $imageTag;
        $this->dockerfile = $dockerfile;
        $this->files = $files;
    }

    public function push(FileInterface ...$files): void
    {
        array_push($this->files, ...$files);
    }

    public function build(LoggerInterface $logger): void
    {
        $archive = new TarArchive($this->dockerfile, ...$this->files);

        $process = new Process([
            'docker', 'build', '-t', $this->imageTag, '--rm', '-'
        ]);

        $process->setInput($archive->asResource());

        $process->setTimeout(300);

        stream_copy_to_stream($archive->asResource(), fopen(getcwd() . '/archive.tar', 'w'));

        $process->run(function ($type, $buffer) use ($logger) {
            if (Process::ERR === $type) {
                $logger->error($buffer);
            } else {
                $logger->debug($buffer);
            }
        });

        if ($process->getExitCode() !== 0) {
            throw new \RuntimeException('Process exited unexpectedly.');
        }
    }
}
