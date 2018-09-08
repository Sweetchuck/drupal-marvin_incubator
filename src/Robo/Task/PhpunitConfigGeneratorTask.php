<?php

declare(strict_types = 1);

namespace Drupal\marvin_incubator\Robo\Task;

use Drupal\marvin\Robo\Task\BaseTask;
use Drupal\marvin_incubator\PhpunitConfigGenerator;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Filesystem\Filesystem;

class PhpunitConfigGeneratorTask extends BaseTask {

  /**
   * {@inheritdoc}
   */
  protected $taskName = 'Marvin - Generate PHPUnit XML';

  /**
   * @var string
   */
  protected $drupalRoot = '';

  public function getDrupalRoot(): string {
    return $this->drupalRoot;
  }

  public function setDrupalRoot(string $drupalRoot) {
    $this->drupalRoot = $drupalRoot;

    return $this;
  }

  /**
   * @var string
   */
  protected $url = '';

  public function getUrl(): string {
    return $this->url;
  }

  public function setUrl(string $value) {
    $this->url = $value;

    return $this;
  }

  /**
   * @var array
   */
  protected $dbConnection = [];

  public function getDbConnection(): array {
    return $this->dbConnection;
  }

  public function setDbConnection(array $value) {
    $this->dbConnection = $value;

    return $this;
  }

  /**
   * @var string[]
   */
  protected $packagePaths = [];

  public function getPackagePaths(): array {
    return $this->packagePaths;
  }

  public function setPackagePaths(array $value) {
    $this->packagePaths = $value;

    return $this;
  }

  /**
   * @var string
   */
  protected $phpVersion = '0701';

  public function getPhpVersion(): string {
    return $this->phpVersion;
  }

  public function setPhpVersion(string $value) {
    $this->phpVersion = $value;

    return $this;
  }

  /**
   * @var string
   */
  protected $reportsDir = 'reports';

  public function getReportsDir(): string {
    return $this->reportsDir;
  }

  public function setReportsDir(string $value) {
    $this->reportsDir = $value;

    return $this;
  }

  /**
   * Output destination.
   *
   * @var null|string|\Symfony\Component\Console\Output\OutputInterface
   */
  protected $destination = NULL;

  /**
   * @return null|string|\Symfony\Component\Console\Output\OutputInterface
   */
  public function getDestination() {
    return $this->destination;
  }

  /**
   * @param null|string|\Symfony\Component\Console\Output\OutputInterface $destination
   *
   * @return $this
   */
  public function setDestination($destination) {
    $this->destination = $destination;

    return $this;
  }

  /**
   * Output destination mode.
   *
   * @var string
   */
  protected $destinationMode = 'w';

  /**
   * Output destination.
   *
   * @var null|\Symfony\Component\Console\Output\OutputInterface
   */
  protected $destinationOutput = NULL;

  /**
   * File handler.
   *
   * @var null|resource
   */
  protected $destinationResource = NULL;

  public function getDestinationMode(): string {
    return $this->destinationMode;
  }

  public function setDestinationMode(string $destinationMode) {
    $this->destinationMode = $destinationMode;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions(array $options) {
    parent::setOptions($options);

    if (array_key_exists('destination', $options)) {
      $this->setDestination($options['destination']);
    }

    if (array_key_exists('destinationMode', $options)) {
      $this->setDestinationMode($options['destinationMode']);
    }

    if (array_key_exists('drupalRoot', $options)) {
      $this->setDrupalRoot($options['drupalRoot']);
    }

    if (array_key_exists('url', $options)) {
      $this->setUrl($options['url']);
    }

    if (array_key_exists('dbConnection', $options)) {
      $this->setDbConnection($options['dbConnection']);
    }

    if (array_key_exists('phpVersion', $options)) {
      $this->setPhpVersion($options['phpVersion']);
    }

    if (array_key_exists('packagePaths', $options)) {
      $this->setPackagePaths($options['packagePaths']);
    }

    if (array_key_exists('reportsDir', $options)) {
      $this->setReportsDir($options['reportsDir']);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function initOptions() {
    parent::initOptions();

    $this->options['drupalRoot'] = [
      'type' => 'other',
      'value' => $this->getDrupalRoot(),
    ];

    $this->options['url'] = [
      'type' => 'other',
      'value' => $this->getUrl(),
    ];

    $this->options['dbConnection'] = [
      'type' => 'other',
      'value' => $this->getDbConnection(),
    ];

    $this->options['packagePaths'] = [
      'type' => 'other',
      'value' => $this->getPackagePaths(),
    ];

    $this->options['reportsDir'] = [
      'type' => 'other',
      'value' => $this->getReportsDir(),
    ];

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function runAction() {
    $this->assets['phpunitConfig'] = $this->getGenerator()->generate();

    $this->writeToDestination();

    return $this;
  }

  protected function getGenerator(): PhpunitConfigGenerator {
    $generator = new PhpunitConfigGenerator();

    if ($this->options['drupalRoot']['value']) {
      $generator->setDrupalRoot($this->options['drupalRoot']['value']);
    }

    if ($this->options['url']['value']) {
      $generator->setUrl($this->options['url']['value']);
    }

    if ($this->options['dbConnection']['value']) {
      $generator->setDbConnection($this->options['dbConnection']['value']);
    }

    if ($this->options['phpVersion']['value']) {
      $generator->setPhpVersion($this->options['phpVersion']['value']);
    }

    if ($this->options['packagePaths']['value']) {
      $generator->setPackagePaths($this->options['packagePaths']['value']);
    }

    if ($this->options['reportsDir']['value']) {
      $generator->setReportsDir($this->options['reportsDir']['value']);
    }

    return $generator;
  }

  /**
   * @return $this
   */
  protected function writeToDestination() {
    $this->initDestination();
    if ($this->destinationOutput) {
      $this->destinationOutput->write($this->assets['phpunitConfig']);
    }
    $this->closeDestination();

    return $this;
  }

  /**
   * Initialize the output destination based on the Jar values.
   *
   * @return $this
   */
  protected function initDestination() {
    $destination = $this->getDestination();
    if (is_string($destination)) {
      $fs = new Filesystem();
      $fs->mkdir(dirname($destination));

      $this->destinationResource = fopen($destination, $this->getDestinationMode());
      if ($this->destinationResource === FALSE) {
        throw  new \RuntimeException("File '$destination' could not be opened.");
      }

      $this->destinationOutput = new StreamOutput(
        $this->destinationResource,
        OutputInterface::VERBOSITY_NORMAL,
        FALSE
      );

      return $this;
    }

    $this->destinationOutput = $destination;

    return $this;
  }

  /**
   * Close the destination resource if it was opened here.
   *
   * @return $this
   */
  protected function closeDestination() {
    if ($this->destinationResource) {
      fclose($this->destinationResource);
    }

    return $this;
  }

}
