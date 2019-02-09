<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_incubator\Lint;

use Drupal\marvin_incubator\CommandsBaseTrait;
use Drush\Commands\marvin\Lint\CommandsBase;
use Robo\Collection\CollectionBuilder;
use Robo\State\Data as RoboStateData;
use Symfony\Component\Console\Input\InputInterface;

/**
 * @deprecated Replace this with a NodeJS based SCSS linter.
 * @todo Replace this with a NodeJS based SCSS linter.
 */
class ScssLintCommands extends CommandsBase {

  use CommandsBaseTrait;

  /**
   * @hook on-event marvin:git-hook:pre-commit
   */
  public function onEventMarvinGitHookPreCommit(InputInterface $input): array {
    $package = $this->normalizeManagedDrupalExtensionName($input->getArgument('packagePath'));

    return [
      'marvin.lint.scss' => [
        'task' => $this->lintScss([$package['name']]),
      ],
    ];
  }

  /**
   * @hook on-event marvin:lint
   */
  public function onEventMarvinLint(InputInterface $input): array {
    return [
      'marvin.lint.scss' => [
        'task' => $this->lintScss($input->getArgument('packages')),
      ],
    ];
  }

  /**
   * @command marvin:lint:scss
   * @bootstrap none
   *
   * @marvinArgPackages packages
   */
  public function lintScss(array $packages): CollectionBuilder {
    $managedDrupalExtensions = $this->getManagedDrupalExtensions();
    $cb = $this->collectionBuilder();
    foreach ($packages as $packageName) {
      $packagePath = $managedDrupalExtensions[$packageName];
      $cb->addCode(function (RoboStateData $data) use ($packagePath): int {
        $this->getLogger()->warning('SCSS lint not implemented');

        return 0;
      });
    }

    return $cb;
  }

}
