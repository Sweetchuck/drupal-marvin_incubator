<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_incubator\Compass;

use Drush\Commands\marvin\Compass\CompassCommandsBase;
use Drupal\marvin_incubator\CommandsBaseTrait;
use Robo\Collection\CollectionBuilder;

class CompassCommands extends CompassCommandsBase {

  use CommandsBaseTrait;

  /**
   * Runs 'compass clean' if any 'config.rb' files exist in the package.
   *
   * @command marvin:compass:clean
   * @bootstrap none
   *
   * @marvinArgPackages packages
   */
  public function compassClean(array $packages): CollectionBuilder {
    $packages = array_intersect_key(
      $this->getManagedDrupalExtensions(),
      array_flip($packages)
    );

    return $this->getTaskCompassCleanPackages($packages);
  }

  /**
   * Runs 'compass compile' if any 'config.rb' files exist in the package.
   *
   * @command marvin:compass:compile
   *
   * @marvinArgPackages packages
   */
  public function compassCompile(array $packages): CollectionBuilder {
    $packages = array_intersect_key(
      $this->getManagedDrupalExtensions(),
      array_flip($packages)
    );

    return $this->getTaskCompassCompilePackages($packages);
  }

}
