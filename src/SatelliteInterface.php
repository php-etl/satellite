<?php

namespace Kiboko\Component\ETL\Satellite;

use Kiboko\Component\ETL\Promise\DeferredInterface;
use Kiboko\Component\ETL\Satellite\Adapter\Docker\NetworkInterface;
use Psr\Log\LoggerInterface;

interface SatelliteInterface
{
    public function build(LoggerInterface $logger): void;

//    public function start(LoggerInterface $logger, NetworkInterface $network): void;

//    public function send(\JsonSerializable $payload): DeferredInterface;

//    public function stop(): void;

//    public function poll(LoggerInterface $logger): void;
}
