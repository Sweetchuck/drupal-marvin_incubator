<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_incubator;

use Drupal\marvin_incubator\CommandsBaseTrait;
use Drush\Commands\marvin\CommandsBase;
use Robo\Contract\TaskInterface;

class BuildCommands extends CommandsBase {

  use CommandsBaseTrait;

  protected string $customEventNamePrefix = 'marvin:build';

  /**
   * Builds the code base up from the source code.
   *
   * Usually it runs "yarn install", "tsc" and things like
   * that.
   *
   * @param string[] $packageNames
   *
   * @command marvin:build
   *
   * @bootstrap none
   *
   * @marvinArgPackages packages
   */
  public function build(array $packageNames): TaskInterface {
    $packageNames = array_intersect_key(
      $this->getManagedDrupalExtensions(),
      array_flip($packageNames)
    );

    $cb = $this->collectionBuilder();
    foreach ($packageNames as $packageName => $packagePath) {
      $cb->addTask($this->delegate('', $packageName, $packagePath));
    }

    return $cb;
  }

}
