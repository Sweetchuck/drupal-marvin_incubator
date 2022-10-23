<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_incubator;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandResult;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\marvin\Utils as MarvinUtils;
use Drupal\marvin_incubator\CommandsBaseTrait;
use Drupal\marvin_incubator\Utils as MarvinIncubatorUtils;
use Drush\Attributes as CLI;
use Drush\Boot\DrupalBootLevels;
use Drush\Commands\marvin\CommandsBase;
use League\Container\Container as LeagueContainer;
use League\Container\ContainerAwareInterface;
use Psr\Container\ContainerInterface;
use Robo\Collection\CollectionBuilder;
use Robo\State\Data as RoboState;

class ManagedDrupalExtensionCommands extends CommandsBase {

  use CommandsBaseTrait;

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
  #[CLI\Command(name: 'marvin:managed-drupal-extension:list')]
  #[CLI\Bootstrap(level: DrupalBootLevels::NONE)]
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

  /**
   * Add a new managed extension.
   *
   * @param string $path
   *   Relative path to the extension.
   */
  #[CLI\Command(name: 'marvin:managed-drupal-extension:add')]
  #[CLI\Argument(
    name: 'path',
    description: 'Relative path to the extension.',
  )]
  #[CLI\Bootstrap(level: DrupalBootLevels::NONE)]
  #[CLI\Usage(
    name: "drush marvin:managed-drupal-extension:add '../../drupal/foo-1.x'",
    description: 'Adds "drupal/foo:1.x-dev" to the project.',
  )]
  public function cmdMarvinManagedDrupalExtensionAddExecute(
    string $path,
  ): CollectionBuilder {
    return $this
      ->collectionBuilder()
      ->addCode(function (RoboState $state) use ($path): int {
        $extComposer = json_decode(MarvinUtils::fileGetContents("$path/composer.json"), TRUE);
        $extName = $extComposer['name'];

        $composer = json_decode(MarvinUtils::fileGetContents('composer.json'), TRUE);
        $composer['repositories'] = [
          $extName => [
            'type' => 'path',
            'url' => $path,
          ],
        ] + ($composer['repositories'] ?? []);

        $jsonEncodeFlags = \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES;
        file_put_contents(
          'composer.json',
          json_encode($composer, $jsonEncodeFlags),
        );

        $extGitBranch = $this->getGitBranch($path);
        $this->getLogger()->warning(sprintf(
          'composer require %s',
          escapeshellarg("$extName:$extGitBranch-dev"),
        ));

        return 0;
      });
  }

  protected function getGitBranch(string $path): ?string {
    $output = [];
    $exitCode = 0;
    $command = sprintf(
      'cd %s ; git branch --show-current',
      escapeshellarg($path),
    );
    exec($command, $output, $exitCode);
    if ($exitCode) {
      return NULL;
    }

    return trim((string) array_pop($output));
  }

}
