<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin_incubator\Integration;

use Symfony\Component\Yaml\Yaml;

/**
 * @group marvin_incubator
 * @group drush-command
 */
class MarvinManagedDrupalExtensionListTest extends CommandsTestCase {

  public function casesExecuteDrushCommand(): array {
    $miRootDir = $this->getMarvinIncubatorRootDir();
    $extensions = $this->getExtensionDirs();

    $options = [];
    $options += $this->getCommonCommandLineOptions();
    $args = [];

    return [
      'default' => [
        [
          'stdError' => [
            'same' => [
              'stdError same' => implode(PHP_EOL, [
                "[Composer - Package paths] cd '$miRootDir' && composer show -P",
                ' [Marvin - Managed Drupal extension list]',
              ]),
            ],
          ],
          'stdOutput' => [
            'same' => [
              'stdOutput same' => trim(Yaml::dump($extensions, 99, 2)),
            ],
          ],
          'exitCode' => 0,
        ],
        'marvin:managed-drupal-extension:list',
        $args,
        $options,
      ],
    ];
  }

}
