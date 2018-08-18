<?php

declare(strict_types = 1);

namespace Drush\Commands\Tests\marvin_incubator\Unish;

class MarvinTestPhpunitTest extends CommandsTestBase {

  /**
   * {@inheritdoc}
   */
  protected $drushCommand = 'marvin:test:phpunit';

  /**
   * {@inheritdoc}
   */
  public function casesExecuteDrushCommand(): array {
    return [];
  }

}
