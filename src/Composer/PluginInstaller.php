<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Composer;

use Composer\Composer;
use Composer\Installer\BinaryInstaller;
use Composer\Installer\LibraryInstaller;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Util\Filesystem;

final class PluginInstaller extends LibraryInstaller
{
    private iterable $serviceClasses;

    public function __construct(
        private Accumulator $accumulator,
        IOInterface $io,
        Composer $composer,
        $type = 'satellite-plugin',
        Filesystem $filesystem = null,
        BinaryInstaller $binaryInstaller = null
    ) {
        parent::__construct($io, $composer, $type, $filesystem, $binaryInstaller);
        $this->serviceClasses = [];
    }

    public function isInstalled(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        if (!parent::isInstalled($repo, $package)) {
            return false;
        }

        $this->accumulator->idle($package);
        return true;
    }

    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        return parent::install($repo, $package)
            ->then(
                function () use ($repo, $package) {
                    if (isset($package->getExtra()['satellite']['class'])) {
                        $this->serviceClasses[] = $class = $package->getExtra()['satellite']['class'];
                    } else {
                        $this->io->error(strtr(
                            'There is no service class defined for the package %package%, please set the extra.satellite.class parameter in your composer.json file.',
                            [
                                '%package%' => $package->getName(),
                            ]
                        ));
                        return \React\Promise\reject();
                    }

                    $this->accumulator->append($package);

                    return \React\Promise\resolve();
                }
            );
    }

    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        return parent::update($repo, $initial, $target)
            ->then(
                function () use ($repo, $target) {
                    if (!isset($target->getExtra()['satellite']['class'])) {
                        $this->io->error(strtr(
                            'There is no service class defined for the package %package%, please set the extra.satellite.class parameter in your composer.json file.',
                            [
                                '%package%' => $target->getName(),
                            ]
                        ));
                        return \React\Promise\reject();
                    }

                    $this->accumulator->update($target);

                    return \React\Promise\resolve();
                }
            );
    }

    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        return parent::uninstall($repo, $package)
            ->then(
                function () use ($repo, $package) {
                    if (isset($package->getExtra()['satellite']['class'])) {
                        $this->serviceClasses[] = $class = $package->getExtra()['satellite']['class'];
                    } else {
                        $this->io->error(strtr(
                            'There is no service class defined for the package %package%, please set the extra.satellite.class parameter in your composer.json file.',
                            [
                                '%package%' => $package->getName(),
                            ]
                        ));
                        return \React\Promise\reject();
                    }

                    $this->accumulator->uninstall($package);

                    return \React\Promise\resolve();
                }
            );
    }
}
