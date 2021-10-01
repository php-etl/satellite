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
            if ($package instanceof Package\AliasPackage
                || $package->getType() !== 'satellite-plugin'
            ) {
                continue;
            }

            if (!isset($package->getExtra()['satellite']['class'])) {
                $io->error(strtr(
                    'There is no service class defined for the package %package%, please set the extra.satellite.class parameter in your composer.json file.',
                    [
                        '%package%' => $package->getName(),
                    ]
                ));
                continue;
            }

            $io->write(strtr(
                '  - Using satellite plugin <info>%package%</>.',
                [
                    '%package%' => $package->getPrettyName(),
                ]
            ));

            $packages->append($package);
        }

        file_put_contents('.gyro.php', $packages);
    }
}
