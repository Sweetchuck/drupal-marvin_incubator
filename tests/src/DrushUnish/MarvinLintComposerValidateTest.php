<?php

declare(strict_types = 1);

namespace Drush\Commands\Tests\marvin_incubator\Unish;

class MarvinLintComposerValidateTest extends CommandsTestBase {

  /**
   * {@inheritdoc}
   */
  protected $drushCommand = 'marvin:lint:composer-validate';

  /**
   * {@inheritdoc}
   */
  public function casesExecuteDrushCommand(): array {
    $fixturesDir = static::getTmp();
    $options = $this->getDefaultDrushCommandOptions();

    return [
      'dummy_m1' => [
        [
          'exitCode' => 0,
          'stdOutput' => [
            'same' => [
              'stdOutput same' => './composer.json is valid',
            ],
          ],
          'stdError' => [
            'contains' => [
              'stdError contains' => " Running composer validate in $fixturesDir/extensions/dummy_m1\n",
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
                './composer.json is valid for simple usage with composer but has',
                'strict errors that make it unable to be published as a package:',
                'See https://getcomposer.org/doc/04-schema.md for details on the schema',
                'description : The property description is required',
              ]),
            ],
          ],
          'stdError' => [
            'contains' => [
              'stdError contains' => " Running composer validate in $fixturesDir/extensions/dummy_m2\n",
            ],
          ],
        ],
        ['dummy_m2'],
        $options,
      ],
    ];
  }

}
