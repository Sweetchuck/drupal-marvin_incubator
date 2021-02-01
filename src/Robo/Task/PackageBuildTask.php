<?php

declare(strict_types = 1);

namespace Drupal\marvin_incubator\Robo\Task;

use Drupal\marvin\Robo\Task\BaseTask;

class PackageBuildTask extends BaseTask {

  /**
   * {@inheritdoc}
   */
  protected function runAction() {
    /*
     * Actions to implement:
     * gulp list commands (marvin:build)
     *   Run it if exists.
     *   exit
     * yarn list commands (marvin:build)
     *   Run it if exists.
     *   exit
     * Find tsc (TypeScript compile).
     * Find node-sass.
     */
    return $this;
  }

}
