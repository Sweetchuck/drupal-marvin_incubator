<?php

declare(strict_types = 1);

namespace Drush\Commands\Tests\marvin_incubator\Unish;

use Symfony\Component\Yaml\Yaml;

class MarvinManagedDrupalExtensionListTest extends CommandsTestBase {

  public function testMarvinManagedDrupalExtensionList(): void {
    $expectedExitCode = 0;
    $expectedStdOutput = [
      'drupal/dummy_m1' => static::getExtensionsDir() . '/dummy_m1',
    ];

    $args = [];
    $options = $this->getDefaultDrushCommandOptions();

    $this->drush(
      'marvin:managed-drupal-extension:list',
      $args,
      $options,
      NULL,
      static::getSut(),
      $expectedExitCode,
      '2>/dev/null'
    );

    $actualStdOutput = $this->getOutput();
    $actualStdOutputArray = Yaml::parse($actualStdOutput);
    unset($actualStdOutputArray['drupal/marvin']);
    $this->assertSame($expectedStdOutput, $actualStdOutputArray);
  }

}
