<?php

declare(strict_types = 1);

namespace Drush\Commands\Tests\marvin_incubator\DrushUnit;

use Drush\Commands\marvin_incubator\Lint\PhpLintCommands;
use PHPUnit\Framework\IncompleteTestError;

class PhpLintCommandsTest extends CommandsTestBase {

  public function testLintPhp() {
    $task = new PhpLintCommands();
    $task->setBuilder($this->builder);
    $task->setContainer($this->container);
    $task->setConfig($this->config);

    $packages = [];
    $cb = $task->lintPhp($packages);
    $this->assertNotNull($cb);

    throw new IncompleteTestError(__METHOD__);
  }

}
