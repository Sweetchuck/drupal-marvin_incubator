<?php

declare(strict_types = 1);

namespace Drush\Commands\Tests\marvin_incubator\Unish;

class MarvinLintTest extends CommandsTestBase {

  public function testMarvinLintPhpcs(): void {
    $expectedExitCode = 0;
    $expectedStdOutput = '';
    $expectedStdErrorFragments = [
      "/bin/phpcs --standard='Drupal,DrupalPractice' --report='json'",
      'bundle exec scss-lint',
    ];

    $options = $this->getDefaultDrushCommandOptions();

    $this->drush(
      'marvin:lint',
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
    foreach ($expectedStdErrorFragments as $expectedStdErrorFragment) {
      $this->assertContains($expectedStdErrorFragment, $actualStdError);
    }
  }

}
