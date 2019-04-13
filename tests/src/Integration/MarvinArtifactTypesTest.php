<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin_incubator\Integration;

/**
 * @group marvin_incubator
 * @group drush_command
 */
class MarvinArtifactTypesCommandsTest extends CommandsTestCase {

  /**
   * {@inheritdoc}
   */
  public function casesExecuteDrushCommand(): array {
    $options = $this->getCommonCommandLineOptions();

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
        'marvin:artifact:types',
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
        'marvin:artifact:types',
        [],
        ['format' => 'table'] + $options,
      ],
    ];
  }

}
