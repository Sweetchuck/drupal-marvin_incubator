<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_incubator;

use Drupal\marvin_incubator\CommandsBaseTrait;
use Drush\Commands\marvin\ArtifactBuildCommandsBase;
use Robo\Collection\CollectionBuilder;
use Robo\Contract\TaskInterface;
use Robo\State\Data as RoboStateData;

class ArtifactBuildCommands extends ArtifactBuildCommandsBase {

  use CommandsBaseTrait;

  protected function isApplicable(string $projectType): bool {
    return TRUE;
  }

  protected function getTaskCollectChildExtensionDirs(): \Closure|TaskInterface {
    return function (RoboStateData $data): int {
      $data['customExtensionDirs'] = $this->getManagedDrupalExtensions();

      return 0;
    };
  }

  /**
   * Build release artifacts from the given packages.
   *
   * @param string[] $packageNames
   *   Package names.
   * @param array $options
   *   Additional options.
   *
   * @phpstan-param array{type: string} $options
   *
   * @command marvin:artifact:build
   *
   * @bootstrap none
   *
   * @marvinArgPackages packages
   *
   * @todo Every artifact type should have dedicated command, instead of --type.
   */
  public function artifactBuild(
    array $packageNames,
    array $options = [
      'type' => 'vanilla',
    ]
  ): CollectionBuilder {
    $packageNames = array_intersect_key(
      $this->getManagedDrupalExtensions(),
      array_flip($packageNames)
    );

    $cb = $this->collectionBuilder();
    foreach ($packageNames as $packageName => $packagePath) {
      $cb->addTask($this->delegate('build', $packageName, $packagePath));
    }

    return $cb;
  }

}
