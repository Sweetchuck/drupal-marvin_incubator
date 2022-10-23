<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_incubator;

use Drupal\marvin\Robo\ArtifactCollectFilesTaskLoader;
use Drupal\marvin\Robo\CopyFilesTaskLoader;
use Drupal\marvin\Robo\PrepareDirectoryTaskLoader;
use Drupal\marvin\Robo\VersionNumberTaskLoader;
use Drupal\marvin_incubator\CommandsBaseTrait;
use Drush\Commands\marvin\ArtifactBuildCommandsBase;
use Robo\Collection\CollectionBuilder;
use Robo\State\Data as RoboStateData;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ArtifactBuildVanillaCommands extends ArtifactBuildCommandsBase {

  use ArtifactCollectFilesTaskLoader;
  use CommandsBaseTrait;
  use CopyFilesTaskLoader;
  use PrepareDirectoryTaskLoader;
  use VersionNumberTaskLoader;

  protected string $customEventNamePrefix = 'marvin:artifact:build';

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
   * @hook on-event marvin:artifact:types
   */
  public function onEventMarvinArtifactTypes(string $projectType): array {
    $types = [];

    if ($projectType === 'incubator') {
      $types['vanilla'] = [
        'label' => 'Vanilla',
        'description' => 'Not customized',
      ];
    }

    return $types;
  }

  /**
   * @command marvin:artifact:build:vanilla
   * @bootstrap none
   *
   * @marvinArgPackages packages
   */
  public function artifactBuildVanilla(array $packages): CollectionBuilder {
    $cb = $this->collectionBuilder();

    $packages = array_intersect_key(
      $this->getManagedDrupalExtensions(),
      array_flip($packages)
    );

    foreach ($packages as $packageName => $packagePath) {
      $cb->addTask($this->delegate('vanilla', $packageName, $packagePath));
    }

    return $cb;
  }

  /**
   * @hook on-event marvin:artifact:build:vanilla
   *
   * @noinspection PhpUnusedParameterInspection
   */
  public function onEventMarvinArtifactBuildVanilla(
    InputInterface $input,
    OutputInterface $output,
    string $packageName,
    string $packagePath
  ): array {
    $buildDir = $this->getConfig()->get('marvin.buildDir');

    return [
      'marvin.detectLatestVersionNumber' => [
        'weight' => -220,
        // @todo Create native task.
        'task' => function (RoboStateData $data): int {
          $data['latestVersionNumber'] = '8.x-1.0';

          return 0;
        },
      ],
      'marvin.composeNextVersionNumber' => [
        'weight' => -210,
        // @todo Create native task.
        'task' => function (RoboStateData $data): int {
          $data['nextVersionNumber'] = '8.x-1.1';

          return 0;
        },
      ],
      'marvin.composeBuildDirPath' => [
        'weight' => -210,
        'task' => function (RoboStateData $data) use ($buildDir, $packageName): int {
          $data['buildDir'] = "$buildDir/$packageName-{$data['nextVersionNumber']}/vanilla";

          return 0;
        },
      ],
      'marvin.prepareDirectory' => [
        'weight' => -200,
        'task' => $this
          ->taskMarvinPrepareDirectory()
          ->deferTaskConfiguration('setWorkingDirectory', 'buildDir'),
      ],
      'marvin.collectFiles' => [
        'weight' => -190,
        'task' => $this
          ->taskMarvinArtifactCollectFiles()
          ->setPackagePath($packagePath),
      ],
      'marvin.copyFiles' => [
        'weight' => -180,
        'task' => $this
          ->taskMarvinCopyFiles()
          ->setSrcDir($packagePath)
          ->deferTaskConfiguration('setDstDir', 'buildDir')
          ->deferTaskConfiguration('setFiles', 'files'),
      ],
      'marvin.bumpVersionNumber' => [
        'weight' => 200,
        'task' => $this
          ->taskMarvinVersionNumberBumpExtensionInfo()
          ->deferTaskConfiguration('setPackagePath', 'buildDir')
          ->deferTaskConfiguration('setVersionNumber', 'nextVersionNumber'),
      ],
    ];
  }

}
