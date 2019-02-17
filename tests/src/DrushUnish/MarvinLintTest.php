<?php

declare(strict_types = 1);

namespace Drush\Commands\Tests\marvin_incubator\DrushUnish;

class MarvinLintTest extends CommandsTestBase {

  /**
   * {@inheritdoc}
   */
  protected $drushCommand = 'marvin:lint';

  /**
   * {@inheritdoc}
   */
  public function casesExecuteDrushCommand(): array {
    $options = $this->getDefaultDrushCommandOptions();

    return [
      'default' => [
        [
          'stdError' => [
            'contains' => [
              'stdError contains - phpcs' => "/bin/phpcs --standard='Drupal,DrupalPractice' --report='json'",
              'stdError contains - scss-lint' => 'bundle exec scss-lint',
            ],
          ],
        ],
        [
          'dummy_m1',
        ],
        $options,
      ],
    ];
  }

}
