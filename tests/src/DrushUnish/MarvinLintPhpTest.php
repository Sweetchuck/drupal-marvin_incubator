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
    return [];
  }

}
