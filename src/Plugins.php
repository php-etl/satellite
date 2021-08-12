<?php
/**
 * Diglin GmbH - Switzerland.
 *
 * @author      Sylvain Rayé <support at diglin.com>
 * @copyright   2021 - Diglin (https://www.diglin.com)
 */

namespace Kiboko\Component\Satellite;

class Plugins
{
    const DEFAULT_ETL_PLUGINS_PATH = __DIR__ . '/../../../../.etl_plugins.php';

    /**
     * @var array<\Kiboko\Contract\Configurator\FactoryInterface>
     */
    private array $plugins = [];

    public function __construct(?string $filename = self::DEFAULT_ETL_PLUGINS_PATH)
    {
        if (file_exists($filename)) {
            $this->plugins = include $filename;
        } else {
            throw new \Exception(sprintf('Filename %s not found', $filename));
        }
    }

    public function getPlugins(): array
    {
        return $this->plugins;
    }
}
