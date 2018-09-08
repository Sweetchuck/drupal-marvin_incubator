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
    $dbVariants = [
      'my0506' => 'my0506',
    ];
    $projectRootDir = $this->getConfig()->get('env.cwd');
    foreach ($phpVariants as $phpVariant) {
      foreach ($dbVariants as $dbVariant) {
        $phpUnitTask = $this
          ->getTaskPhpUnit($testSuiteNames, $groups, $phpVariant)
          ->setProcessTimeout(NULL)
          ->setConfiguration("$projectRootDir/phpunit.$dbVariant.{$phpVariant['version']['majorMinor']}.xml");

        $cb->addTask($phpUnitTask);
      }
    }

    return $cb;
  }

}
