<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_incubator;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandError;
use Drupal\marvin\PhpVariantTrait;
use Drush\Commands\marvin\CommandsBase;
use Drupal\marvin\Utils as MarvinUtils;
use Drupal\marvin_incubator\CommandsBaseTrait;

class BaseValidatorCommands extends CommandsBase {

  use CommandsBaseTrait;
  use PhpVariantTrait;

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
    $optionValues = $commandData->input()->getOption($optionName);
    $phpVariantIds = $this->foo($optionValues);

    $isArray = is_array($phpVariantIds);
    if (!$isArray) {
      $phpVariantIds = [$phpVariantIds];
    }

    $validPhpVariants = $this->getConfigPhpVariants();

    $phpVariants = [];
    $invalidPhpVariantIds = [];
    foreach ($phpVariantIds as $phpVariantId) {
      if (isset($validPhpVariants[$phpVariantId])) {
        $phpVariants[$phpVariantId] = $validPhpVariants[$phpVariantId];
      }
      else {
        $invalidPhpVariantIds[] = $phpVariantId;
      }
    }

    if ($invalidPhpVariantIds) {
      // @todo Designed exit codes and messages.
      // @todo The documentation about the return value is not clear.
      // Exception vs CommandError?
      // See https://github.com/consolidation/annotated-command#validate-hook .
      return new CommandError(
        dt(
          'The following PHP variants are invalid for option "--@optionName": @phpVariantIds',
          [
            '@optionName' => $optionName,
            '@phpVariantIds' => implode(', ', $invalidPhpVariantIds),
          ]
        ),
        1
      );
    }

    if (!$phpVariants) {
      $phpVariants = $validPhpVariants;
    }

    if (!$isArray) {
      $phpVariants = reset($phpVariants);
    }

    $commandData->input()->setOption($optionName, $phpVariants);

    return NULL;
  }

  protected function foo(array $optionValues): array {
    $items = [];
    foreach ($optionValues as $optionValue) {
      $items = array_merge($items, $this->explodeCommaSeparatedList($optionValue));
    }

    return array_unique($items);
  }

  protected function parseMultiValueAnnotation(string $name, string $value): array {
    return $this->explodeCommaSeparatedList($value);
  }

  protected  function explodeCommaSeparatedList(string $items): array {
    return array_filter(preg_split('/\s*,\s*/', trim($items)));
  }

}
