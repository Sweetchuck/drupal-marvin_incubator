<?php

declare(strict_types = 1);

namespace Drush\Commands\Tests\marvin_incubator\Unish;

use Drupal\marvin\Utils as MarvinUtils;

class MarvinManagedDrupalExtensionListTest extends CommandsTestBase {

  /**
   * {@inheritdoc}
   */
  protected $drushCommand = 'marvin:managed-drupal-extension:list';

  /**
   * {@inheritdoc}
   */
  public function casesExecuteDrushCommand(): array {
    $options = $this->getDefaultDrushCommandOptions();

    return [
      'default' => [
        [
          'stdOutput' => [
            'same' => [
              'stdOutput same' => implode(PHP_EOL, [
                'drupal/dummy_m1: ' . static::getExtensionsDir() . '/dummy_m1',
                'drupal/dummy_m2: ' . static::getExtensionsDir() . '/dummy_m2',
                'drupal/marvin: ' . MarvinUtils::marvinRootDir(),
              ]),
            ],
          ],
          'stdError' => [],
        ],
        [],
        $options,
      ],
    ];
  }

}
