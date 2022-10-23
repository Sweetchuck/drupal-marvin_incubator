<?php

declare(strict_types = 1);

namespace Drupal\marvin_incubator\Attributes;

use Consolidation\AnnotatedCommand\Parser\CommandInfo;
use Drush\Commands\marvin_incubator\BaseValidatorCommands;

#[\Attribute(\Attribute::TARGET_METHOD)]
class ValidateRegexp {

  protected array $locators = [];

  protected string $pattern = '';

  public function __construct(
    array $locators,
    string $pattern,
  ) {
    $this->locators = $locators;
    $this->pattern = $pattern;
  }

  public static function handle(\ReflectionAttribute $attribute, CommandInfo $commandInfo) {
    $args = $attribute->getArguments();
    $commandInfo->addAnnotation(
      BaseValidatorCommands::TAG_VALIDATE_MARVIN_REGEXP,
      json_encode([
        'locators' => $args['locators'] ?? $args[0],
        'pattern' => $args['pattern'] ?? $args[1],
      ]),
    );
  }

}
