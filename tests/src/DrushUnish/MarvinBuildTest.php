<?php

declare(strict_types = 1);

namespace Drush\Commands\Tests\marvin_incubator\DrushUnish;

class MarvinBuildTest extends CommandsTestBase {

  /**
   * {@inheritdoc}
   */
  protected $drushCommand = 'marvin:build';

  /**
   * {@inheritdoc}
   */
  public function casesExecuteDrushCommand(): array {
    $options = $this->getDefaultDrushCommandOptions();
    $envRvmPath = getenv('rvm_path');

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
              'yarn install' => 'yarn install',
              'bundle install' => "$envRvmPath/rubies/ruby-2.4.1/bin/ruby $envRvmPath/gems/ruby-2.4.1/bin/bundle install\n",
            ],
          ],
        ],
        ['dummy_m1'],
        $options,
      ],
    ];
  }

}
