<?php

declare(strict_types = 1);

namespace Drupal\marvin_incubator\Robo;

trait SiteTaskLoader {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drupal\marvin_incubator\Robo\Task\SiteCreateTask
   */
  protected function taskMarvinSiteCreate(array $options = []) {
    /** @var \Drupal\marvin_incubator\Robo\Task\SiteCreateTask $task */
    $task = $this->task(Task\SiteCreateTask::class);
    $task->setOptions($options);

    return $task;
  }

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drupal\marvin_incubator\Robo\Task\SiteDeleteTask
   */
  protected function taskMarvinSiteDelete(array $options = []) {
    /** @var \Drupal\marvin_incubator\Robo\Task\SiteDeleteTask $task */
    $task = $this->task(Task\SiteDeleteTask::class);
    $task->setOptions($options);

    return $task;
  }

}
