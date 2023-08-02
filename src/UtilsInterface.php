<?php

declare(strict_types = 1);

namespace Drupal\marvin_incubator;

interface UtilsInterface {

  public function collectManagedDrupalExtensions(
    string $rootDir,
    string $drupalRootDir,
    array $composerLock,
    array $packagePaths
  ): array;

}
