<?php

declare(strict_types = 1);

namespace Drupal\marvin_incubator;

use Drupal\marvin\Utils as MarvinUtils;
use Stringy\StaticStringy;

class Utils implements UtilsInterface {

  public static function marvinIncubatorDir(): string {
    return dirname(__DIR__);
  }

  /**
   * {@inheritdoc}
   */
  public function collectManagedDrupalExtensions(
    string $drupalRootDir,
    array $composerLock,
    array $packagePaths
  ): array {
    $drupalRootDir = StaticStringy::ensureRight($drupalRootDir, DIRECTORY_SEPARATOR);
    $managedExtensions = [];
    foreach ($packagePaths as $packageName => $packagePath) {
      foreach (['packages', 'packages-dev'] as $lockKey) {
        if (file_exists("$packagePath/.git")
          && isset($composerLock[$lockKey][$packageName])
          && MarvinUtils::isDrupalPackage($composerLock[$lockKey][$packageName])
          && !StaticStringy::startsWith($packagePath, $drupalRootDir)
        ) {
          $managedExtensions[$packageName] = $packagePath;
        }
      }
    }

    return $managedExtensions;
  }

}
