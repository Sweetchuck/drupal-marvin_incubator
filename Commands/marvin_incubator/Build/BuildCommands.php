<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_incubator\Build;

use Drush\Commands\marvin\Build\BuildCommandsBase;
use Drupal\marvin_incubator\CommandsBaseTrait;

class BuildCommands extends BuildCommandsBase {

  use CommandsBaseTrait;

  /**
   * Builds the code base up from the source code.
   *
   * Usually it runs "yarn install", "tsc", "bundle install" and things like
   * that.
   *
   * @command marvin:build
   * @bootstrap none
   *
   * @marvinArgPackages packages
   */
  public function build(array $packages) {
    $packages = array_intersect_key(
      $this->getManagedDrupalExtensions(),
      array_flip($packages)
    );

    $cb = $this->collectionBuilder();
    foreach ($packages as $packageName => $packagePath) {
      $cb->addTask($this->delegate('', $packageName, $packagePath));
    }

    return $cb;
  }

}
