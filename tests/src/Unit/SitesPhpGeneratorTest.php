<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin_incubator\Unit;

use Drupal\marvin_incubator\SitesPhpGen;
use PHPUnit\Framework\TestCase;

class SitesPhpGeneratorTest extends TestCase {

  public function casesGenerate(): array {
    return [
      'empty' => [
        implode(PHP_EOL, [
          '<?php',
          '',
          '$sites = [];',
          '',
        ]),
        [],
      ],
      'without db' => [
        implode(PHP_EOL, [
          '<?php',
          '',
          '$sites = [];',
          '',
        ]),
        [
          'siteNames' => ['sn1', 'sn2'],
        ],
      ],
      'without sites' => [
        implode(PHP_EOL, [
          '<?php',
          '',
          '$sites = [];',
          '',
        ]),
        [
          'databaseVariantIds' => ['db1'],
        ],
      ],
      'db1 sn1' => [
        implode(PHP_EOL, [
          '<?php',
          '',
          '$sites = [',
          "  'db1.sn1.d8.localhost' => 'sn1.db1',",
          '];',
          '',
        ]),
        [
          'databaseVariantIds' => ['db1'],
          'siteNames' => ['sn1'],
        ],
      ],
      'db1 sn1 with patterns' => [
        implode(PHP_EOL, [
          '<?php',
          '',
          '$sites = [',
          "  'db1.sn1.my-host' => 'foo.sn1.bar.db1',",
          '];',
          '',
        ]),
        [
          'databaseVariantIds' => ['db1'],
          'siteNames' => ['sn1'],
          'urlPattern' => '{{ dbId }}.{{ siteName }}.my-host',
          'siteDirPattern' => 'foo.{{ siteName }}.bar.{{ dbId }}',
        ],
      ],
      'db1 sn2' => [
        implode(PHP_EOL, [
          '<?php',
          '',
          '$sites = [',
          "  'db1.sn1.d8.localhost' => 'sn1.db1',",
          "  'db1.sn2.d8.localhost' => 'sn2.db1',",
          '];',
          '',
        ]),
        [
          'databaseVariantIds' => ['db1'],
          'siteNames' => ['sn1', 'sn2'],
        ],
      ],
      'db2 sn2' => [
        implode(PHP_EOL, [
          '<?php',
          '',
          '$sites = [',
          "  'db1.sn1.d8.localhost' => 'sn1.db1',",
          "  'db2.sn1.d8.localhost' => 'sn1.db2',",
          "  'db1.sn2.d8.localhost' => 'sn2.db1',",
          "  'db2.sn2.d8.localhost' => 'sn2.db2',",
          '];',
          '',
        ]),
        [
          'databaseVariantIds' => ['db1', 'db2'],
          'siteNames' => ['sn1', 'sn2'],
        ],
      ],
      'db2 sn1' => [
        implode(PHP_EOL, [
          '<?php',
          '',
          '$sites = [',
          "  'db1.sn1.d8.localhost' => 'sn1.db1',",
          "  'db2.sn1.d8.localhost' => 'sn1.db2',",
          '];',
          '',
        ]),
        [
          'databaseVariantIds' => ['db1', 'db2'],
          'siteNames' => ['sn1'],
        ],
      ],
    ];
  }

  /**
   * @dataProvider casesGenerate
   */
  public function testGenerate(string $expected, array $options) {
    $generator = new SitesPhpGen();
    if (array_key_exists('siteNames', $options)) {
      $generator->setSiteNames($options['siteNames']);
    }

    if (array_key_exists('databaseVariantIds', $options)) {
      $generator->setDatabaseVariantIds($options['databaseVariantIds']);
    }

    if (array_key_exists('siteDirPattern', $options)) {
      $generator->setSiteDirPattern($options['siteDirPattern']);
    }

    if (array_key_exists('urlPattern', $options)) {
      $generator->setUrlPattern($options['urlPattern']);
    }

    $this->assertSame($expected, $generator->generate());
  }

}
