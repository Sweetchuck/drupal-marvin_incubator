<?php

declare(strict_types = 1);

namespace Drush\Commands\Tests\marvin_incubator\Unish;

class MarvinLintPhpcsTest extends CommandsTestBase {

  public function testMarvinLintPhpcs(): void {
    $expectedExitCode = 0;
    $expectedStdOutput = '';
    $expectedStdError = "/phpcs --standard='Drupal,DrupalPractice' --report='json'";

    $options = $this->getDefaultDrushCommandOptions();

    $this->drush(
      'marvin:lint:phpcs',
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
