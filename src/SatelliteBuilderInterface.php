<?php

declare(strict_types=1);

trigger_deprecation('php-etl/satellite', '0.4', 'The "%s" interface is deprecated, use "%s" instead.', 'Kiboko\\Component\\Satellite\\SatelliteBuilderInterface', \Kiboko\Contract\Configurator\SatelliteBuilderInterface::class);

/*
 * @deprecated since Satellite 0.4, use Kiboko\Contract\Configurator\SatelliteBuilderInterface instead.
 */
class_alias(\Kiboko\Contract\Configurator\SatelliteBuilderInterface::class, 'Kiboko\\Component\\Satellite\\SatelliteBuilderInterface');
