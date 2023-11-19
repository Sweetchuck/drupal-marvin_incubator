<?php

declare(strict_types = 1);

namespace Drupal\marvin_incubator\Robo;

use Drupal\marvin_incubator\Robo\Task\CollectSiteNamesTask;

trait CollectSiteNamesTaskLoader {

  /**
   * @phpstan-param marvin-incubator-robo-task-collect-site-names-options $options
   *
   * @return \Robo\Collection\CollectionBuilder|\Drupal\marvin_incubator\Robo\Task\CollectSiteNamesTask
   */
  protected function taskMarvinCollectSiteNames(array $options = []) {
    /** @var \Drupal\marvin_incubator\Robo\Task\CollectSiteNamesTask $task */
    $task = $this->task(CollectSiteNamesTask::class);
    $task->setOptions($options);

    return $task;
  }

}
