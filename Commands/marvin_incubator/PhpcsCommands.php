<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_incubator;

use Drush\Commands\marvin\PhpcsCommandsBase;
use Drupal\marvin_incubator\CommandsBaseTrait;
use Robo\Collection\CollectionBuilder;
use Symfony\Component\Console\Input\InputInterface;

class PhpcsCommands extends PhpcsCommandsBase {

  use CommandsBaseTrait;

  /**
   * @hook on-event marvin:composer:post-install-cmd
   * @hook on-event marvin:composer:post-update-cmd
   */
  public function composerPostInstallAndUpdateCmd() {
    return [
      'marvin.phpcs.config.installed_paths' => [
        'weight' => -200,
        'task' => $this->getTaskPhpcsConfigSetInstalledPaths(getcwd()),
      ],
    ];
  }

  /**
   * @hook on-event marvin:git-hook:pre-commit
   */
  public function onEventMarvinGitHookPreCommit(InputInterface $input): array {
    $package = $this->normalizeManagedDrupalExtensionName($input->getArgument('packagePath'));

    return [
      'marvin.lint.phpcs' => [
        'weight' => -200,
        'task' => $this->lintPhpcs([$package['name']]),
      ],
    ];
  }

  /**
   * @hook on-event marvin:lint
   */
  public function onEventMarvinLint(InputInterface $input): array {
    return [
      'marvin.lint.phpcs' => [
        'weight' => -200,
        'task' => $this->lintPhpcs($input->getArgument('packages')),
      ],
    ];
  }

  /**
   * Runs PHP Code Sniffer.
   *
   * @command marvin:lint:phpcs
   * @bootstrap none
   *
   * @marvinArgPackages packages
   */
  public function lintPhpcs(array $packages): CollectionBuilder {
    $managedDrupalExtensions = $this->getManagedDrupalExtensions();
    $cb = $this->collectionBuilder();
    foreach ($packages as $packageName) {
      $packagePath = $managedDrupalExtensions[$packageName];
      $cb->addTask($this->getTaskLintPhpcsExtension($packagePath));
    }

    return $cb;
  }

}
