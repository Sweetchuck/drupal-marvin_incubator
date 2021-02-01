<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_incubator;

use Drush\Commands\marvin\ComposerCommandsBase;
use Drupal\marvin_incubator\CommandsBaseTrait;
use Robo\Collection\CollectionBuilder;
use Symfony\Component\Console\Input\InputInterface;

class ComposerValidateCommands extends ComposerCommandsBase {

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
      $package = $managedDrupalExtensions[$packageName];
      $cb->addTask($this->getTaskComposerValidate($package['path']));
    }

    return $cb;
  }

}
