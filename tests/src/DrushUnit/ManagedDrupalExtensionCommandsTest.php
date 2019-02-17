<?php

declare(strict_types = 1);

namespace Drush\Commands\Tests\marvin_incubator\DrushUnit;

use Drush\Commands\marvin_incubator\ManagedDrupalExtensionCommands;

class ManagedDrupalExtensionCommandsTest extends CommandsTestBase {

  public function testManagedDrupalExtensionList() {
    $commands = new ManagedDrupalExtensionCommands();
    $commands->setBuilder($this->builder);
    $commands->setContainer($this->container);
    $commands->setConfig($this->config);

    $wd = "{$this->projectRoot}/tests/fixtures/drush-sut";
    $expected = [
      'drupal/dummy_m1' => "$wd/tests/fixtures/extensions/dummy_m1",
      'drupal/dummy_m2' => "$wd/tests/fixtures/extensions/dummy_m2",
    ];
    $actual = $commands->managedDrupalExtensionList();
    $this->assertSame($expected, $actual);
  }

}
