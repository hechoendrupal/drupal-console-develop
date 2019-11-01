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
    // TODO: Implement isInstalled() method.
  }

  /**
   * Installs specific package.
   *
   * @param InstalledRepositoryInterface $repo repository in which to check
   * @param PackageInterface $package package instance
   */
  public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
  {
    // TODO: Implement install() method.
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
    // TODO: Implement update() method.
  }

  /**
   * Uninstalls specific package.
   *
   * @param InstalledRepositoryInterface $repo repository in which to check
   * @param PackageInterface $package package instance
   */
  public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
  {
    // TODO: Implement uninstall() method.
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
