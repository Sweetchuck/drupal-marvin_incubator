<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_incubator\Test;

use Drush\Commands\marvin\Test\PhpunitCommandsBase;
use Drupal\marvin\Utils as MarvinUtils;
use Drupal\marvin_incubator\CommandsBaseTrait;
use Robo\Collection\CollectionBuilder;
use Sweetchuck\Utils\Filter\ArrayFilterEnabled;

class PhpunitCommands extends PhpunitCommandsBase {

  use CommandsBaseTrait;

  /**
   * @command marvin:test:phpunit
   * @bootstrap none
   *
   * @marvinArgPackages packages
   * @marvinOptionPhpVariants phpVariants
   */
  public function phpunit(
    array $packages,
    array $options = [
      'phpVariants' => [],
    ]
  ): CollectionBuilder {
    // @todo Generate phpunit.xml for managed extensions.
    // @todo CLI option for testSuiteNames.
    $cb = $this->collectionBuilder();
    $testSuiteNames = $this->getTestSuiteNamesByEnvironmentVariant();
    if ($testSuiteNames === NULL) {
      return $cb;
    }

    $groups = [];
    foreach ($packages as $packageName) {
      $groups[] = MarvinUtils::splitPackageName($packageName)['name'];
    }

    if (!$groups) {
      return $cb;
    }

    $phpVariants = array_filter($options['phpVariants'], new ArrayFilterEnabled());
    $drupalRoot = $this->getDrupalRootDir();
    foreach ($phpVariants as $phpVariant) {
      $phpUnitTask = $this
        ->getTaskPhpUnit($testSuiteNames, $groups, $phpVariant)
        ->setConfiguration("$drupalRoot/core/phpunit.xml");

      $cb->addTask($phpUnitTask);
    }

    return $cb;
  }

}
