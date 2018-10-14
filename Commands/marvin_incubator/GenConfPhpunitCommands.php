<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_incubator;

use Drupal\marvin\PhpVariantTrait;
use Drupal\marvin_incubator\CommandsBaseTrait;
use Drupal\marvin_incubator\Robo\PhpunitConfigGeneratorTaskLoader;
use Drush\Commands\marvin\CommandsBase;
use Drush\Sql\SqlBase;
use Robo\Contract\TaskInterface;
use Webmozart\PathUtil\Path;

class GenConfPhpunitCommands extends CommandsBase {

  use CommandsBaseTrait;
  use PhpVariantTrait;
  use PhpunitConfigGeneratorTaskLoader;

  /**
   * @command marvin:gen-conf:phpunit
   * @bootstrap configuration
   */
  public function genConf(): TaskInterface {
    /** @var \Drush\Boot\BootstrapManager $bootstrapManager */
    $bootstrapManager = $this->getContainer()->get('bootstrap.manager');

    $uri = $bootstrapManager->getUri();
    $uriParts = parse_url($uri);
    // @todo URL parts detector.
    list($webPhpVariantId, , $dbId) = explode('.', $uriParts['host']);

    $phpVariants = $this->getConfigPhpVariants();
    $webPhpVariant = $phpVariants[$webPhpVariantId];

    $projectRoot = $bootstrapManager->getComposerRoot();
    $drupalRootAbs = $bootstrapManager->getRoot();
    $drupalRoot = Path::makeRelative($drupalRootAbs, $projectRoot);
    $backToProjectRoot = Path::makeRelative($projectRoot, $drupalRootAbs);

    $reportsDir = (string) $this->getConfig()->get('command.marvin.settings.reportsDir', 'reports');
    $db = SqlBase::create([]);
    $dstFileName = "$backToProjectRoot/phpunit.$dbId.{$webPhpVariant['version']['majorMinor']}.xml";

    $dbConnection = $db->getDbSpec();
    unset($dbConnection['prefix']);

    $phpunitConfGenTask = $this
      ->taskPhpunitConfigGenerator()
      ->setOutputDestination($dstFileName)
      ->setDrupalRoot($drupalRoot)
      ->setUrl($bootstrapManager->getUri())
      ->setDbConnection($dbConnection)
      ->setPhpVersion((string) $webPhpVariant['version']['id'])
      ->setReportsDir($reportsDir)
      ->setPackagePaths($this->getManagedDrupalExtensions());

    return $phpunitConfGenTask;
  }

}
