<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Cloud;

use Kiboko\Component\Satellite;
use Kiboko\Component\Packaging;
use Kiboko\Component\Satellite\SatelliteBuilderInterface;
use Kiboko\Contract\Packaging as PackagingContract;

final class SatelliteBuilder implements Satellite\SatelliteBuilderInterface
{
    public function build(): Satellite\SatelliteInterface
    {
        if (!file_exists($this->workdir)) {
            mkdir($this->workdir, 0775, true);
        }

        $composer = new Satellite\Adapter\Composer($this->workdir);
        $satellite = new Satellite\Adapter\Filesystem\Satellite(
            $this->workdir,
            $composer,
        );

        if ($this->composerJsonFile !== null) {
            $satellite->withFile($this->composerJsonFile);
            if ($this->composerLockFile !== null) {
                $satellite->withFile($this->composerLockFile);
            }

            // FIXME: finish the Sylius API client migration
            $composer->addGithubRepository('sylius-api-php-client', 'https://github.com/gplanchat/sylius-api-php-client');

            $composer->install();
        } else {
            $composer->init(sprintf('satellite/%s', substr(hash('sha512', random_bytes(64)), 0, 64)));
            $composer->minimumStability('dev');

            // FIXME: finish the Sylius API client migration
            $composer->addGithubRepository('sylius-api-php-client', 'https://github.com/gplanchat/sylius-api-php-client');

            $composer->autoload($this->composerAutoload);
        }

        $satellite->dependsOn(...$this->composerRequire);

        return $satellite;
    }
}
