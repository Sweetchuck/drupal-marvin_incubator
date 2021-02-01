<?php

declare(strict_types = 1);

namespace Drupal\marvin_incubator\Robo;

use Drupal\marvin_incubator\Robo\Task\SitesPhpGeneratorTask;

trait SitesPhpGeneratorTaskLoader {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drupal\marvin_incubator\Robo\Task\SitesPhpGeneratorTask
   */
  protected function taskMarvinGenerateSitesPhp(array $options = []) {
    /** @var \Drupal\marvin_incubator\Robo\Task\SitesPhpGeneratorTask $task */
    $task = $this->task(SitesPhpGeneratorTask::class);
    $task->setOptions($options);

    return $task;
  }

}
