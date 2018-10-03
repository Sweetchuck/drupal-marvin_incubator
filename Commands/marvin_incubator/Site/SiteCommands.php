<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_incubator\Site;

use Drupal\marvin\DatabaseVariantTrait;
use Drupal\marvin\PhpVariantTrait;
use Drupal\marvin_incubator\Robo\SiteTaskLoader;
use Drupal\marvin_incubator\Utils as MarvinIncubatorUtils;
use Drush\Commands\marvin\CommandsBase;
use Symfony\Component\Filesystem\Filesystem;

class SiteCommands extends CommandsBase {

  use SiteTaskLoader;
  use DatabaseVariantTrait;
  use PhpVariantTrait;

  /**
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  protected $fs;

  protected $protectedSiteNames = [
    'list' => ['simpletest'],
    'create' => ['default', 'simpletest'],
    'delete' => ['default'],
  ];

  /**
   * {@inheritdoc}
   */
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
    var_dump(MarvinIncubatorUtils::getSiteDirs('.'));
  }

  /**
   * @command marvin:site:info
   *
   * @bootstrap root
   */
  public function info() {

  }

  /**
   * @command marvin:site:create
   *
   * @bootstrap root
   */
  public function create(string $siteName) {
    return $this
      ->taskMarvinSiteCreate()
      ->setSiteName($siteName)
      ->setDbVariants($this->getConfigDatabaseVariants())
      ->setPhpVariants($this->getConfigPhpVariants());
  }

  /**
   * @command marvin:site:delete
   *
   * @bootstrap root
   */
  public function delete(string $siteName) {
    return $this
      ->taskMarvinSiteDelete()
      ->setSiteName($siteName);
  }

}
