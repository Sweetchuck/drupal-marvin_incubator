<?php

declare(strict_types = 1);

namespace Drupal\marvin_incubator\Robo;

use Drupal\marvin_incubator\Robo\Task\GitHooksDeployTask;

trait GitHooksTaskLoader {

  /**
   * @phpstan-param marvin-incubator-robo-task-git-hooks-deploy-options $options
   *
   * @return \Robo\Collection\CollectionBuilder|\Drupal\marvin_incubator\Robo\Task\GitHooksDeployTask
   */
  protected function taskMarvinGitHooksDeploy(array $options = []) {
    /** @var \Drupal\marvin_incubator\Robo\Task\GitHooksDeployTask $task */
    $task = $this->task(GitHooksDeployTask::class);
    $task->setOptions($options);

    return $task;
  }

}
