<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_incubator;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandError;
use Drupal\marvin\DatabaseVariantTrait;
use Drupal\marvin\PhpVariantTrait;
use Drush\Commands\marvin\CommandsBase;
use Drupal\marvin\Utils as MarvinUtils;
use Drupal\marvin_incubator\CommandsBaseTrait;

class BaseValidatorCommands extends CommandsBase {

  use CommandsBaseTrait;
  use PhpVariantTrait;
  use DatabaseVariantTrait;

  /**
   * @hook validate @marvinArgPackages
   */
  public function hookValidateMarvinArgPackages(CommandData $commandData): ?CommandError {
    $annotationKey = 'marvinArgPackages';
    $annotationData = $commandData->annotationData();
    if (!$annotationData->has($annotationKey)) {
      return NULL;
    }

    $commandErrors = [];
    $argNames = $this->parseMultiValueAnnotation($annotationKey, $annotationData->get($annotationKey));
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

  /**
   * @hook validate @marvinOptionPhpVariants
   *
   * @todo Error when a disabled phpVariant is provided.
   */
  public function hookValidateMarvinPhpVariants(CommandData $commandData): ?CommandError {
    $annotationKey = 'marvinOptionPhpVariants';
    $annotationData = $commandData->annotationData();
    if (!$annotationData->has($annotationKey)) {
      return NULL;
    }

    $commandErrors = [];
    $optionNames = $this->parseMultiValueAnnotation($annotationKey, $annotationData->get($annotationKey));
    foreach ($optionNames as $optionName) {
      $commandErrors[] = $this->hookValidateMarvinOptionPhpVariantsSingle($commandData, $optionName);
    }

    return MarvinUtils::aggregateCommandErrors($commandErrors);
  }

  protected function hookValidateMarvinOptionPhpVariantsSingle(CommandData $commandData, string $optionName): ?CommandError {
    return $this->validateMarvinOptionItemIds(
      $commandData,
      $optionName,
      $this->getConfigPhpVariants(),
      'The following PHP variants are invalid for option "--@optionName": @invalidItemIds'
    );
  }

  /**
   * @hook validate @marvinOptionDatabaseVariants
   *
   * @todo Error when a disabled database variant is provided.
   */
  public function hookValidateMarvinDatabaseVariants(CommandData $commandData): ?CommandError {
    $annotationKey = 'marvinOptionDatabaseVariants';
    $annotationData = $commandData->annotationData();
    if (!$annotationData->has($annotationKey)) {
      return NULL;
    }

    $commandErrors = [];
    $optionNames = $this->parseMultiValueAnnotation($annotationKey, $annotationData->get($annotationKey));
    foreach ($optionNames as $optionName) {
      $commandErrors[] = $this->hookValidateMarvinOptionDatabaseVariantsSingle($commandData, $optionName);
    }

    return MarvinUtils::aggregateCommandErrors($commandErrors);
  }

  protected function hookValidateMarvinOptionDatabaseVariantsSingle(CommandData $commandData, string $optionName): ?CommandError {
    return $this->validateMarvinOptionItemIds(
      $commandData,
      $optionName,
      $this->getConfigDatabaseVariants(),
      'The following Database variants are invalid for option "--@optionName": @invalidItemIds'
    );
  }

  protected function validateMarvinOptionItemIds(
    CommandData $commandData,
    string $optionName,
    array $validItems,
    string $errorMessage
  ): ?CommandError {
    $optionValues = $commandData->input()->getOption($optionName);
    $itemIds = $this->parseOptionValues($optionValues);

    $isArray = is_array($itemIds);
    if (!$isArray) {
      $itemIds = [$itemIds];
    }

    $items = [];
    $invalidItemIds = [];
    foreach ($itemIds as $itemId) {
      if (isset($validItems[$itemId])) {
        $items[$itemId] = $validItems[$itemId];
      }
      else {
        $invalidItemIds[] = $itemId;
      }
    }

    if ($invalidItemIds) {
      // @todo Designed exit codes and messages.
      // @todo The documentation about the return value is not clear.
      // Exception vs CommandError?
      // See https://github.com/consolidation/annotated-command#validate-hook .
      return new CommandError(
        dt(
          $errorMessage,
          [
            '@optionName' => $optionName,
            '@invalidItemIds' => implode(', ', $invalidItemIds),
          ]
        ),
        1
      );
    }

    if (!$items) {
      $items = $validItems;
    }

    if (!$isArray) {
      $items = reset($items);
    }

    $commandData->input()->setOption($optionName, $items);

    return NULL;
  }

  protected function parseOptionValues(array $optionValues): array {
    $items = [];
    foreach ($optionValues as $optionValue) {
      $items = array_merge($items, $this->explodeCommaSeparatedList($optionValue));
    }

    return array_unique($items);
  }

  protected function parseMultiValueAnnotation(string $name, string $value): array {
    return $this->explodeCommaSeparatedList($value);
  }

  protected function explodeCommaSeparatedList(string $items): array {
    return array_filter(preg_split('/\s*,\s*/', trim($items)));
  }

}
