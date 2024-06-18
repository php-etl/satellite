<?php

declare(strict_types=1);

use Kiboko\Component\Packaging;

trigger_deprecation('php-etl/satellite', '0.1', 'The "%s" class is deprecated, use "%s" instead.', 'Kiboko\Component\Satellite\Filesystem\Asset\Resource', Packaging\Asset\NativeResource::class);

/*
 * @deprecated since Satellite 0.1, use Kiboko\Component\Packaging\Asset\Resource instead.
 */
class_alias(Packaging\Asset\NativeResource::class, 'Kiboko\Component\Satellite\Filesystem\Asset\Resource');
