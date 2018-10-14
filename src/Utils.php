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

  /**
   * @param string $sitesDir
   *   Example: "path/to/drupal_root/sites".
   *
   * @return string[]
   */
  public static function getSiteDirs(string $sitesDir): array {
    $sites = [];

    $dirIterator = new \DirectoryIterator($sitesDir);
    foreach ($dirIterator as $dir) {
      if ($dir->isDot()
        || !$dir->isDir()
        || !file_exists($dir->getPathname() . '/settings.php')
        || $dir->getFilename() === 'simpletest'
      ) {
        continue;
      }

      $sites[] = $dir->getPathname();
    }

    return $sites;
  }

  /**
   * @param string[] $siteDirs
   *
   * @return string[]
   */
  public static function getSiteNames(array $siteDirs): array {
    $siteNames = [];
    foreach ($siteDirs as $siteDir) {
      $siteNames[] = explode('.', basename($siteDir))[0];
    }

    return $siteNames;
  }

  public static function getPhpUnitConfigFileName(
    string $projectRootDir,
    array $phpVariant,
    array $dbVariant
  ): string {
    return "$projectRootDir/phpunit.{$dbVariant['id']}.{$phpVariant['version']['majorMinor']}.xml";
  }

}
