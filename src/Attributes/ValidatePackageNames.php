<?php

declare(strict_types = 1);

namespace Drupal\marvin_incubator\Attributes;

use Consolidation\AnnotatedCommand\Parser\CommandInfo;
use Drush\Commands\marvin_incubator\BaseHooksCommands;

/**
 * Drush command annotator.
 *
 * @code
 * use Drupal\marvin_incubator\Attributes as MarvinIncubatorCLI;
 *
 * #[MarvinIncubatorCLI\ValidatePackageNames(
 *   locators: [
 *     [
 *       'type' => 'argument',
 *       'name' => 'packageNames',
 *     ],
 *   ],
 * )]
 * @endcode
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class ValidatePackageNames {

  /**
   * @phpstan-param array<array{type: 'option'|'argument', name: string}> $locators
   */
  public function __construct(public array $locators = []) {
  }

  /**
   * @phpstan-param \ReflectionAttribute<\Robo\Tasks> $attribute
   */
  public static function handle(\ReflectionAttribute $attribute, CommandInfo $commandInfo): void {
    $args = $attribute->getArguments();
    $commandInfo->addAnnotation(
      BaseHooksCommands::TAG_VALIDATE_MARVIN_PACKAGE_NAMES,
      json_encode([
        'locators' => $args['locators'] ?? $args[0],
      ]),
    );
  }

}
