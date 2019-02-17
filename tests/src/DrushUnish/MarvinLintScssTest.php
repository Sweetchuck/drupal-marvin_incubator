<?php

declare(strict_types = 1);

namespace Drush\Commands\Tests\marvin_incubator\DrushUnish;

class MarvinLintScssTest extends CommandsTestBase {

  /**
   * {@inheritdoc}
   */
  protected $drushCommand = 'marvin:lint:scss';

  /**
   * {@inheritdoc}
   */
  public function casesExecuteDrushCommand(): array {
    return [];
  }

}
