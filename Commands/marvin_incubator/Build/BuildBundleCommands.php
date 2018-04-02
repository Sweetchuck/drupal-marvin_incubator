<?php

namespace Drush\Commands\marvin_incubator\Build;

use Drush\Commands\marvin\Build\BuildBundleCommandsBase;
use Drupal\marvin_incubator\CommandsBaseTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuildBundleCommands extends BuildBundleCommandsBase {

  use CommandsBaseTrait;

  /**
   * @hook on-event marvin:build
   */
  public function onEventMarvinBuild(
    InputInterface $input,
    OutputInterface $output,
    string $packageName,
    string $packagePath
  ): array {
    return [
      'marvin.build.bundle' => [
        'task' => $this->getTaskBuildBundlePackage($packageName, $packagePath),
        'weight' => 30,
      ],
    ];
  }

  /**
   * @command marvin:build:bundle
   * @bootstrap none
   *
   * @marvinArgPackages packages
   */
  public function buildBundle(array $packages) {
    $packages = array_intersect_key(
      $this->getManagedDrupalExtensions(),
      array_flip($packages)
    );

    return $this->getTaskBuildBundlePackages($packages);
  }

}
