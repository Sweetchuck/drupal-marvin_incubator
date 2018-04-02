<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_incubator\Artifact;

use Drush\Commands\marvin\Artifact\ArtifactBuildCommandsBase;
use Drupal\marvin_incubator\CommandsBaseTrait;
use Robo\Collection\CollectionBuilder;

class ArtifactBuildCommands extends ArtifactBuildCommandsBase {

  use CommandsBaseTrait;

  /**
   * @command marvin:artifact:build
   * @bootstrap none
   *
   * @marvinArgPackages packages
   */
  public function artifactBuild(
    array $packages,
    array $options = [
      'type' => 'vanilla',
    ]
  ): CollectionBuilder {
    $packages = array_intersect_key(
      $this->getManagedDrupalExtensions(),
      array_flip($packages)
    );

    $cb = $this->collectionBuilder();
    foreach ($packages as $packageName => $packagePath) {
      $cb->addTask($this->delegate('build', $packageName, $packagePath));
    }

    return $cb;
  }

}
