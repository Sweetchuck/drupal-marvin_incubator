<?php

namespace Drush\Commands\marvin_incubator\Qa;

use Consolidation\AnnotatedCommand\CommandData;
use Drush\Commands\marvin\Qa\LintPhpcsCommandsBase;
use Robo\Collection\CollectionBuilder;
use Symfony\Component\Console\Input\InputInterface;

class LintPhpcsCommands extends LintPhpcsCommandsBase {

  /**
   * @hook on-event marvin-git-hook-pre-commit
   */
  public function onEventMarvinGitHookPreCommit(InputInterface $input): array {
    $package = $this->normalizeManagedDrupalExtensionName($input->getArgument('packagePath'));

    return [
      'marvin.qa.lint.phpcs' => [
        'task' => $this->lintPhpcs([$package['name']]),
      ],
    ];
  }

  /**
   * @hook on-event marvin-qa-lint
   */
  public function onEventMarvinQaLint(InputInterface $input): array {
    return [
      'marvin.qa.lint.phpcs' => [
        'task' => $this->lintPhpcs($input->getArgument('packages')),
      ],
    ];
  }

  /**
   * @hook validate marvin:qa:lint:phpcs
   */
  public function lintPhpcsHookValidate(CommandData $commandData) {
    $this->hookValidateArgumentPackages($commandData);
  }

  /**
   * Runs PHP Code Sniffer.
   *
   * @command marvin:qa:lint:phpcs
   * @bootstrap none
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
