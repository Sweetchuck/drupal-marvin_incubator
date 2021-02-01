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
    $fixturesDir = static::$fixturesDir;

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
            'stringContainsString' => [
              'stdError stringContainsString' => " Running composer validate in $baseDir/$fixturesDir/repository/drupal/dummy_m1\n",
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
                './composer.json is valid for simple usage with Composer but has',
                'strict errors that make it unable to be published as a package',
                'See https://getcomposer.org/doc/04-schema.md for details on the schema',
                '# Publish errors',
                '- description : The property description is required',
              ]),
            ],
          ],
          'stdError' => [
            'stringContainsString' => [
              'stdError stringContainsString' => " Running composer validate in $baseDir/$fixturesDir/repository/drupal/dummy_m2\n",
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
