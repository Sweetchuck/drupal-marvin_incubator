<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_incubator;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandResult;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drush\Commands\marvin\CommandsBase;
use Drupal\marvin_incubator\CommandsBaseTrait;
use Drupal\marvin_incubator\Utils as MarvinIncubatorUtils;
use League\Container\Container as LeagueContainer;
use League\Container\ContainerAwareInterface;
use Psr\Container\ContainerInterface;

class ManagedDrupalExtensionCommands extends CommandsBase {

  use CommandsBaseTrait;

  /**
   * {@inheritdoc}
   */
  public function setContainer(ContainerInterface $container): ContainerAwareInterface {
    // @todo Use @hook pre-init *.
    if ($container instanceof LeagueContainer) {
      if (!$container->has('marvin_incubator.utils')) {
        $container->add('marvin_incubator.utils', MarvinIncubatorUtils::class);
      }
    }

    parent::setContainer($container);

    return $this;
  }

  /**
   * Lists the managed Drupal extensions.
   *
   * @command marvin:managed-drupal-extension:list
   *
   * @bootstrap none
   *
   * @noinspection PhpUnusedParameterInspection
   */
  public function cmdManagedDrupalExtensionListExecute(
    array $options = [
      'format' => 'table',
    ]
  ): CommandResult {
    return CommandResult::data($this->getManagedDrupalExtensions());
  }

  /**
   * @hook process marvin:managed-drupal-extension:list
   */
  public function cmdMarvinManagedDrupalExtensionListProcess($result, CommandData $commandData) {
    if (!($result instanceof CommandResult)) {
      return;
    }

    $extensions = $result->getOutputData();
    $format = $commandData->input()->getOption('format');
    if ($format === 'table') {
      $result->setOutputData(new RowsOfFields($extensions));
    }
  }

}
