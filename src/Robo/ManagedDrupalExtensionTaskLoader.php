<?php

declare(strict_types = 1);

namespace Drupal\marvin_incubator\Robo;

use Drupal\marvin_incubator\Robo\Task\ManagedDrupalExtensionListTask;

trait ManagedDrupalExtensionTaskLoader {

  /**
   * @phpstan-param marvin-incubator-robo-task-managed-drupal-extension-list-options $options
   *
   * @return \Robo\Collection\CollectionBuilder|\Drupal\marvin_incubator\Robo\Task\ManagedDrupalExtensionListTask
   */
  protected function taskMarvinManagedDrupalExtensionList(array $options = []) {
    $container = $this->getContainer();
    /** @var \Drupal\marvin_incubator\Robo\Task\ManagedDrupalExtensionListTask $task */
    $task = $this->task(
      ManagedDrupalExtensionListTask::class,
      $container->get('marvin_incubator.utils'),
    );
    $task->setOptions($options);

    return $task;
  }

}
