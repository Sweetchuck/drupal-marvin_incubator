<?php

namespace Drupal\marvin_incubator\Robo;

use Drupal\marvin_incubator\Robo\Task\SitesPhpGeneratorTask;

trait SitesPhpGeneratorTaskLoader {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drupal\marvin_incubator\Robo\Task\SitesPhpGeneratorTask
   */
  protected function taskMarvinSitesPhpGenerator(array $options = []) {
    /** @var \Drupal\marvin_incubator\Robo\Task\SitesPhpGeneratorTask $task */
    $task = $this->task(SitesPhpGeneratorTask::class);
    $task->setOptions($options);

    return $task;
  }

}
