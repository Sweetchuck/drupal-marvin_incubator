<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_incubator;

use Drupal\marvin\DatabaseVariantTrait;
use Drupal\marvin_incubator\CommandsBaseTrait;
use Drupal\marvin_incubator\Robo\CollectSiteNamesTaskLoader;
use Drupal\marvin_incubator\SiteGenerateSitesPhpTrait;
use Drupal\marvin_incubator\Robo\SitesPhpGeneratorTaskLoader;
use Drush\Commands\marvin\CommandsBase;
use Robo\Contract\TaskInterface;

class SiteGenerateSitesPhpCommands extends CommandsBase {

  use CommandsBaseTrait;
  use CollectSiteNamesTaskLoader;
  use DatabaseVariantTrait;
  use SitesPhpGeneratorTaskLoader;
  use SiteGenerateSitesPhpTrait;

  /**
   * @command marvin:generate:sites-php
   * @bootstrap root
   */
  public function generateSitesPhp(): TaskInterface {
    return $this->getTaskMarvinGenerateSitesPhp($this->getConfigDatabaseVariants());
  }

}
