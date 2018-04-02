<?php

declare(strict_types = 1);

namespace Drush\Commands\Tests\marvin_incubator\Unish;

use Symfony\Component\Yaml\Yaml;

class MarvinArtifactTypesCommandsTest extends CommandsTestBase {

  public function testMarvinArtifactTypes(): void {
    $expectedExitCode = 0;
    $expectedStdOutput = [
      'vanilla' => [
        'label' => 'Vanilla',
        'description' => 'Not customized',
        'id' => 'vanilla',
        'weight' => 0,
      ],
    ];
    $expectedStdError = '';

    $this->drush(
      'marvin:artifact:types',
      [],
      $this->getDefaultDrushCommandOptions(),
      NULL,
      static::getSut(),
      $expectedExitCode
    );

    $actualStdOutput = Yaml::parse($this->getOutput());
    $actualStdError = $this->getErrorOutput();

    $this->assertSame($expectedStdOutput, $actualStdOutput);
    $this->assertSame($expectedStdError, $actualStdError);
  }

  public function testMarvinArtifactTypesFormatTable(): void {
    $expectedExitCode = 0;
    $expectedStdOutput = implode(PHP_EOL, [
      'ID      Label   Description    ',
      ' vanilla Vanilla Not customized',
    ]);
    $expectedStdError = '';

    $args = [];
    $options = $this->getDefaultDrushCommandOptions();
    $options['format'] = 'table';

    $this->drush(
      'marvin:artifact:types',
      $args,
      $options,
      NULL,
      static::getSut(),
      $expectedExitCode
    );

    $actualStdOutput = $this->getOutput();
    $actualStdError = $this->getErrorOutput();

    $this->assertSame($expectedStdOutput, $actualStdOutput);
    $this->assertSame($expectedStdError, $actualStdError);
  }

}
