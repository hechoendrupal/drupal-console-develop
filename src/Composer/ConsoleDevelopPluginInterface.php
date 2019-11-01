<?php
namespace Drupal\Console\Develop\Composer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class ConsoleDevelopPluginInterface implements PluginInterface
{
  /**
   * Apply plugin modifications to Composer
   *
   * @param Composer $composer
   * @param IOInterface $io
   */
  public function activate(Composer $composer, IOInterface $io)
  {
    $installer = new ConsoleDevelopInstaller($io, $composer);
    $composer->getInstallationManager()->addInstaller($installer);
  }
}
