<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_incubator;

use Drush\Commands\marvin\LintCommandsBase;
use Robo\Contract\TaskInterface;

class LintCommands extends LintCommandsBase {

  /**
   * @param string[] $packages
   *
   * @command marvin:lint
   *
   * @bootstrap none
   *
   * @marvinArgPackages packages
   */
  public function lint(array $packages): TaskInterface {
    return $this->delegate('');
  }

}
