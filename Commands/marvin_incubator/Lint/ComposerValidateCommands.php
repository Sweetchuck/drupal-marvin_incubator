<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_incubator\Lint;

use Drush\Commands\marvin\Lint\ComposerValidateCommandsBase;
use Drupal\marvin_incubator\CommandsBaseTrait;
use Robo\Collection\CollectionBuilder;
use Symfony\Component\Console\Input\InputInterface;

class ComposerValidateCommands extends ComposerValidateCommandsBase {

  use CommandsBaseTrait;

  /**
   * @hook on-event marvin:git-hook:pre-commit
   */
  public function onEventMarvinGitHookPreCommit(InputInterface $input): array {
    $package = $this->normalizeManagedDrupalExtensionName($input->getArgument('packagePath'));

    return [
      'marvin:lint:composer-validate' => [
        'task' => $this->composerValidate([$package['name']]),
      ],
    ];
  }

  /**
   * Runs `composer validate`.
   *
   * @command marvin:lint:composer-validate
   * @bootstrap none
   *
   * @marvinArgPackages packages
   */
  public function composerValidate(array $packages): CollectionBuilder {
    $cb = $this->collectionBuilder();

    $managedDrupalExtensions = $this->getManagedDrupalExtensions();
    foreach ($packages as $packageName) {
      $packagePath = $managedDrupalExtensions[$packageName];
      $cb->addTask($this->getTaskComposerValidate($packagePath));
    }

    return $cb;
  }

}
