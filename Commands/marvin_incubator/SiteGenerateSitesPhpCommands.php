<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_incubator;

use Drupal\marvin\DatabaseVariantTrait;
use Drupal\marvin_incubator\CommandsBaseTrait;
use Drupal\marvin_incubator\Robo\CollectSiteNamesTaskLoader;
use Drupal\marvin_incubator\Robo\SitesPhpGeneratorTaskLoader;
use Drupal\marvin_incubator\SiteGenerateSitesPhpTrait;
use Drush\Attributes as CLI;
use Drush\Boot\DrupalBootLevels;
use Drush\Commands\marvin\CommandsBase;
use Robo\Contract\TaskInterface;

class SiteGenerateSitesPhpCommands extends CommandsBase {

  use CommandsBaseTrait;
  use DatabaseVariantTrait;
  use CollectSiteNamesTaskLoader;
  use SitesPhpGeneratorTaskLoader;
  use SiteGenerateSitesPhpTrait;

  /**
   * Generates a new ./docroot/sites/sites.php.
   *
   * @todo I think this command is not required anymore,
   * because ./docroot/sites/sites.php dynamically populates the $sites array.
   */
  #[CLI\Command(name: 'marvin:generate:sites-php')]
  #[CLI\Bootstrap(level: DrupalBootLevels::ROOT)]
  public function cmdGenerateSitesPhpExecute(): TaskInterface {
    return $this->getTaskMarvinGenerateSitesPhp($this->getConfigDatabaseVariants());
  }

}
