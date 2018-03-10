<?php

namespace Drush\Commands\marvin_incubator\Qa;

use Consolidation\AnnotatedCommand\CommandData;
use Drush\Commands\marvin\Qa\LintCommandsBase;
use Robo\Collection\CollectionBuilder;
use Symfony\Component\Console\Input\InputInterface;

class LintScssCommands extends LintCommandsBase {

  /**
   * @hook on-event marvin-git-hook-pre-commit
   */
  public function onEventMarvinGitHookPreCommit(InputInterface $input): array {
    $package = $this->normalizeManagedDrupalExtensionName($input->getArgument('packagePath'));

    return [
      'marvin.qa.lint.scss' => [
        'task' => $this->lintScss([$package['name']]),
      ],
    ];
  }

  /**
   * @hook on-event marvin-qa-lint
   */
  public function onEventMarvinQaLint(InputInterface $input): array {
    return [
      'marvin.qa.lint.scss' => [
        'task' => $this->lintScss($input->getArgument('packages')),
      ],
    ];
  }

  /**
   * @hook validate marvin:qa:lint:scss
   */
  public function lintScssHookValidate(CommandData $commandData) {
    $this->hookValidateArgumentPackages($commandData);
  }

  /**
   * @command marvin:qa:lint:scss
   * @bootstrap none
   */
  public function lintScss(array $packages): CollectionBuilder {
    $cb = $this->collectionBuilder();

    $cb->addCode(function () {
      $this->say('@todo Implement ' . __METHOD__);
    });

    return $cb;
  }

}
