<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_incubator\Test;

use Drush\Commands\marvin\Test\PhpunitCommandsBase;
use Drupal\marvin_incubator\CommandsBaseTrait;
use Robo\Collection\CollectionBuilder;

class PhpunitCommands extends PhpunitCommandsBase {

  use CommandsBaseTrait;

  /**
   * @command marvin:test:phpunit
   * @bootstrap none
   *
   * @marvinArgPackages packages
   */
  public function phpunit(array $packages): CollectionBuilder {
    $cb = $this->collectionBuilder();

    $testSuiteNames = $this->getTestSuiteNamesByEnvironmentVariant();
    if ($testSuiteNames === NULL) {
      return $cb;
    }

    $drupalRoot = $this->getDrupalRootDir();
    $managedDrupalExtensions = $this->getManagedDrupalExtensions();
    foreach ($packages as $packageName) {
      $packagePath = $managedDrupalExtensions[$packageName];
      $cb->addTask(
        $this
          ->getTaskPhpUnit($packagePath, $testSuiteNames)
          ->setConfiguration("$drupalRoot/core/phpunit.xml.dist")
      );
    }

    return $cb;
  }

}
