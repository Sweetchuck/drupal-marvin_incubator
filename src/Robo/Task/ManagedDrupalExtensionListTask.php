<?php

declare(strict_types = 1);

namespace Drupal\marvin_incubator\Robo\Task;

use Drupal\marvin\ComposerInfo;
use Drupal\marvin\Robo\Task\BaseTask as MarvinBaseTask;
use Drupal\marvin_incubator\Utils as MarvinIncubatorUtils;

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

  /**
   * @phpstan-var array<string, string>
   */
  protected array $packagePaths = [];

  /**
   * @phpstan-return array<string, string>
   */
  public function getPackagePaths(): array {
    return $this->packagePaths;
  }

  /**
   * @phpstan-param array<string, string> $value
   */
  public function setPackagePaths(array $value): static {
    $this->packagePaths = $value;

    return $this;
  }

  /**
   * @var string[]
   */
  protected array $ignoredPackages = [];

  /**
   * @return string[]
   */
  public function getIgnoredPackages(): array {
    return $this->ignoredPackages;
  }

  /**
   * @param string[] $value
   */
  public function setIgnoredPackages(array $value): static {
    $this->ignoredPackages = $value;

    return $this;
  }

  /**
   * @phpstan-param marvin-incubator-robo-task-managed-drupal-extension-list-options $options
   */
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

  /**
   * @phpstan-var \Drupal\marvin\ComposerInfo<string, mixed>
   */
  protected ComposerInfo $composerInfo;

  public function runAction(): static {
    $workingDirectory = $this->getWorkingDirectory();
    $this->initComposerInfo();

    $managedDrupalExtensions = $this->utils->collectManagedDrupalExtensions(
      $workingDirectory,
      $this->composerInfo->getDrupalRootDir(),
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
      $this->getComposerJsonFileName(),
    );

    return $this;
  }

}
