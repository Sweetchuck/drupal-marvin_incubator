<?php

namespace Drush\Commands\marvin_incubator\Qa;

use Consolidation\AnnotatedCommand\CommandData;
use Drush\Commands\marvin\Qa\ComposerValidateCommandsBase;
use Robo\Contract\TaskInterface;
use Symfony\Component\Console\Input\InputInterface;

class ComposerValidateCommands extends ComposerValidateCommandsBase {

  /**
   * @hook on-event marvin-git-hook-pre-commit
   */
  public function onEventMarvinGitHookPreCommit(InputInterface $input): array {
    $package = $this->normalizeManagedDrupalExtensionName($input->getArgument('packagePath'));

    return [
      'marvin:qa:composer:validate' => [
        'task' => $this->composerValidate([$package['name']]),
      ],
    ];
  }

  /**
   * @hook validate marvin:qa:composer:validate
   */
  public function composerValidateHookValidate(CommandData $commandData) {
    $this->hookValidateArgumentPackages($commandData);
  }

  /**
   * Runs `composer validate`.
   *
   * @command marvin:qa:composer:validate
   * @bootstrap none
   */
  public function composerValidate(array $packages): TaskInterface {
    $cb = $this->collectionBuilder();

    $managedDrupalExtensions = $this->getManagedDrupalExtensions();
    foreach ($packages as $packageName) {
      $packagePath = $managedDrupalExtensions[$packageName];
      $cb->addTask($this->getTaskComposerValidate($packagePath));
    }

    return $cb;
  }

}
