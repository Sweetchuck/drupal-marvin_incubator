<?php

declare(strict_types = 1);

namespace Drush\Commands\Tests\marvin_incubator\DrushUnit;

use Drush\Commands\marvin_incubator\ManagedDrupalExtensionCommands;
use Webmozart\PathUtil\Path;

class ManagedDrupalExtensionCommandsTest extends CommandsTestBase {

  public function testLintPhp() {
    $task = new ManagedDrupalExtensionCommands();
    $task->setBuilder($this->builder);
    $task->setContainer($this->container);
    $task->setConfig($this->config);

    $cwd = getcwd();
    $composerRoot = Path::canonicalize("$cwd/../..");
    $expected = [
      'drupal/marvin' => "$composerRoot/drupal/marvin",
      'drupal/dummy_m1' => "$cwd/tests/fixtures/extensions/dummy_m1",
      'drupal/dummy_m2' => "$cwd/tests/fixtures/extensions/dummy_m2",
    ];
    $actual = $task->managedDrupalExtensionList();
    $this->assertSame($expected, $actual);
  }

}
