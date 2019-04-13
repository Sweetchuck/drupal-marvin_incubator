<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin_incubator\Integration;

/**
 * @group marvin_incubator
 * @group drush-command
 */
class MarvinLintComposerValidateTest extends CommandsTestCase {

  /**
   * {@inheritdoc}
   */
  public function casesExecuteDrushCommand(): array {
    $baseDir = $this->getMarvinIncubatorRootDir();
    $fixturesDir = $this->fixturesDir;

    $options = $this->getCommonCommandLineOptions();
    $envVars = $this->getCommonCommandLineEnvVars();

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
              'stdError contains' => " Running composer validate in $baseDir/$fixturesDir/extensions/dummy_m1\n",
            ],
          ],
        ],
        'marvin:lint:composer-validate',
        ['dummy_m1'],
        $options,
        $envVars,
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
              'stdError contains' => " Running composer validate in $baseDir/$fixturesDir/extensions/dummy_m2\n",
            ],
          ],
        ],
        'marvin:lint:composer-validate',
        ['dummy_m2'],
        $options,
        $envVars,
      ],
    ];
  }

}
