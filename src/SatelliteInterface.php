<?php

declare(strict_types=1);

trigger_deprecation('php-etl/satellite', '0.4', 'The "%s" interface is deprecated, use "%s" instead.', 'Kiboko\Component\Satellite\SatelliteInterface', Kiboko\Contract\Configurator\SatelliteInterface::class);

/*
 * @deprecated since Satellite 0.4, use Kiboko\Contract\Configurator\SatelliteInterface instead.
 */
class_alias(Kiboko\Contract\Configurator\SatelliteInterface::class, 'Kiboko\Component\Satellite\SatelliteInterface');
