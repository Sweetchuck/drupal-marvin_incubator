<?php

declare(strict_types = 1);

namespace Drupal\marvin_incubator\Attributes;

use Consolidation\AnnotatedCommand\Parser\CommandInfo;
use Drush\Commands\marvin_incubator\BaseHooksCommands;

#[\Attribute(\Attribute::TARGET_METHOD)]
class ValidatePackageNames {

  protected array $locators = [];

  public function __construct(
    array $locators = [],
  ) {
    $this->locators = $locators;
  }

  public static function handle(\ReflectionAttribute $attribute, CommandInfo $commandInfo) {
    $args = $attribute->getArguments();
    $commandInfo->addAnnotation(
      BaseHooksCommands::TAG_VALIDATE_MARVIN_PACKAGE_NAMES,
      json_encode([
        'locators' => $args['locators'] ?? $args[0],
      ]),
    );
  }

}
