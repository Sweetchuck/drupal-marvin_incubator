<?php

namespace Drupal\Tests\marvin_incubator\Kernel;

use Drupal\Tests\marvin_incubator\Integration\CommandsTestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * @group marvin_incubator
 * @group drush_command
 */
class MarvinManagedDrupalExtensionCommandsTest extends CommandsTestCase {

  public function testMarvinManagedDrupalExtensionList() {
    $miRootDir = $this->getMarvinIncubatorRootDir();

    $expected = [
      'exitCode' => 0,
      'stdOutput' => $this->getExtensionDirs(),
      'stdError' => implode(PHP_EOL, [
        "[notice] cd '$miRootDir' && composer show -P",
        ' [notice]',
      ]),
    ];

    $args = [];
    $options = [];
    $options += $this->getCommonCommandLineOptions();

    $this->drush(
      'marvin:managed-drupal-extension:list',
      $args,
      $options,
      NULL,
      NULL,
      $expected['exitCode']
    );

    static::assertSame($expected['stdError'], $this->getErrorOutput(), 'stdError');

    $extensions = Yaml::parse($this->getOutput());
    unset($extensions['drupal/marvin']);

    static::assertSame($expected['stdOutput'], $extensions);
  }

}
