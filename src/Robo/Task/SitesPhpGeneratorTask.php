<?php

declare(strict_types = 1);

namespace Drupal\marvin_incubator\Robo\Task;

use Drupal\marvin\OutputDestinationTrait;
use Drupal\marvin\Robo\Task\BaseTask;
use Drupal\marvin_incubator\SitesPhpGenerator;

class SitesPhpGeneratorTask extends BaseTask {

  use OutputDestinationTrait;

  /**
   * {@inheritdoc}
   */
  protected $taskName = 'Marvin - Generate sites.php';

  /**
   * @var \Drupal\marvin_incubator\SitesPhpGenerator
   */
  protected $generator;

  public function __construct() {
    $this->generator = new SitesPhpGenerator();
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions(array $options) {
    parent::setOptions($options);

    if (array_key_exists('outputDestination', $options)) {
      $this->setOutputDestination($options['outputDestination']);
    }

    if (array_key_exists('outputDestinationMode', $options)) {
      $this->setOutputDestinationMode($options['outputDestinationMode']);
    }

    if (array_key_exists('siteNames', $options)) {
      $this->setSiteNames($options['siteNames']);
    }

    if (array_key_exists('databaseVariantIds', $options)) {
      $this->setDatabaseVariantIds($options['databaseVariantIds']);
    }

    if (array_key_exists('siteDirPattern', $options)) {
      $this->setSiteDirPattern($options['siteDirPattern']);
    }

    if (array_key_exists('urlPattern', $options)) {
      $this->setUrlPattern($options['urlPattern']);
    }

    return $this;
  }

  public function getSiteNames(): array {
    return $this->generator->getSiteNames();
  }

  /**
   * @param string[] $siteNames
   *
   * @return $this
   */
  public function setSiteNames(array $siteNames) {
    $this->generator->setSiteNames($siteNames);

    return $this;
  }

  /**
   * @return string[]
   */
  public function getDatabaseVariantIds(): array {
    return $this->generator->getDatabaseVariantIds();
  }

  /**
   * @param string[] $ids
   *
   * @return $this
   */
  public function setDatabaseVariantIds(array $ids) {
    $this->generator->setDatabaseVariantIds($ids);

    return $this;
  }

  public function getSiteDirPattern(): string {
    return $this->generator->getSiteDirPattern();
  }

  /**
   * @return $this
   */
  public function setSiteDirPattern(string $pattern) {
    $this->generator->setSiteDirPattern($pattern);

    return $this;
  }

  public function getUrlPattern(): string {
    return $this->generator->getUrlPattern();
  }

  /**
   * @return $this
   */
  public function setUrlPattern(string $pattern) {
    $this->generator->setUrlPattern($pattern);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function runAction() {
    $this->assets['sitesPhp'] = $this->generator->generate();

    $this->writeToOutputDestination($this->assets['sitesPhp']);

    return $this;
  }

}
