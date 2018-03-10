<?php

namespace Drush\Commands\marvin_incubator\Qa;

use Consolidation\AnnotatedCommand\CommandData;
use Drush\Commands\marvin\Qa\LintCommandsBase;

class LintCommands extends LintCommandsBase {

  /**
   * @hook validate marvin:qa:lint
   */
  public function lintHookValidate(CommandData $commandData) {
    $this->hookValidateArgumentPackages($commandData);
  }

  /**
   * @command marvin:qa:lint
   * @bootstrap none
   */
  public function lint(array $packages) {
    $this->delegate('lint');
  }

}
