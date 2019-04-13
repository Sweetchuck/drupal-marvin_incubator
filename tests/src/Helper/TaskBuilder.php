<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin_incubator\Helper;

use Drupal\marvin_incubator\Robo\GitHooksTaskLoader;
use Drupal\marvin_incubator\Robo\ManagedDrupalExtensionTaskLoader;
use Drupal\marvin_incubator\Robo\PhpunitConfigGeneratorTaskLoader;
use Drupal\marvin_incubator\Robo\SitesPhpGeneratorTaskLoader;
use Drupal\marvin_incubator\Robo\SiteTaskLoader;
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
    taskPhpunitConfigGenerator as public;
  }

  use SitesPhpGeneratorTaskLoader {
    taskMarvinGenerateSitesPhp as public;
  }

  use SiteTaskLoader {
    taskMarvinSiteCreate as public;
    taskMarvinSiteDelete as public;
  }

  public function collectionBuilder(): CollectionBuilder {
    return CollectionBuilder::create($this->getContainer(), NULL);
  }

}
