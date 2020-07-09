<?php

declare(strict_types=1);

namespace Kiboko\Component\ETL\Satellite\Docker;

use Kiboko\Component\ETL\Promise\DeferredInterface;
use Kiboko\Component\ETL\Satellite\ProducerInterface;
use Kiboko\Component\ETL\Satellite\SatelliteInterface;
use Kiboko\Component\ETL\Satellite\ZMQ;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

final class Satellite implements SatelliteInterface
{
    private string $uuid;
    private Dockerfile $dockerfile;
    private iterable $files;
    private Process $daemon;
    private ProducerInterface $messenger;

    public function __construct(string $uuid, Dockerfile $dockerfile, FileInterface ...$files)
    {
        $this->uuid = $uuid;
        $this->dockerfile = $dockerfile;
        $this->files = $files;
        $this->messenger = new ZMQ\Producer();
    }

    public function build(LoggerInterface $logger): void
    {
        $archive = new TarArchive($this->dockerfile, ...$this->files);

        $process = new Process(['docker', 'build', '-t', $this->uuid, '-']);
        $process->setInput($archive->asResource());
        $process->setTimeout(3600);

        $process->run(function ($type, $buffer) use ($logger) {
            if (Process::ERR === $type) {
                $logger->error($buffer);
            } else {
                $logger->info($buffer);
            }
        });
    }

    public function start(LoggerInterface $logger): void
    {
        $this->daemon = new Process(['docker', 'run', '--rm', '-i', $this->uuid]);
        $this->daemon->setTimeout(null);

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