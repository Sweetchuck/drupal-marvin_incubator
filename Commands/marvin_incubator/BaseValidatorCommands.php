<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_incubator;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandError;
use Drush\Commands\marvin\CommandsBase;
use Drupal\marvin\Utils as MarvinUtils;
use Drupal\marvin_incubator\CommandsBaseTrait;

class BaseValidatorCommands extends CommandsBase {

  use CommandsBaseTrait;

  /**
   * @hook validate @marvinArgPackages
   */
  public function hookValidateMarvinArgPackages(CommandData $commandData): ?CommandError {
    $annotationData = $commandData->annotationData();
    if (!$annotationData->has('marvinArgPackages')) {
      return NULL;
    }

    $commandErrors = [];
    $argNames = array_filter(explode(',', $commandData->annotationData()->get('marvinArgPackages')));
    foreach ($argNames as $argName) {
      $commandErrors[] = $this->hookValidateMarvinArgPackagesSingle($commandData, $argName);
    }

    return MarvinUtils::aggregateCommandErrors($commandErrors);
  }

  protected function hookValidateMarvinArgPackagesSingle(CommandData $commandData, string $argName): ?CommandError {
    $packageNames = $commandData->input()->getArgument($argName);

    $isArray = is_array($packageNames);
    if (!$isArray) {
      $packageNames = [$packageNames];
    }

    $packages = [];
    $invalidPackageNames = [];
    foreach ($packageNames as $packageName) {
      $package = $this->normalizeManagedDrupalExtensionName($packageName);
      if ($package) {
        $packages[] = $package['name'];
      }
      else {
        $invalidPackageNames[] = $packageName;
      }
    }

    if ($invalidPackageNames) {
      // @todo Designed exit codes and messages.
      // @todo The documentation about the return value is not clear.
      // Exception vs CommandError?
      // See https://github.com/consolidation/annotated-command#validate-hook .
      return new CommandError(
        dt(
          'The following packages are invalid for argument "@argName": @packageNames',
          [
            '@argName' => $argName,
            '@packageNames' => implode(', ', $invalidPackageNames),
          ]
        ),
        1
      );
    }

    if (!$packages) {
      $packages = array_keys($this->getManagedDrupalExtensions());
    }

    if (!$isArray) {
      $packages = reset($packages);
    }

    $commandData->input()->setArgument($argName, $packages);

    return NULL;
  }

}
