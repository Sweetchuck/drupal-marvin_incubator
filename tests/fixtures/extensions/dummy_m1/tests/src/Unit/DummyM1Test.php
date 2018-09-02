<?php

declare(strict_types = 1);

namespace Drupal\Tests\dummy_m1\Unit;

use Drupal\dummy_m1\DummyM1;
use Drupal\Tests\UnitTestCase;

/**
 * @group dummy_m1
 *
 * @coversDefaultClass \Drupal\dummy_m1\DummyM1
 */
class DummyM1Test extends UnitTestCase {

  public function casesEcho(): array {
    return [
      'basic' => ['a', 'a'],
    ];
  }

  /**
   * @dataProvider casesEcho
   */
  public function testEcho(string $expected, string $input): void {
    $this->assertEquals($expected, (new DummyM1())->echo($input));
  }

}
