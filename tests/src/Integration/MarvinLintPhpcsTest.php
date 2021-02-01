<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin_incubator\Integration;

/**
 * @group marvin_incubator
 * @group drush-command
 */
class MarvinLintPhpcsTest extends CommandsTestCase {

  /**
   * {@inheritdoc}
   */
  protected $drushCommand = 'marvin:lint:phpcs';

  /**
   * {@inheritdoc}
   */
  public function casesExecuteDrushCommand(): array {
    $options = $this->getCommonCommandLineOptions();
    $extensionDirs = $this->getExtensionDirs();

    return [
      'dummy_m1' => [
        [
          'exitCode' => 2,
          'stdOutput' => [
            'same' => [
              'stdOutput same' => implode(PHP_EOL, [
                'tests/fixtures/packages/drupal/dummy_m1/src/DummyM1.php',
                '+----------+------+------------------------------+',
                '| Severity | Line | Message                      |',
                '+----------+------+------------------------------+',
                '| error    |    7 | Missing class doc comment    |',
                '| error    |    9 | Missing function doc comment |',
                '+----------+------+------------------------------+',
                '',
                'tests/fixtures/packages/drupal/dummy_m1/tests/src/Unit/DummyM1Test.php',
                '+----------+------+------------------------------------------+',
                '| Severity | Line | Message                                  |',
                '+----------+------+------------------------------------------+',
                '| error    |   10 | Missing short description in doc comment |',
                '| error    |   17 | Missing function doc comment             |',
                '| error    |   23 | Missing short description in doc comment |',
                '+----------+------+------------------------------------------+',
              ]),
            ],
          ],
          'stdError' => [
            'stringContainsString' => [
              'stdError stringContainsString' => implode(' ', [
                "cd '{$extensionDirs['drupal/dummy_m1']}'",
                '&&',
                '../../../../../vendor/bin/phpcs',
                "--report='json'",
              ]),
            ],
          ],
        ],
        'marvin:lint:phpcs',
        ['dummy_m1'],
        $options,
      ],
      'dummy_m2' => [
        [
          'exitCode' => 2,
          'stdOutput' => [
            'same' => [
              'stdOutput same' => implode(PHP_EOL, [
                'tests/fixtures/packages/drupal/dummy_m2/dummy_m2.module',
                '+----------+------+---------------------------------------------------------+',
                '| Severity | Line | Message                                                 |',
                '+----------+------+---------------------------------------------------------+',
                '| error    |    4 | The second line in the file doc comment must be "@file" |',
                '+----------+------+---------------------------------------------------------+',
              ]),
            ],
          ],
          'stdError' => [
            'stringContainsString' => [
              'stdError stringContainsString' => "cd '{$extensionDirs['drupal/dummy_m2']}' && ../../../../../vendor/bin/phpcs --report='json'",
            ],
          ],
        ],
        'marvin:lint:phpcs',
        ['dummy_m2'],
        $options,
      ],
    ];
  }

}
