<?php

declare(strict_types = 1);

namespace Drush\Commands\Tests\marvin_incubator\Unish;

class MarvinLintComposerValidateTest extends CommandsTestBase {

  public function testMarvinLintComposerValidate(): void {
    $fixturesDir = static::getTmp();

    $expectedExitCode = 0;
    $expectedStdOutput = './composer.json is valid';
    $expectedStdError = " Running composer validate in $fixturesDir/extensions/dummy_m1\n";

    $options = $this->getDefaultDrushCommandOptions();

    $this->drush(
      'marvin:lint:composer-validate',
      [
        'dummy_m1',
      ],
      $options,
      NULL,
      static::getSut(),
      $expectedExitCode
    );

    $actualStdOutput = $this->getOutput();
    $actualStdError = $this->getErrorOutput();
    $this->assertSame($expectedStdOutput, $actualStdOutput);
    $this->assertContains($expectedStdError, $actualStdError);
  }

}
