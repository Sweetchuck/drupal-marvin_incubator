<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_incubator;

use Drush\Commands\marvin\CommandsBase;
use Drupal\marvin_incubator\CommandsBaseTrait;

class BuildCommands extends CommandsBase {

  use CommandsBaseTrait;

  protected string $customEventNamePrefix = 'marvin:build';

  /**
   * Builds the code base up from the source code.
   *
   * Usually it runs "yarn install", "tsc" and things like
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
