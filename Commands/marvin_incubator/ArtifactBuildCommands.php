<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_incubator;

use Drupal\marvin_incubator\CommandsBaseTrait;
use Drush\Commands\marvin\ArtifactBuildCommandsBase;
use Robo\Collection\CollectionBuilder;
use Robo\State\Data as RoboStateData;

class ArtifactBuildCommands extends ArtifactBuildCommandsBase {

  use CommandsBaseTrait;

  protected function isApplicable(string $projectType): bool {
    return TRUE;
  }

  protected function getTaskCollectChildExtensionDirs() {
    return function (RoboStateData $data): int {
      $data['customExtensionDirs'] = $this->getManagedDrupalExtensions();

      return 0;
    };
  }

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
