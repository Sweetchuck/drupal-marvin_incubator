<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin_incubator\Unit;

use Drupal\marvin_incubator\PhpunitConfigGenerator;
use Drupal\marvin_incubator\Utils as MarvinIncubatorUtils;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * @covers \Drupal\marvin_incubator\PhpunitConfigGenerator
 */
class PhpunitConfigGeneratorTest extends TestCase {

  public function casesGenerate(): array {
    $rootDir = MarvinIncubatorUtils::marvinIncubatorDir();
    $casesDir = "$rootDir/tests/fixtures/cases/PhpunitConfigGenerator";

    return [
      'basic' => [
        file_get_contents("$casesDir/basic.expected.xml"),
        Yaml::parseFile("$casesDir/basic.input.yml"),
      ],
    ];
  }

  /**
   * @dataProvider casesGenerate
   */
  public function testGenerate(string $expected, array $args) {
    $generator = new PhpunitConfigGenerator();

    if (array_key_exists('drupalRoot', $args)) {
      $generator->setDrupalRoot($args['drupalRoot']);
    }

    if (array_key_exists('url', $args)) {
      $generator->setUrl($args['url']);
    }

    if (array_key_exists('dbConnection', $args)) {
      $generator->setDbConnection($args['dbConnection']);
    }

    if (array_key_exists('packagePaths', $args)) {
      $generator->setPackagePaths($args['packagePaths']);
    }

    if (array_key_exists('phpVersion', $args)) {
      $generator->setPhpVersion($args['phpVersion']);
    }

    if (array_key_exists('reportsDir', $args)) {
      $generator->setReportsDir($args['reportsDir']);
    }

    $this->assertSame($expected, $generator->generate());
  }

}
