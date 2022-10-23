<?php

declare(strict_types = 1);

namespace Drupal\marvin_incubator;

use Drupal\marvin\Utils as MarvinUtils;
use Symfony\Component\String\UnicodeString;

class Utils implements UtilsInterface {

  public static function marvinIncubatorDir(): string {
    return dirname(__DIR__);
  }

  /**
   * @todo Make this method static, or the other ones non-static.
   */
  public function collectManagedDrupalExtensions(
    string $drupalRootDir,
    array $composerLock,
    array $packagePaths
  ): array {
    $drupalRootDir = (new UnicodeString($drupalRootDir))
      ->ensureEnd(DIRECTORY_SEPARATOR)
      ->toString();
    $managedExtensions = [];
    foreach ($packagePaths as $packageName => $packagePath) {
      foreach (['packages', 'packages-dev'] as $lockKey) {
        if (file_exists("$packagePath/.git")
          && isset($composerLock[$lockKey][$packageName])
          && MarvinUtils::isDrupalPackage($composerLock[$lockKey][$packageName])
          && !str_starts_with($packagePath, $drupalRootDir)
        ) {
          $managedExtensions[$packageName] = [
            'name' => $packageName,
            'path' => $packagePath,
          ];
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

    sort($sites, \SORT_NATURAL);

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

    return array_unique($siteNames);
  }

  public static function boolToString(bool $value, bool $uppercase = TRUE): string {
    $string = var_export($value, TRUE);

    return $uppercase ? mb_strtoupper($string) : $string;
  }

}
