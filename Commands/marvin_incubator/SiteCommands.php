<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_incubator;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\marvin\DatabaseVariantTrait;
use Drupal\marvin\PhpVariantTrait;
use Drupal\marvin_incubator\SiteGenerateSitesPhpTrait;
use Drupal\marvin_incubator\Robo\CollectSiteNamesTaskLoader;
use Drupal\marvin_incubator\Robo\SitesPhpGeneratorTaskLoader;
use Drupal\marvin_incubator\Robo\SiteTaskLoader;
use Drupal\marvin_incubator\Utils as MarvinIncubatorUtils;
use Drush\Commands\marvin\CommandsBase;
use Robo\Collection\CollectionBuilder;
use Symfony\Component\Filesystem\Filesystem;

class SiteCommands extends CommandsBase {

  use DatabaseVariantTrait;
  use PhpVariantTrait;
  use SiteGenerateSitesPhpTrait;
  use SiteTaskLoader;
  use SitesPhpGeneratorTaskLoader;
  use CollectSiteNamesTaskLoader;

  protected Filesystem $fs;

  protected array $protectedSiteNames = [
    'list' => ['simpletest'],
    'create' => ['default', 'simpletest'],
    'delete' => ['default'],
  ];

  public function __construct() {
    parent::__construct();

    $this->fs = new Filesystem();
  }

  protected function getCustomEventNamePrefix(): string {
    return parent::getCustomEventNamePrefix() . ':site';
  }

  /**
   * @command marvin:site:list
   *
   * @bootstrap root
   */
  public function list() {
    var_dump(MarvinIncubatorUtils::getSiteDirs('sites'));
  }

  /**
   * @command marvin:site:info
   *
   * @bootstrap root
   */
  public function info() {
    // @todo Implement.
  }

  /**
   * @hook validate marvin:site:create
   */
  public function createValidate(CommandData $commandData): void {
    $siteName = $commandData->input()->getArgument('siteName');
    if (in_array($siteName, $this->protectedSiteNames['create'])) {
      throw new \Exception("Site name '$siteName' is protected", 1);
    }
  }

  /**
   * @command marvin:site:create
   *
   * @bootstrap root
   */
  public function create(string $siteName): CollectionBuilder {
    return $this
      ->collectionBuilder()
      ->addTask(
        $this
          ->taskMarvinSiteCreate()
          ->setDrupalRoot('.')
          ->setSiteName($siteName)
          ->setDbVariants($this->getConfigDatabaseVariants())
          ->setPhpVariants($this->getConfigPhpVariants())
      );
    // Currently sites.php has a dynamic content. No need to generate.
    // ->addTask($this->getTaskMarvinGenerateSitesPhp($this->getConfigDatabaseVariants())).
  }

  /**
   * @hook validate marvin:site:delete
   */
  public function deleteValidate(CommandData $commandData): void {
    $siteName = $commandData->input()->getArgument('siteName');
    if (in_array($siteName, $this->protectedSiteNames['delete'])) {
      throw new \Exception("Site name '$siteName' is protected", 1);
    }
  }

  /**
   * @command marvin:site:delete
   *
   * @bootstrap root
   */
  public function delete(string $siteName): CollectionBuilder {
    // @todo Delete other resources as well. Database, Solr core.
    return $this
      ->collectionBuilder()
      ->addTask(
        $this
          ->taskMarvinSiteDelete()
          ->setDrupalRoot('.')
          ->setSiteName($siteName)
      );
    // Currently sites.php has a dynamic content. No need to delete.
    // ->addTask($this->getTaskMarvinGenerateSitesPhp($this->getConfigDatabaseVariants())).
  }

}
