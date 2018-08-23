<?php

declare(strict_types = 1);

namespace Drush\Commands\Tests\marvin_incubator\Unish;

class MarvinLintPhpTest extends CommandsTestBase {

  /**
   * {@inheritdoc}
   */
  protected $drushCommand = 'marvin:lint:php';

  /**
   * {@inheritdoc}
   */
  public function casesExecuteDrushCommand(): array {
    $options = $this->getDefaultDrushCommandOptions();
    $fileListerCommand = "git ls-files -z -- '*.profile' '*.module' '*.theme' '*.engine' '*.install' '*.php'";

    return [
      'dummy_m1' => [
        [
          'exitCode' => 0,
          'stdError' => [
            'contains' => [
              'fileListerCommand' => $fileListerCommand,
            ],
          ],
        ],
        ['dummy_m1'],
        $options,
      ],
      'dummy_m2' => [
        [
          'exitCode' => 1,
          'stdError' => [
            'contains' => [
              'fileListerCommand' => $fileListerCommand,
              'dummy_m2.module' => 'Parse error: syntax error, unexpected end of file in dummy_m2.module on line 8',
            ],
          ],
        ],
        ['dummy_m2'],
        $options,
      ],
    ];
  }

}
