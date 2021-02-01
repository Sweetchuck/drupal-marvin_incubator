<?php

declare(strict_types = 1);

namespace Drupal\marvin_incubator\Robo;

use Drupal\marvin_incubator\Robo\Task\ManagedDrupalExtensionListTask;

trait ManagedDrupalExtensionTaskLoader {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drupal\marvin_incubator\Robo\Task\ManagedDrupalExtensionListTask
   */
  protected function taskMarvinManagedDrupalExtensionList(array $options = []) {
    $container = $this->getContainer();
    /** @var \Drupal\marvin_incubator\Robo\Task\ManagedDrupalExtensionListTask|\Robo\Collection\CollectionBuilder $task */
    $task = $this->task(
      ManagedDrupalExtensionListTask::class,
      $container->get('marvin_incubator.utils'),
    );
    $task->setOptions($options);

    return $task;
  }

}
