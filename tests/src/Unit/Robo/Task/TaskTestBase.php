<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin_incubator\Unit\Robo\Task;

use Drupal\marvin\Utils as MarvinUtils;
use Drupal\Tests\marvin\Helper\DummyOutput;
use Drupal\Tests\marvin_incubator\Helper\TaskBuilder;
use Drush\Config\DrushConfig;
use Drush\Drush;
use League\Container\Container as LeagueContainer;
use PHPUnit\Framework\TestCase;
use Robo\Collection\CollectionBuilder;
use Robo\Robo;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\ErrorHandler\BufferingLogger;

class TaskTestBase extends TestCase {

  protected LeagueContainer $container;

  protected DrushConfig $config;

  protected CollectionBuilder $builder;

  protected TaskBuilder $taskBuilder;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    Robo::unsetContainer();
    Drush::unsetContainer();

    $this->container = new LeagueContainer();
    $application = new SymfonyApplication('MarvinIncubator - DrushUnit', '1.0.0');
    $this->config = (new DrushConfig())
      ->set('drush.vendor-dir', '.');
    $input = NULL;
    $output = new DummyOutput(DummyOutput::VERBOSITY_DEBUG, FALSE, NULL);

    $this->container->add('container', $this->container);
    $this->container->add('marvin.utils', MarvinUtils::class);

    Robo::configureContainer($this->container, $application, $this->config, $input, $output);
    Drush::setContainer($this->container);
    $this->container->addShared('logger', BufferingLogger::class);

    $this->builder = CollectionBuilder::create($this->container, NULL);
    $this->taskBuilder = new TaskBuilder();
    $this->taskBuilder->setContainer($this->container);
    $this->taskBuilder->setBuilder($this->builder);
  }

  public static function assertRoboTaskLogEntries(array $expected, array $actual) {
    static::assertSameSize($expected, $actual, 'Number of log messages');

    foreach ($actual as $key => $log) {
      unset($log[2]['task']);
      static::assertSame($expected[$key], $log);
    }
  }

}
