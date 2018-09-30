<?php

namespace Drupal\marvin_incubator\Robo;

use Drupal\marvin_incubator\Robo\Task\PhpunitConfigGeneratorTask;

trait PhpunitConfigGeneratorTaskLoader {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drupal\marvin_incubator\Robo\Task\PhpunitConfigGeneratorTask
   */
  protected function taskPhpunitConfigGenerator(array $options = []) {
    /** @var \Drupal\marvin_incubator\Robo\Task\PhpunitConfigGeneratorTask $task */
    $task = $this->task(PhpunitConfigGeneratorTask::class);
    $task->setOptions($options);

    return $task;
  }

}