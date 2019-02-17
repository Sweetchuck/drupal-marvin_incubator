<?php

declare(strict_types = 1);

namespace Drush\Commands\Tests\marvin_incubator\DrushUnit;

use Drupal\marvin_incubator\Utils as MarvinIncubatorUtils;
use Drupal\Tests\marvin\Helper\DummyOutput;
use Drush\Config\DrushConfig;
use Drush\Drush;
use League\Container\Container as LeagueContainer;
use PHPUnit\Framework\TestCase;
use Robo\Collection\CollectionBuilder;
use Robo\Robo;
use Symfony\Component\Console\Application as SymfonyApplication;
use Webmozart\PathUtil\Path;

class CommandsTestBase extends TestCase {

  /**
   * @var string
   */
  protected $projectRoot = '';

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
    $this
      ->setUpProjectRoot()
      ->setUpContainers();
  }

  /**
   * @return $this
   */
  protected function setUpProjectRoot() {
    $this->projectRoot = Path::canonicalize(__DIR__ . '/../../..');

    return $this;
  }

  /**
   * @return $this
   */
  protected function setUpContainers() {
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

    return $this;
  }

}
