<?php

declare(strict_types = 1);

namespace Drush\Commands\Tests\marvin_incubator\DrushUnish;

class MarvinLintPhpcsTest extends CommandsTestBase {

  /**
   * {@inheritdoc}
   */
  protected $drushCommand = 'marvin:lint:phpcs';

  /**
   * {@inheritdoc}
   */
  public function casesExecuteDrushCommand(): array {
    $options = $this->getDefaultDrushCommandOptions();
    $extensionsDir = $this->getExtensionsDir();

    return [
      'dummy_m1' => [
        [
          'exitCode' => 0,
          'stdOutput' => [
            'same' => [
              'stdOutput same' => '',
            ],
          ],
          'stdError' => [
            'contains' => [
              'stdError contains' => "cd '$extensionsDir/dummy_m1' && ../../../../bin/phpcs --standard='Drupal,DrupalPractice' --report='json'",
            ],
          ],
        ],
        ['dummy_m1'],
        $options,
      ],
      'dummy_m2' => [
        [
          'exitCode' => 2,
          'stdOutput' => [
            'same' => [
              'stdOutput same' => implode(PHP_EOL, [
                '../extensions/dummy_m2/dummy_m2.module',
                '+----------+------+---------------------------------------------------------+',
                '| Severity | Line | Message                                                 |',
                '+----------+------+---------------------------------------------------------+',
                '| error    |    4 | The second line in the file doc comment must be "@file" |',
                '+----------+------+---------------------------------------------------------+',
              ]),
            ],
          ],
          'stdError' => [
            'contains' => [
              'stdError contains' => "cd '$extensionsDir/dummy_m2' && ../../../../bin/phpcs --standard='Drupal,DrupalPractice' --report='json' -- 'dummy_m2.module'",
            ],
          ],
        ],
        ['dummy_m2'],
        $options,
      ],
    ];
  }

}
