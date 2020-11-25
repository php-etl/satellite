<?php declare(strict_types=1);

namespace Kiboko\Component\ETL\Satellite\Adapter\Docker\PHP\Extension;

use Kiboko\Component\ETL\Satellite\Adapter\Docker\Dockerfile;

final class ZMQ implements Dockerfile\LayerInterface
{
    public function __toString()
    {
        return (string) new Dockerfile\Run(<<<RUN
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
            RUN);
    }
}