<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_incubator\Build;

use Drush\Commands\marvin\Build\NpmCommandsBase;
use Drupal\marvin_incubator\CommandsBaseTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuildNpmCommands extends NpmCommandsBase {

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
      'marvin.build.npm' => [
        'task' => $this->getTaskNpmInstallPackage($packageName, $packagePath),
        'weight' => 10,
      ],
    ];
  }

  /**
   * Runs "yarn install".
   *
   * @command marvin:build:npm
   * @bootstrap none
   *
   * @marvinArgPackages packages
   */
  public function buildNpm(array $packages) {
    $packages = array_intersect_key(
      $this->getManagedDrupalExtensions(),
      array_flip($packages)
    );

    return $this->getTaskNpmInstallPackages($packages);
  }

}
