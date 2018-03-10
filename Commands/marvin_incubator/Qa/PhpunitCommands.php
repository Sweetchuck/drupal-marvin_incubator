<?php

namespace Drush\Commands\marvin_incubator\Qa;

use Consolidation\AnnotatedCommand\CommandData;
use Drush\Commands\marvin\Qa\PhpunitCommandsBase;
use Robo\Collection\CollectionBuilder;

class PhpunitCommands extends PhpunitCommandsBase {

  /**
   * @hook validate marvin:qa:phpunit
   */
  public function phpunitHookValidate(CommandData $commandData) {
    $this->hookValidateArgumentPackages($commandData);
  }

  /**
   * @command marvin:qa:phpunit
   * @bootstrap none
   */
  public function phpunit(array $packages): CollectionBuilder {
    $cb = $this->collectionBuilder();

    $testSuiteNames = $this->getTestSuiteNamesByEnvironmentVariant();
    if ($testSuiteNames !== NULL) {
      $managedDrupalExtensions = $this->getManagedDrupalExtensions();
      foreach ($packages as $packageName) {
        $packagePath = $managedDrupalExtensions[$packageName];
        $cb->addTask($this->getTaskPhpUnit($packagePath, $testSuiteNames));
      }
    }

    return $cb;
  }

}
