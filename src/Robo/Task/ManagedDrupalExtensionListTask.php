<?php

namespace Drupal\marvin_incubator\Robo\Task;

use Drupal\marvin\ComposerInfo;
use Drupal\marvin\Robo\Task\BaseTask as MarvinBaseTask;
use Webmozart\PathUtil\Path;

class ManagedDrupalExtensionListTask extends MarvinBaseTask {

  /**
   * {@inheritdoc}
   */
  protected $taskName = 'Marvin - Managed Drupal extension list';

  /**
   * @var string
   */
  protected $workingDirectory = '.';

  public function getWorkingDirectory(): string {
    return $this->workingDirectory;
  }

  /**
   * @return $this
   */
  public function setWorkingDirectory(string $value) {
    $this->workingDirectory = $value;

    return $this;
  }

  /**
   * @var string
   */
  protected $composerJsonFileName = 'composer.json';

  public function getComposerJsonFileName(): string {
    return $this->composerJsonFileName;
  }

  /**
   * @return $this
   */
  public function setComposerJsonFileName(string $value) {
    $this->composerJsonFileName = $value;

    return $this;
  }

  /**
   * @var array
   */
  protected $packagePaths = [];

  public function getPackagePaths(): array {
    return $this->packagePaths;
  }

  /**
   * @return $this
   */
  public function setPackagePaths(array $value) {
    $this->packagePaths = $value;

    return $this;
  }

  /**
   * @var array
   */
  protected $ignoredPackages = [];

  public function getIgnoredPackages(): array {
    return $this->ignoredPackages;
  }

  /**
   * @return $this
   */
  public function setIgnoredPackages(array $value) {
    $this->ignoredPackages = $value;

    return $this;
  }

  /**
   * @return $this
   */
  public function setOptions(array $options) {
    parent::setOptions($options);

    if (array_key_exists('workingDirectory', $options)) {
      $this->setWorkingDirectory($options['workingDirectory']);
    }

    if (array_key_exists('composerJsonFileName', $options)) {
      $this->setComposerJsonFileName($options['composerJsonFileName']);
    }

    if (array_key_exists('packagePaths', $options)) {
      $this->setPackagePaths($options['packagePaths']);
    }

    if (array_key_exists('ignoredPackages', $options)) {
      $this->setIgnoredPackages($options['ignoredPackages']);
    }

    return $this;
  }

  /**
   * @var \Drupal\marvin\ComposerInfo
   */
  protected $composerInfo;

  /**
   * {@inheritdoc}
   */
  public function runAction() {
    $workingDirectory = $this->getWorkingDirectory();
    $this->initComposerInfo();

    $drupalCoreDir = $this->composerInfo->getDrupalExtensionInstallDir('core');
    $drupalRootRelative = Path::join($workingDirectory, $drupalCoreDir, '..');

    $utils = $this->getContainer()->get('marvin_incubator.utils');
    $managedDrupalExtensions = $utils->collectManagedDrupalExtensions(
      Path::makeAbsolute($drupalRootRelative, getcwd()),
      $this->composerInfo->getLock(),
      $this->getPackagePaths()
    );

    $this->assets['managedDrupalExtensions'] = array_diff_key(
      $managedDrupalExtensions,
      array_flip($this->getIgnoredPackages())
    );

    return $this;
  }

  /**
   * @return $this
   */
  protected function initComposerInfo() {
    $this->composerInfo = ComposerInfo::create(
      $this->getWorkingDirectory(),
      $this->getComposerJsonFileName()
    );

    return $this;
  }

}
