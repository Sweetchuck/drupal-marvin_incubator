<?php

declare(strict_types = 1);

namespace Drush\Commands\Tests\marvin_incubator\DrushUnit;

use Drupal\marvin_incubator\Utils as MarvinIncubatorUtils;
use Drush\Commands\Tests\marvin_incubator\Helper\DummyOutput;
use Drush\Config\DrushConfig;
use Drush\Drush;
use League\Container\Container as LeagueContainer;
use PHPUnit\Framework\TestCase;
use Robo\Collection\CollectionBuilder;
use Robo\Robo;
use Symfony\Component\Console\Application as SymfonyApplication;

class CommandsTestBase extends TestCase {

  /**
   * @var \League\Container\ContainerInterface
   */
  protected $container;

  /**
   * @var \Drush\Config\DrushConfig
   */
  protected $config;

  /**
   * @var \Robo\Collection\CollectionBuilder
   */
  protected $builder;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    Robo::unsetContainer();
    Drush::unsetContainer();

    $this->container = new LeagueContainer();
    $application = new SymfonyApplication('MarvinIncubator - DrushUnit', '1.0.0');
    $this->config = (new DrushConfig())
      ->set('drush.vendor-dir', '.');
    $input = NULL;
    $outputConfig = [];
    $output = new DummyOutput($outputConfig);

    $this->container->add('container', $this->container);
    $this->container->add('marvin_incubator.utils', MarvinIncubatorUtils::class);

    Robo::configureContainer($this->container, $application, $this->config, $input, $output);
    Drush::setContainer($this->container);

    $this->builder = CollectionBuilder::create($this->container, NULL);
  }

}
