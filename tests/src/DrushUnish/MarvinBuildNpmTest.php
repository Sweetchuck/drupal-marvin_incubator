<?php

declare(strict_types = 1);

namespace Drush\Commands\Tests\marvin_incubator\Unish;

class MarvinBuildNpmTest extends CommandsTestBase {

  /**
   * {@inheritdoc}
   */
  protected $drushCommand = 'marvin:build:npm';

  /**
   * {@inheritdoc}
   */
  public function casesExecuteDrushCommand(): array {
    return [];
  }

}
