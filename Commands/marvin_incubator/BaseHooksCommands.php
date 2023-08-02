<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_incubator;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandError;
use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Drupal\marvin\DatabaseVariantTrait;
use Drupal\marvin\PhpVariantTrait;
use Drupal\marvin\Utils as MarvinUtils;
use Drupal\marvin_incubator\CommandsBaseTrait;
use Drupal\marvin_incubator\Utils as MarvinIncubatorUtils;
use Drush\Attributes as CLI;
use Drush\Commands\marvin\CommandsBase;
use League\Container\Container as LeagueContainer;
use League\Container\ContainerAwareInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputInterface;

class BaseHooksCommands extends CommandsBase {

  use CommandsBaseTrait;
  use PhpVariantTrait;
  use DatabaseVariantTrait;

  public const TAG_VALIDATE_MARVIN_REGEXP = 'validate-marvin-regexp';
  public const TAG_VALIDATE_MARVIN_DATABASE_ID = 'validate-marvin-database-id';
  public const TAG_VALIDATE_MARVIN_PACKAGE_NAMES = 'validate-marvin-package-names';

  public function setContainer(ContainerInterface $container): ContainerAwareInterface {
    if ($container instanceof LeagueContainer) {
      if (!$container->has('marvin_incubator.utils')) {
        $container->add('marvin_incubator.utils', MarvinIncubatorUtils::class);
      }
    }

    parent::setContainer($container);

    return $this;
  }

  /**
   * @hook validate @marvinArgPackages
   *
   * @deprecated Use \Drupal\marvin_incubator\Attributes\ValidatePackageNames.
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

  #[CLI\Hook(type: HookManager::ARGUMENT_VALIDATOR, selector: self::TAG_VALIDATE_MARVIN_PACKAGE_NAMES)]
  public function onHookValidateMarvinPackageNames(CommandData $commandData): ?CommandError {
    $annotationKey = self::TAG_VALIDATE_MARVIN_PACKAGE_NAMES;
    $annotationData = $commandData->annotationData();
    if (!$annotationData->has($annotationKey)) {
      return NULL;
    }

    $commandErrors = [];
    $args = json_decode($annotationData->get($annotationKey), TRUE);
    foreach ($args['locators'] as $locator) {
      $commandErrors[] = $this->validatePackageNames($commandData, $locator);
    }

    return MarvinUtils::aggregateCommandErrors($commandErrors);
  }

  /**
   * @phpstan-param array{type: string, name: string} $locator
   */
  protected function validatePackageNames(CommandData $commandData, array $locator): ?CommandError {
    $packageNames = $this->getInputValue($commandData->input(), $locator);
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
          $locator['type'] === 'argument' ?
            'The following packages are invalid for argument "@inputName": @packageNames'
            : 'The following packages are invalid for option --@inputName": @packageNames',
          [
            '@inputName' => $locator['name'],
            '@packageNames' => implode(', ', $invalidPackageNames),
          ],
        ),
        1,
      );
    }

    if (!$packages) {
      $packages = array_keys($this->getManagedDrupalExtensions());
    }

    $locator['type'] === 'argument' ?
      $commandData->input()->setArgument($locator['name'], $packages)
      : $commandData->input()->setOption($locator['name'], $packages);

    return NULL;
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
    return $this->validateMarvinInputItemIds(
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
  public function hookValidateMarvinOptionDatabaseVariants(CommandData $commandData): ?CommandError {
    $annotationKey = 'marvinOptionDatabaseVariants';
    $annotationData = $commandData->annotationData();
    if (!$annotationData->has($annotationKey)) {
      return NULL;
    }

    $commandErrors = [];
    $locators = $this->parseMultiValueAnnotation($annotationKey, $annotationData->get($annotationKey));
    foreach ($locators as $locator) {
      $commandErrors[] = $this->hookValidateMarvinOptionDatabaseVariantsSingle($commandData, $locator);
    }

    return MarvinUtils::aggregateCommandErrors($commandErrors);
  }

  /**
   * @todo Move this into \Drush\Commands\marvin\AcHooksCommands.
   */
  #[CLI\Hook(type: HookManager::ARGUMENT_VALIDATOR, selector: self::TAG_VALIDATE_MARVIN_REGEXP)]
  public function onHookValidateMarvinRegexp(CommandData $commandData): ?CommandError {
    $annotationKey = self::TAG_VALIDATE_MARVIN_REGEXP;
    $annotationData = $commandData->annotationData();
    if (!$annotationData->has($annotationKey)) {
      return NULL;
    }

    $commandErrors = [];
    $args = json_decode($annotationData->get($annotationKey), TRUE);
    foreach ($args['locators'] as $locator) {
      $commandErrors[] = $this->validateByRegexp($commandData, $locator, $args['pattern']);
    }

    return MarvinUtils::aggregateCommandErrors($commandErrors);
  }

  #[CLI\Hook(type: HookManager::ARGUMENT_VALIDATOR, selector: self::TAG_VALIDATE_MARVIN_DATABASE_ID)]
  public function onHookValidateMarvinDatabaseId(CommandData $commandData): ?CommandError {
    $annotationKey = self::TAG_VALIDATE_MARVIN_DATABASE_ID;
    $annotationData = $commandData->annotationData();
    if (!$annotationData->has($annotationKey)) {
      return NULL;
    }

    $commandErrors = [];
    $args = json_decode($annotationData->get($annotationKey), TRUE);
    foreach ($args['locators'] as $locator) {
      $commandErrors[] = $this->validateDatabaseId($commandData, $locator);
    }

    return MarvinUtils::aggregateCommandErrors($commandErrors);
  }

  /**
   * @phpstan-param array{type: string, name: string} $locator
   */
  protected function getInputValue(InputInterface $input, array $locator): mixed {
    return $locator['type'] === 'argument' ?
      $input->getArgument($locator['name'])
      : $input->getOption($locator['name']);
  }

  protected function validateByRegexp(
    CommandData $commandData,
    string $locator,
    string $pattern,
  ): ?CommandError {
    [$type, $name] = explode(':', $locator);
    $value = $type === 'argument' ?
      $commandData->input()->getArgument($name)
      : $commandData->input()->getOption($name);

    $namedPatterns = $this->getNamedRegexpPatterns();
    if (array_key_exists($pattern, $namedPatterns)) {
      $pattern = $namedPatterns[$pattern];
    }

    return preg_match($pattern, $value) === 1 ?
      NULL
      : new CommandError(sprintf(
        'Value "%s" provided for %s does not match to pattern %s',
        $value,
        ($type === 'argument' ? "argument '$name'" : "option --$name"),
        $pattern,
      ));
  }

  protected function getNamedRegexpPatterns(): array {
    return [
      'machineNameStrict' => '/^[a-z][a-z0-9]*$/',
      'machineNameNormal' => '/^[a-z][a-z0-9_]*$/',
    ];
  }

  protected function validateDatabaseId(CommandData $commandData, string $locator): ?CommandError {
    [$type, $name] = explode(':', $locator);
    $value = $type === 'argument' ?
      $commandData->input()->getArgument($name)
      : $commandData->input()->getOption($name);

    $allowedValues = $this->getConfigDatabaseVariants();
    if (array_key_exists($value, $allowedValues)) {
      return NULL;
    }

    $message = $type === 'argument' ?
      'Value "{{ current }}" provided for argument "{{ name }}" is invalid. Allowed values: {{ allowedValues }}'
      : 'Value "{{ current }}" provided for option --{{ name }} is invalid. Allowed values: {{ allowedValues }}';

    return new CommandError(strtr(
      $message,
      [
        '{{ current }}' => $value,
        '{{ name }}' => $name,
        '{{ allowedValues }}' => implode(', ', array_keys($allowedValues)),
      ],
    ));
  }

  protected function hookValidateMarvinOptionDatabaseVariantsSingle(CommandData $commandData, string $optionName): ?CommandError {
    return $this->validateMarvinInputItemIds(
      $commandData,
      $optionName,
      $this->getConfigDatabaseVariants(),
      'The following Database variants are invalid for option "--@optionName": @invalidItemIds'
    );
  }

  protected function validateMarvinInputItemIds(
    CommandData $commandData,
    string $locator,
    array $validItems,
    string $errorMessage,
  ): ?CommandError {
    [$type, $name] = explode(':', $locator);
    $values = $type === 'argument' ?
      $commandData->input()->getArgument($name)
      : $commandData->input()->getOption($name);
    $itemIds = $this->parseInputValues($values);

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
            '@name' => $name,
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

    $commandData->input()->setOption($name, $items);

    return NULL;
  }

  protected function parseInputValues(array $values): array {
    $items = [];
    foreach ($values as $value) {
      $items = array_merge($items, $this->explodeCommaSeparatedList($value));
    }

    return array_unique($items);
  }

  protected function parseMultiValueAnnotation(string $name, string $value): array {
    return $this->explodeCommaSeparatedList($value);
  }

  protected function explodeCommaSeparatedList(string $items): array {
    return array_filter(
      preg_split('/\s*,\s*/', trim($items)),
      'mb_strlen',
    );
  }

}
