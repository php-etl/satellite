<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite;

use Composer\IO\IOInterface;
use Composer\Package;
use Composer\Repository\RepositoryInterface;
use Composer\Script\Event;
use Kiboko\Component\Satellite\Composer\Accumulator;

final class ComposerScripts
{
    public static function postInstall(Event $event): void
    {
        self::updatePlugins(
            $packages = new Composer\Accumulator(),
            $repository = $event->getComposer()
                ->getRepositoryManager()
                ->getLocalRepository(),
            $event->getIO()
        );
    }

    public static function postUpdate(Event $event): void
    {
        self::updatePlugins(
            $packages = new Composer\Accumulator(),
            $repository = $event->getComposer()
                ->getRepositoryManager()
                ->getLocalRepository(),
            $event->getIO()
        );
    }

    private static function updatePlugins(
        Accumulator $packages,
        RepositoryInterface $repository,
        IOInterface $io
    ): void {
        /** @var Package\BasePackage $package */
        foreach ($repository->getPackages() as $package) {
            if ($package instanceof Package\AliasPackage) {
                continue;
            }
            if ('satellite-plugin' !== $package->getType()
                && 'gyroscops-plugin' !== $package->getType()
                && 'php-etl/satellite' !== $package->getName()
            ) {
                continue;
            }
            if ('satellite-plugin' === $package->getType()) {
                $io->warning(strtr(
                    'The package %package% is using a deprecated type: "satellite-plugin", you may upgrade it to use the'
                        .' "gyroscops-plugin" type, the support for this type may disappear at any time.',
                    [
                        '%package%' => $package->getName(),
                    ]
                ));
            }

            if (!isset($package->getExtra()['satellite']['class'])
                && !isset($package->getExtra()['gyroscops']['plugins'])
                && !isset($package->getExtra()['gyroscops']['adapters'])
                && !isset($package->getExtra()['gyroscops']['runtimes'])
                && !isset($package->getExtra()['gyroscops']['actions'])
            ) {
                $io->error(strtr(
                    'There is no service class defined for the package %package%, please set the extra.gyroscops.plugins,'
                    .' extra.gyroscops.adapters or extra.gyroscops.runtimes parameters in your composer.json file. The'
                    .' support for this type may disappear at any time.',
                    [
                        '%package%' => $package->getName(),
                    ]
                ));
                continue;
            }

            if (isset($package->getExtra()['satellite']['class'])) {
                $io->warning(strtr(
                    'The package %package% is using a deprecated configuration: extras.satellite.class, you may upgrade'
                        .' it to use the extra.gyroscops.plugins, extra.gyroscops.adapters or extra.gyroscops.runtimes'
                        .' parameters in your composer.json file. The support for this type may disappear at any time.',
                    [
                        '%package%' => $package->getName(),
                    ]
                ));
            }

            $io->write(strtr(
                '  - Registering <fg=yellow>Gyroscops</> plugins from <info>%package%</> (<comment>%version%</>).',
                [
                    '%package%' => $package->getPrettyName(),
                    '%version%' => $package->getPrettyVersion(),
                ]
            ));

            $packages->append($package);
        }

        file_put_contents('.gyro.php', $packages);
    }
}
