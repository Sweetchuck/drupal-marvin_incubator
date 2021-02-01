<?php

declare(strict_types = 1);

namespace Drupal\marvin_incubator;

use Drupal\marvin_incubator\Robo\ManagedDrupalExtensionTaskLoader;
use Robo\Collection\CollectionBuilder;
use Sweetchuck\Utils\Filter\ArrayFilterEnabled;

/**
 * @todo Move this file into /Commands/marvin_incubator
 */
trait CommandsBaseTrait {

  use ManagedDrupalExtensionTaskLoader;

  protected ?string $drupalRoot = NULL;

  protected ?array $managedDrupalExtensions = NULL;

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

    $ignoredFilter = new ArrayFilterEnabled();
    $ignoredFilter->setKey('ignored');
    $ignoredPackages = array_filter($packageDefinitions, $ignoredFilter);

    if (!$workingDirectory) {
      $workingDirectory = $this->getProjectRootDir();
    }

    return $this
      ->collectionBuilder()
      ->addTask(
        $this
          ->taskComposerPackagePaths()
          ->setWorkingDirectory($workingDirectory))
      ->addTask(
        $this
          ->taskMarvinManagedDrupalExtensionList()
          ->setWorkingDirectory($workingDirectory)
          ->setIgnoredPackages(array_keys($ignoredPackages))
          ->deferTaskConfiguration('setPackagePaths', 'composer.packagePaths'));
  }

  /**
   * @todo This method could be part of the \Drupal\marvin_incubator\Utils.
   */
  protected function normalizeManagedDrupalExtensionName(string $extensionName): ?array {
    $managedDrupalExtensions = $this->getManagedDrupalExtensions();

    // Fully qualified composer package name.
    if (isset($managedDrupalExtensions[$extensionName])) {
      return [
        'name' => $extensionName,
        'path' => $managedDrupalExtensions[$extensionName],
      ];
    }

    // Transform a Drupal extension machine-name to a fq composer package name.
    if (mb_strpos($extensionName, '/') === FALSE) {
      // @todo The vendor can be anything not just "drupal".
      $packageName = "drupal/$extensionName";
      if (isset($managedDrupalExtensions[$packageName])) {
        return [
          'name' => $packageName,
          'path' => $managedDrupalExtensions[$packageName],
        ];
      }
    }

    // Full real path.
    $packageName = array_search($extensionName, $managedDrupalExtensions);
    if ($packageName !== FALSE) {
      return [
        'name' => $packageName,
        'path' => $extensionName,
      ];
    }

    // Relative path.
    if (is_dir($extensionName)) {
      $packagePath = realpath($extensionName);
      $packageName = array_search($packagePath, $managedDrupalExtensions);
      if ($packageName !== FALSE) {
        return [
          'name' => $packageName,
          'path' => $packagePath,
        ];
      }
    }

    return NULL;
  }

}
