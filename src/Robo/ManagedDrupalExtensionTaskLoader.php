<?php

namespace Drupal\marvin_incubator\Robo;

use Drupal\marvin_incubator\Robo\Task\ManagedDrupalExtensionListTask;
use League\Container\ContainerAwareInterface;

trait ManagedDrupalExtensionTaskLoader {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drupal\marvin_incubator\Robo\Task\ManagedDrupalExtensionListTask
   */
  protected function taskMarvinManagedDrupalExtensionList(array $options = []) {
    /** @var \Robo\Collection\CollectionBuilder|\Drupal\marvin_incubator\Robo\Task\ManagedDrupalExtensionListTask $taskWrapper */
    $taskWrapper = $this->task(ManagedDrupalExtensionListTask::class);

    /** @var \Drupal\marvin_incubator\Robo\Task\ManagedDrupalExtensionListTask $task */
    $task = $taskWrapper->getCollectionBuilderCurrentTask();
    if ($this instanceof ContainerAwareInterface && $task instanceof ContainerAwareInterface) {
      $container = $this->getContainer();
      if ($container && !$task->getContainer()) {
        $task->setContainer($container);
      }
    }

    $task->setOptions($options);

    return $taskWrapper;
  }

}
