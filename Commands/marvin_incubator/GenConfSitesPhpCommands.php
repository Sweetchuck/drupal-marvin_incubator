<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_incubator;

use Drupal\marvin\DatabaseVariantTrait;
use Drupal\marvin_incubator\CommandsBaseTrait;
use Drupal\marvin_incubator\Robo\SitesPhpGeneratorTaskLoader;
use Drupal\marvin_incubator\Utils as MarvinIncubatorUtils;
use Drush\Commands\marvin\CommandsBase;
use Robo\Contract\TaskInterface;

class GenConfSitesPhpCommands extends CommandsBase {

  use CommandsBaseTrait;
  use DatabaseVariantTrait;
  use SitesPhpGeneratorTaskLoader;

  /**
   * @command marvin:gen-conf:sites-php
   * @bootstrap root
   */
  public function genConf(): TaskInterface {
    /** @var \Drush\Boot\BootstrapManager $bootstrapManager */
    $bootstrapManager = $this->getContainer()->get('bootstrap.manager');
    $dbVariants = $this->getConfigDatabaseVariants();
    $drupalRootAbs = $bootstrapManager->getRoot();
    $siteDirs = MarvinIncubatorUtils::getSiteDirs("$drupalRootAbs/sites");

    return $this
      ->taskMarvinSitesPhpGenerator()
      ->setOutputDestination("$drupalRootAbs/sites/sites.php")
      ->setDatabaseVariantIds(array_keys($dbVariants))
      ->setSiteNames(MarvinIncubatorUtils::getSiteNames($siteDirs));
  }

}
