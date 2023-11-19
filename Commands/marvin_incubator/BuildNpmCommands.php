<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_incubator;

use Drupal\marvin_incubator\CommandsBaseTrait;
use Drush\Commands\marvin\NpmCommandsBase;
use Robo\Contract\TaskInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuildNpmCommands extends NpmCommandsBase {

  use CommandsBaseTrait;

  /**
   * @hook on-event marvin:build
   *
   * @phpstan-return array<string, marvin-task-definition>
   */
  public function onEventMarvinBuild(
    InputInterface $input,
    OutputInterface $output,
    string $packageName,
    string $packagePath,
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
   * @param string[] $packageNames
   *
   * @command marvin:build:npm
   * @bootstrap none
   *
   * @marvinArgPackages packages
   */
  public function buildNpm(array $packageNames): TaskInterface {
    $extensions = array_intersect_key(
      $this->getManagedDrupalExtensions(),
      array_flip($packageNames)
    );

    $packages = [];
    foreach ($extensions as $name => $extension) {
      $packages[$name] = $extension['pathRelative'];
    }

    return $this->getTaskNpmInstallPackages($packages);
  }

}
