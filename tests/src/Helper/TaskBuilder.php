<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin_incubator\Helper;

use Drupal\marvin_incubator\Robo\GitHooksTaskLoader;
use Drupal\marvin_incubator\Robo\ManagedDrupalExtensionTaskLoader;
use Drupal\marvin_incubator\Robo\SiteTaskLoader;
use Drupal\marvin_phpunit_incubator\Robo\PhpunitConfigGeneratorTaskLoader;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Collection\CollectionBuilder;
use Robo\Common\TaskIO;
use Robo\Contract\BuilderAwareInterface;
use Robo\State\StateAwareTrait;
use Robo\TaskAccessor;

class TaskBuilder implements BuilderAwareInterface, ContainerAwareInterface {

  use TaskAccessor;
  use ContainerAwareTrait;
  use StateAwareTrait;
  use TaskIO;

  use GitHooksTaskLoader {
    taskMarvinGitHooksDeploy as public;
  }

  use ManagedDrupalExtensionTaskLoader {
    taskMarvinManagedDrupalExtensionList as public;
  }

  use PhpunitConfigGeneratorTaskLoader {
    taskMarvinPhpunitConfigGenerator as public;
  }

  use SiteTaskLoader {
    taskMarvinSiteCreate as public;
    taskMarvinSiteDelete as public;
  }

  public function collectionBuilder(): CollectionBuilder {
    // @phpstan-ignore-next-line
    return CollectionBuilder::create($this->getContainer(), NULL);
  }

}
