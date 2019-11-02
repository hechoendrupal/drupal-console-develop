<?php
namespace Drupal\Console\Develop\Composer;

use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Installer\LibraryInstaller;
use InvalidArgumentException;

class ConsoleDevelopInstaller extends LibraryInstaller
{
  /**
   * Decides if the installer supports the given type
   *
   * @param string $packageType
   * @return bool
   */
  public function supports($packageType)
  {
    return 'drupal-console-library' === $packageType;
  }

  /**
   * Checks that provided package is installed.
   *
   * @param InstalledRepositoryInterface $repo repository in which to check
   * @param PackageInterface $package package instance
   *
   * @return bool
   */
  public function isInstalled(InstalledRepositoryInterface $repo, PackageInterface $package)
  {
    if (!$repo->hasPackage($package)) {
        return false;
    }
    $installPath = $this->getInstallPath($package);
    if (is_readable($installPath)) {
        return true;
    }
    return (Platform::isWindows() && $this->filesystem->isJunction($installPath)) || is_link($installPath);
  }

  /**
   * Installs specific package.
   *
   * @param InstalledRepositoryInterface $repo repository in which to check
   * @param PackageInterface $package package instance
   */
  public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
  {
    $this->initializeVendorDir();
    $downloadPath = $this->getInstallPath($package);
    // remove the binaries if it appears the package files are missing
    if (!is_readable($downloadPath) && $repo->hasPackage($package)) {
        $this->binaryInstaller->removeBinaries($package);
    }
    $this->installCode($package);
    $this->binaryInstaller->installBinaries($package, $this->getInstallPath($package));
    if (!$repo->hasPackage($package)) {
        $repo->addPackage(clone $package);
    }
  }

  /**
   * Updates specific package.
   *
   * @param InstalledRepositoryInterface $repo repository in which to check
   * @param PackageInterface $initial already installed package version
   * @param PackageInterface $target updated version
   *
   * @throws InvalidArgumentException if $initial package is not installed
   */
  public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
  {
    if (!$repo->hasPackage($initial)) {
        throw new \InvalidArgumentException('Package is not installed: '.$initial);
    }
    $this->initializeVendorDir();
    $this->binaryInstaller->removeBinaries($initial);
    $this->updateCode($initial, $target);
    $this->binaryInstaller->installBinaries($target, $this->getInstallPath($target));
    $repo->removePackage($initial);
    if (!$repo->hasPackage($target)) {
        $repo->addPackage(clone $target);
    }
  }

  /**
   * Uninstalls specific package.
   *
   * @param InstalledRepositoryInterface $repo repository in which to check
   * @param PackageInterface $package package instance
   */
  public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
  {
    if (!$repo->hasPackage($package)) {
        throw new \InvalidArgumentException('Package is not installed: '.$package);
    }
    $this->removeCode($package);
    $this->binaryInstaller->removeBinaries($package);
    $repo->removePackage($package);
    $downloadPath = $this->getPackageBasePath($package);
    if (strpos($package->getName(), '/')) {
        $packageVendorDir = dirname($downloadPath);
        if (is_dir($packageVendorDir) && $this->filesystem->isDirEmpty($packageVendorDir)) {
            Silencer::call('rmdir', $packageVendorDir);
        }
    }
  }

  /**
   * Returns the installation path of a package
   *
   * @param PackageInterface $package
   * @return string           path
   */
  public function getInstallPath(PackageInterface $package)
  {
    return 'vendor/drupal/'.$package->getPrettyName();
  }
}
