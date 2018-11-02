<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_incubator;

use Drupal\marvin\DatabaseVariantTrait;
use Drupal\marvin_incubator\CommandsBaseTrait;
use Drupal\marvin_incubator\GenConfSitesPhpTrait;
use Drupal\marvin_incubator\Robo\SitesPhpGeneratorTaskLoader;
use Drush\Commands\marvin\CommandsBase;
use Robo\Contract\TaskInterface;

class GenConfSitesPhpCommands extends CommandsBase {

  use CommandsBaseTrait;
  use DatabaseVariantTrait;
  use SitesPhpGeneratorTaskLoader;
  use GenConfSitesPhpTrait;

  /**
   * @command marvin:gen-conf:sites-php
   * @bootstrap root
   */
  public function genConfSitesPhp(): TaskInterface {
    return $this->getTaskMarvinGenConfSitesPhp($this->getConfigDatabaseVariants());
  }

}
