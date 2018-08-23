<?php

declare(strict_types = 1);

namespace Drush\Commands\Tests\marvin_incubator\Unish;

class MarvinBuildNpmTest extends CommandsTestBase {

  /**
   * {@inheritdoc}
   */
  protected $drushCommand = 'marvin:build:npm';

  /**
   * {@inheritdoc}
   */
  public function casesExecuteDrushCommand(): array {
    $options = $this->getDefaultDrushCommandOptions();

    return [
      'basic' => [
        [
          'stdOutput' => [
            'same' => [
              'todo-1' => '',
            ],
          ],
          'stdError' => [
            'contains' => [
              'nvm which' => "nvm which '9.11.2'",
              'yarn install' => "yarn install",
            ],
          ],
        ],
        ['dummy_m1'],
        $options,
      ],
    ];
  }

}
