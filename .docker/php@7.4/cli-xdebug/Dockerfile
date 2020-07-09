ARG ORIGINAL_IMAGE

FROM ${ORIGINAL_IMAGE}

RUN set -ex\
    && apk add docker zeromq-dev

RUN apk add --virtual .build-deps autoconf git $PHPIZE_DEPS

RUN git clone https://github.com/gplanchat/php-zmq.git \
    && cd php-zmq \
    && phpize \
    && autoconf \
    && ./configure \
    && make \
    && make install \
    && cd - \
    && echo "extension=zmq.so" > /usr/local/etc/php/conf.d/zmq.ini

RUN apk del .build-deps
