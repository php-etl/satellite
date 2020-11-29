<?php declare(strict_types=1);

namespace Kiboko\Component\ETL\Satellite\Worker;

use Kiboko\Component\ETL\Satellite\Adapter\docker;
use Kiboko\Component\ETL\Satellite\SatelliteInterface;

final class PHP implements SatelliteInterface
{
    private SatelliteInterface $decorated;

    public function __construct(string $uuid)
    {
        $this->decorated = new Docker\Satellite(
            $uuid,
            new Docker\Dockerfile(
                new Docker\Dockerfile\From('kiboko/php:7.4-cli'),
                new Docker\Dockerfile\Run(<<<RUN
                    set -ex \\
                        && apk add docker zeromq-dev \\
                        && apk add --virtual .build-deps autoconf git \$PHPIZE_DEPS \\
                        && git clone https://github.com/gplanchat/php-zmq.git \\
                        && cd php-zmq \\
                        && phpize \\
                        && autoconf \\
                        && ./configure \\
                        && make \\
                        && make install \\
                        && cd - \\
                        && echo "extension=zmq.so" > /usr/local/etc/php/conf.d/zmq.ini \\
                        && apk del .build-deps
                    RUN),
                new Docker\Dockerfile\Run(<<<RUN
                    set -ex \\
                        && mkdir -p /var/www/html \\
                        && cd /var/www/html \\
                        && composer require ramsey/uuid
                    RUN),
                new Docker\Dockerfile\Workdir('/var/www/html/'),
                new Docker\Dockerfile\Copy('main.php', '/var/www/html/main.php'),
                new Docker\Dockerfile\Copy('function.php', '/var/www/html/function.php'),
                new Docker\Dockerfile\Cmd('php main.php tcp://host.docker.internal:5557 tcp://host.docker.internal:5559'),
                ...Docker\Dockerfile\Copy::directory(__DIR__ . '/../src/', '/var/www/html/library/'),
            ),
            new Docker\File('main.php', new Docker\Asset\File(__DIR__ . '/../src/Docker/Runtime/main.php')),
            new Docker\File('function.php', new Docker\Asset\InMemory(<<<SOURCE
                <?php
                
                use Kiboko\\Component\\ETL\\Satellite\\ZMQ\\Consumer;

                return function(\\JsonSerializable \$request) {
                    var_dump(\$request->getUuid(), \$request->getPayload());

                    return new class(['success' => true, \$request->getUuid()]) implements \JsonSerializable {
                        public array \$payload;

                        public function __construct(array \$payload)
                        {
                            \$this->payload = \$payload;
                        }

                        public function jsonSerialize()
                        {
                            return \$this->payload;
                        }
                    };
                };
                SOURCE
            )),
            ...Docker\File::directory(__DIR__ . '/../src/'),
        );
    }
}
