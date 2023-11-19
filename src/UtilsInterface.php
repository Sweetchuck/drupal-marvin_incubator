<?php

declare(strict_types = 1);

namespace Drupal\marvin_incubator;

interface UtilsInterface {

  /**
   * @phpstan-param marvin-composer-lock $composerLock
   * @phpstan-param array<string, string> $packagePaths
   *
   * @phpstan-return array<string, marvin-incubator-managed-drupal-extension>
   */
  public function collectManagedDrupalExtensions(
    string $rootDir,
    string $drupalRootDir,
    array $composerLock,
    array $packagePaths
  ): array;

}
