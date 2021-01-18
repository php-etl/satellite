<?php

namespace Kiboko\Component\Satellite;

use Psr\Log\LoggerInterface;

interface SatelliteInterface
{
    public function dependsOn(string ...$dependencies): void;

    public function build(LoggerInterface $logger): void;

//    public function start(LoggerInterface $logger, NetworkInterface $network): void;

//    public function send(\JsonSerializable $payload): DeferredInterface;

//    public function stop(): void;

//    public function poll(LoggerInterface $logger): void;
}
