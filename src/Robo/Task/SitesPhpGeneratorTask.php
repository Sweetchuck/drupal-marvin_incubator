<?php

declare(strict_types = 1);

namespace Drupal\marvin_incubator\Robo\Task;

use Drupal\marvin\Robo\Task\BaseTask;
use Drupal\marvin\WriterWrapper;
use Drupal\marvin_incubator\SitesPhpGen;

class SitesPhpGeneratorTask extends BaseTask {

  /**
   * {@inheritdoc}
   */
  protected string $taskName = 'Marvin - Generate sites.php';

  protected SitesPhpGen $generator;

  protected WriterWrapper $outputDestinationWrapper;

  public function __construct(
    ?SitesPhpGen $generator = NULL,
    ?WriterWrapper $outputDestinationWrapper = NULL
  ) {
    $this->generator = $generator ?: new SitesPhpGen();
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
   */
  public function setOutputDestination($destination): static {
    $this->outputDestinationWrapper->setDestination($destination);

    return $this;
  }

  public function getOutputDestinationMode(): string {
    return $this->outputDestinationWrapper->getDestinationMode();
  }

  public function setOutputDestinationMode($destinationMode): static {
    $this->outputDestinationWrapper->setDestinationMode($destinationMode);

    return $this;
  }

  public function setOptions(array $options): static {
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
   */
  public function setSiteNames(array $siteNames): static {
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
   */
  public function setDatabaseVariantIds(array $ids): static {
    $this->generator->setDatabaseVariantIds($ids);

    return $this;
  }

  public function getSiteDirPattern(): string {
    return $this->generator->getSiteDirPattern();
  }

  public function setSiteDirPattern(string $pattern): static {
    $this->generator->setSiteDirPattern($pattern);

    return $this;
  }

  public function getUrlPattern(): string {
    return $this->generator->getUrlPattern();
  }

  public function setUrlPattern(string $pattern): static {
    $this->generator->setUrlPattern($pattern);

    return $this;
  }

  protected function runAction(): static {
    $this->assets['sitesPhp'] = $this->generator->generate();

    $this->outputDestinationWrapper
      ->write($this->assets['sitesPhp'])
      ->close();

    return $this;
  }

}
