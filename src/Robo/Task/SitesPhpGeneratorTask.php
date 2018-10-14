<?php

declare(strict_types = 1);

namespace Drupal\marvin_incubator\Robo\Task;

use Drupal\marvin\Robo\Task\BaseTask;
use Drupal\marvin\WriterWrapper;
use Drupal\marvin_incubator\SitesPhpGenerator;

class SitesPhpGeneratorTask extends BaseTask {

  /**
   * {@inheritdoc}
   */
  protected $taskName = 'Marvin - Generate sites.php';

  /**
   * @var \Drupal\marvin_incubator\SitesPhpGenerator
   */
  protected $generator;

  /**
   * @var \Drupal\marvin\WriterWrapper
   */
  protected $outputDestinationWrapper;

  public function __construct($generator = NULL, $outputDestinationWrapper = NULL) {
    $this->generator = $generator ?: new SitesPhpGenerator();
    $this->outputDestinationWrapper = $outputDestinationWrapper ?: new WriterWrapper();
  }

  /**
   * @return null|string|\Symfony\Component\Console\Output\OutputInterface
   */
  public function getOutputDestination() {
    return $this->outputDestinationWrapper->getDestination();
  }

  /**
   * @param null|string|\Symfony\Component\Console\Output\OutputInterface $destination
   *
   * @return $this
   */
  public function setOutputDestination($destination) {
    $this->outputDestinationWrapper->setDestination($destination);

    return $this;
  }

  /**
   * @return string
   */
  public function getOutputDestinationMode() {
    return $this->outputDestinationWrapper->getDestinationMode();
  }

  public function setOutputDestinationMode($destinationMode) {
    $this->outputDestinationWrapper->setDestinationMode($destinationMode);

    return $this;
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

    $this->outputDestinationWrapper
      ->write($this->assets['sitesPhp'])
      ->close();

    return $this;
  }

}
