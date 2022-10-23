<?php

declare(strict_types = 1);

namespace Drupal\marvin_incubator\Robo\Task;

use Drupal\marvin\ComposerInfo;
use Drupal\marvin\Robo\Task\BaseTask as MarvinBaseTask;
use Drupal\marvin_incubator\Utils as MarvinIncubatorUtils;
use Symfony\Component\Filesystem\Path;

class ManagedDrupalExtensionListTask extends MarvinBaseTask {

  /**
   * {@inheritdoc}
   */
  protected string $taskName = 'Marvin - Managed Drupal extension list';

  protected MarvinIncubatorUtils $utils;

  public function __construct(MarvinIncubatorUtils $utils) {
    $this->utils = $utils;
  }

  protected string $workingDirectory = '.';

  public function getWorkingDirectory(): string {
    return $this->workingDirectory;
  }

  public function setWorkingDirectory(string $value): static {
    $this->workingDirectory = $value;

    return $this;
  }

  protected string $composerJsonFileName = 'composer.json';

  public function getComposerJsonFileName(): string {
    return $this->composerJsonFileName;
  }

  public function setComposerJsonFileName(string $value): static {
    $this->composerJsonFileName = $value;

    return $this;
  }

  protected array $packagePaths = [];

  public function getPackagePaths(): array {
    return $this->packagePaths;
  }

  public function setPackagePaths(array $value): static {
    $this->packagePaths = $value;

    return $this;
  }

  protected array $ignoredPackages = [];

  public function getIgnoredPackages(): array {
    return $this->ignoredPackages;
  }

  public function setIgnoredPackages(array $value): static {
    $this->ignoredPackages = $value;

    return $this;
  }

  public function setOptions(array $options): static {
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

  protected ComposerInfo $composerInfo;

  public function runAction(): static {
    $workingDirectory = $this->getWorkingDirectory();
    $this->initComposerInfo();

    $drupalCoreDir = $this->composerInfo->getDrupalExtensionInstallDir('core');
    $drupalRootRelative = Path::join($workingDirectory, $drupalCoreDir, '..');

    // @todo The getcwd() should not be used here.
    $managedDrupalExtensions = $this->utils->collectManagedDrupalExtensions(
      Path::makeAbsolute($drupalRootRelative, getcwd()),
      $this->composerInfo->getLock(),
      $this->getPackagePaths()
    );

    $this->assets['managedDrupalExtensions'] = array_diff_key(
      $managedDrupalExtensions,
      array_flip($this->getIgnoredPackages()),
    );

    return $this;
  }

  protected function initComposerInfo(): static {
    $this->composerInfo = ComposerInfo::create(
      $this->getWorkingDirectory(),
      $this->getComposerJsonFileName()
    );

    return $this;
  }

}
