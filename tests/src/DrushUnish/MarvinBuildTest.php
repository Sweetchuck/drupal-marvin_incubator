<?php

declare(strict_types = 1);

namespace Drush\Commands\Tests\marvin_incubator\Unish;

class MarvinBuildTest extends CommandsTestBase {

  /**
   * {@inheritdoc}
   */
  protected $drushCommand = 'marvin:build';

  /**
   * {@inheritdoc}
   */
  public function casesExecuteDrushCommand(): array {
    return [];
  }

}
