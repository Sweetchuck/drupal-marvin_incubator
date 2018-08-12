<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_incubator;

use Drush\Commands\marvin\CommandsBase;
use Drupal\marvin_incubator\CommandsBaseTrait;
use Drupal\marvin_incubator\Utils as MarvinIncubatorUtils;
use League\Container\ContainerInterface;

class ManagedDrupalExtensionCommands extends CommandsBase {

  use CommandsBaseTrait;

  /**
   * {@inheritdoc}
   */
  public function setContainer(ContainerInterface $container) {
    if (!$container->has('marvin_incubator.utils')) {
      $container->add('marvin_incubator.utils', MarvinIncubatorUtils::class);
    }

    parent::setContainer($container);

    return $this;
  }

  /**
   * Lists the managed Drupal extensions.
   *
   * @command marvin:managed-drupal-extension:list
   * @bootstrap none
   * @option $format
   *   Output format.
   */
  public function managedDrupalExtensionList(
    array $options = [
      'format' => 'yaml',
    ]
  ): array {
    return $this->getManagedDrupalExtensions();
  }

  /**
   * @command dummy
   * @bootstrap none
   */
  public function dummy() {
    $config = $this->getConfig();

    $this->output()->write(yaml_emit($config->export()));
  }

}
