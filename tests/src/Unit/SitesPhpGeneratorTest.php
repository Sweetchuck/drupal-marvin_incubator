<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Unit_incubator;

use Drupal\marvin_incubator\SitesPhpGenerator;
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
      'basic' => [
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
    ];
  }

  /**
   * @dataProvider casesGenerate
   */
  public function testGenerate(string $expected, array $options) {
    $generator = new SitesPhpGenerator();
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