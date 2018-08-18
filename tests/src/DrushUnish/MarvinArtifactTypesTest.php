<?php

declare(strict_types = 1);

namespace Drush\Commands\Tests\marvin_incubator\Unish;

class MarvinArtifactTypesCommandsTest extends CommandsTestBase {

  /**
   * {@inheritdoc}
   */
  protected $drushCommand = 'marvin:artifact:types';

  /**
   * {@inheritdoc}
   */
  public function casesExecuteDrushCommand(): array {
    $options = $this->getDefaultDrushCommandOptions();

    return [
      'default' => [
        [
          'stdOutput' => [
            'same' => [
              'stdOutput same' => implode(PHP_EOL, [
                'vanilla:',
                '  label: Vanilla',
                "  description: 'Not customized'",
                '  id: vanilla',
                '  weight: 0',
              ]),
            ],
          ],
        ],
        [],
        $options,
      ],
      'format=table' => [
        [
          'stdOutput' => [
            'same' => [
              'stdOutput same' => implode(PHP_EOL, [
                'ID      Label   Description    ',
                ' vanilla Vanilla Not customized',
              ]),
            ],
          ],
        ],
        [],
        ['format' => 'table'] + $options,
      ],
    ];
  }

}
