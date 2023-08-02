<?php

declare(strict_types = 1);

namespace Drupal\marvin_incubator;

use Drupal\marvin_incubator\Robo\ManagedDrupalExtensionTaskLoader;
use Robo\Collection\CollectionBuilder;
use Sweetchuck\Robo\Composer\ComposerTaskLoader;
use Sweetchuck\Utils\Filter\EnabledFilter;
use Symfony\Component\Console\Output\OutputInterface;

trait CommandsBaseTrait {

  use ComposerTaskLoader;
  use ManagedDrupalExtensionTaskLoader;

  protected ?string $drupalRoot = NULL;

  protected ?array $managedDrupalExtensions = NULL;

  /**
   * @phpstan-return array<string, marvin-incubator-managed-drupal-extension>
   */
  protected function getManagedDrupalExtensions(string $workingDirectory = ''): array {
    if ($this->managedDrupalExtensions === NULL) {
      $result = $this
        ->getTaskManagedDrupalExtensionList($workingDirectory)
        ->run()
        ->stopOnFail();

      $this->managedDrupalExtensions = $result['managedDrupalExtensions'];
    }

    return $this->managedDrupalExtensions;
  }

  protected function getTaskManagedDrupalExtensionList(string $workingDirectory = ''): CollectionBuilder {
    $packageDefinitions = (array) $this
      ->getConfig()
      ->get('marvin.managedDrupalExtension.package');

    $ignoredFilter = new EnabledFilter();
    $ignoredFilter->setKey('ignored');
    $ignoredPackages = array_filter($packageDefinitions, $ignoredFilter);

    if (!$workingDirectory) {
      $workingDirectory = $this->getProjectRootDir();
    }

    return $this
      ->collectionBuilder()
      ->setProgressIndicator(NULL)
      ->setVerbosityThreshold(OutputInterface::VERBOSITY_QUIET)
      ->addTask(
        $this
          ->taskComposerPackagePaths()
          ->setVerbosityThreshold(4)
          ->setWorkingDirectory($workingDirectory))
      ->addTask(
        $this
          ->taskMarvinManagedDrupalExtensionList()
          ->setVerbosityThreshold(4)
          ->setWorkingDirectory($workingDirectory)
          ->setComposerJsonFileName(getenv('COMPOSER') ?: 'composer.json')
          ->setIgnoredPackages(array_keys($ignoredPackages))
          ->deferTaskConfiguration('setPackagePaths', 'composer.packagePaths'));
  }

  /**
   * @todo This method could be part of the \Drupal\marvin_incubator\Utils.
   * @todo Rename this method to findManagedDrupalExtensionByUserInput().
   *
   * @phpstan-return marvin-incubator-managed-drupal-extension
   */
  protected function normalizeManagedDrupalExtensionName(string $extensionName): ?array {
    $managedDrupalExtensions = $this->getManagedDrupalExtensions();

    if (isset($managedDrupalExtensions[$extensionName])) {
      return $managedDrupalExtensions[$extensionName];
    }

    // Transform a Drupal extension machine-name to a fq composer package name.
    if (mb_strpos($extensionName, '/') === FALSE) {
      // @todo The vendor can be anything not just "drupal".
      $packageName = "drupal/$extensionName";
      if (isset($managedDrupalExtensions[$packageName])) {
        return $managedDrupalExtensions[$packageName];
      }
    }

    foreach ($managedDrupalExtensions as $extension) {
      if ($extension['path'] === $extensionName
        || $extension['pathRelative'] === $extensionName
        || $extension['pathInstalled'] === $extensionName
      ) {
        return $extension;
      }
    }

    return NULL;
  }

}
