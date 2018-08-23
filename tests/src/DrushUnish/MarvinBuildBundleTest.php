<?php

declare(strict_types = 1);

namespace Drush\Commands\Tests\marvin_incubator\Unish;

class MarvinBuildBundleTest extends CommandsTestBase {

  /**
   * {@inheritdoc}
   */
  protected $drushCommand = 'marvin:build:bundle';

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
              'empty' => '',
            ],
          ],
          'stdError' => [
            'contains' => [
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
