<?php

namespace Drupal\Tests\marvin_incubator\Kernel;

use Drupal\Tests\marvin_incubator\Helper\DrushCommandListResult;
use Drupal\Tests\marvin_incubator\Integration\CommandsTestCase;

/**
 * @group marvin_incubator
 * @group drush_command
 */
class ManagedDrupalExtensionCommandsTest extends CommandsTestCase {

  public function testList() {
    $expected = [
      'exitCode' => 0,
      'stdOutput' => 'marvin',
      'stdError' => '',
    ];

    $args = [];
    $options = [
      'format' => 'xml',
    ];
    $options += $this->getCommonCommandLineOptions();

    $this->drush(
      'list',
      $args,
      $options,
      NULL,
      NULL,
      $expected['exitCode']
    );

    static::assertSame($expected['stdError'], $this->getErrorOutput());

    $listResult = new DrushCommandListResult();
    $listResult->setResult($this->getOutput());
    static::assertContains('marvin', $listResult->getNamespaces());
  }

}
