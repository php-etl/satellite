<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin;

use Kiboko\Contract\Configurator\PipelinePluginInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * POC: Charge des plugins depuis des archives Phar externes via Phar::loadPhar().
 *
 * Convention: chaque .phar doit contenir un fichier `plugin-manifest.php` à la racine
 * qui retourne un tableau avec la clé 'plugin_class' (FQCN du service plugin).
 *
 * @see https://www.php.net/manual/en/phar.loadphar.php
 */
final class PharPluginLoader
{
    /** @var array<string, string> alias => path */
    private array $loadedPhars = [];

    public function __construct(
        private readonly string $pluginsDirectory,
        private readonly ExpressionLanguage $interpreter = new ExpressionLanguage(),
    ) {
    }

    /**
     * Charge tous les .phar du répertoire et retourne les instances de plugins.
     *
     * @return iterable<PipelinePluginInterface>
     */
    public function load(): iterable
    {
        if (!is_dir($this->pluginsDirectory)) {
            return;
        }

        $files = glob($this->pluginsDirectory.'/*.phar') ?: [];
        foreach ($files as $path) {
            if (!is_file($path)) {
                continue;
            }
            try {
                $phar = new \Phar($path, 0);
                $alias = $phar->getAlias();
                \Phar::loadPhar($path);
                $manifestPath = 'phar://'.$alias.'/plugin-manifest.php';
                if (!file_exists($manifestPath)) {
                    continue;
                }
                $manifest = require $manifestPath;
                $pluginClass = $manifest['plugin_class'] ?? null;
                if (!$pluginClass) {
                    continue;
                }
                if (!class_exists($pluginClass)) {
                    continue;
                }
                $plugin = new $pluginClass($this->interpreter);
                if ($plugin instanceof PipelinePluginInterface) {
                    yield $plugin;
                    $this->loadedPhars[$alias] = $path;
                }
            } catch (\Throwable $e) {
                // Ignorer les phars invalides (ex: dépendances manquantes)
                continue;
            }
        }
    }

    /** @return array<string, string> */
    public function getLoadedPhars(): array
    {
        return $this->loadedPhars;
    }
}
