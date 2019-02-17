<?php

namespace Drupal\Tests\marvin_incubator\Kernel;

use Drupal\Tests\marvin_incubator\Helper\DrushCommandListResult;

/**
 * @group marvin_incubator
 */
class ManagedDrupalExtensionCommandsTest extends CommandsTestBase {

  public function testList() {
    $result = $this->runDrushCommand('list --format=xml');

    static::assertSame(0, $result->exitCode);
    static::assertSame('', $result->stdError);

    $listResult = new DrushCommandListResult();
    $listResult->setResult($result->stdOutput);
    static::assertContains('marvin', $listResult->getNamespaces());
  }

}
