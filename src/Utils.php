<?php

declare(strict_types = 1);

namespace Drupal\marvin_incubator;

use Consolidation\AnnotatedCommand\CommandError;
use Drupal\marvin\Utils as MarvinUtils;
use Symfony\Component\String\UnicodeString;

class Utils implements UtilsInterface {

  public static function marvinIncubatorDir(): string {
    return dirname(__DIR__);
  }

  /**
   * {@inheritdoc}
   *
   * @todo Make this method static, or the other ones non-static.
   * @todo Maybe $rootProjectDir instead of $drupalRootDir.
   */
  public function collectManagedDrupalExtensions(
    string $rootDir,
    string $drupalRootDir,
    array $composerLock,
    array $packagePaths,
  ): array {
    $rootDir = (new UnicodeString($rootDir))
      ->trimSuffix(DIRECTORY_SEPARATOR)
      ->toString();
    $extensions = [];
    foreach ($packagePaths as $packageName => $packagePath) {
      foreach (['packages', 'packages-dev'] as $lockKey) {
        if (file_exists("$packagePath/.git")
          && isset($composerLock[$lockKey][$packageName])
          && MarvinUtils::isDrupalPackage($composerLock[$lockKey][$packageName])
          && !str_starts_with($packagePath, $rootDir . DIRECTORY_SEPARATOR)
        ) {
          $type = preg_replace(
            '/^drupal-/',
            '',
            $composerLock[$lockKey][$packageName]['type'] ?? 'library',
          );
          $nameParts = explode('/', $packageName);
          $distUrl = (string) ($composerLock[$lockKey][$packageName]['dist']['url'] ?? '');
          $extensions[$packageName] = [
            'name' => $packageName,
            'projectVendor' => $nameParts[0],
            'projectName' => $nameParts[1],
            'type' => $type,
            'path' => $packagePath,
            'pathRelative' => str_starts_with($distUrl, '../') ? $distUrl : NULL,
            'pathInstalled' => match ($type) {
              // @todo Get it from config.
              'module' => "$drupalRootDir/modules/contrib/{$nameParts[1]}",
              'theme' => "$drupalRootDir/themes/contrib/{$nameParts[1]}",
              'profiles' => "$drupalRootDir/profiles/contrib/{$nameParts[1]}",
              'drush' => "drush/Commands/contrib/{$nameParts[1]}",
              default => NULL,
            },
            'composer' => $composerLock[$lockKey][$packageName],
          ];
        }
      }
    }

    return $extensions;
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

  public function validateInputByRegexp(
    string $type,
    string $name,
    string $value,
    string $pattern,
  ): ?CommandError {
    $namedPatterns = $this->getNamedRegexpPatterns();
    if (array_key_exists($pattern, $namedPatterns)) {
      $pattern = $namedPatterns[$pattern];
    }

    return preg_match($pattern, $value) === 1 ?
      NULL
      : new CommandError(sprintf(
        'Value "%s" provided for %s does not match to pattern %s',
        $value,
        ($type === 'argument' ? "argument '$name'" : "option --$name"),
        $pattern,
      ));
  }

  /**
   * @return string[]
   */
  protected function getNamedRegexpPatterns(): array {
    return [
      'machineNameStrict' => '/^[a-z][a-z0-9]*$/',
      'machineNameNormal' => '/^[a-z][a-z0-9_]*$/',
    ];
  }

}
