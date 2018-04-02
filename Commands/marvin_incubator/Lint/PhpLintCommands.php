<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_incubator\Lint;

use Drupal\marvin_incubator\CommandsBaseTrait;
use Robo\Collection\CollectionBuilder;
use Robo\Contract\TaskInterface;
use Sweetchuck\Robo\Git\GitTaskLoader;
use Sweetchuck\Robo\PhpLint\PhpLintTaskLoader;
use Sweetchuck\Utils\ArrayFilterInterface;
use Sweetchuck\Utils\Filter\ArrayFilterEnabled;
use Symfony\Component\Console\Input\InputInterface;

class PhpLintCommands extends Commands {

  use CommandsBaseTrait;
  use GitTaskLoader;
  use PhpLintTaskLoader;

  /**
   * @hook on-event marvin:git-hook:pre-commit
   */
  public function onEventMarvinGitHookPreCommit(InputInterface $input): array {
    $package = $this->normalizeManagedDrupalExtensionName($input->getArgument('packagePath'));

    return [
      'marvin.lint.phpcs' => [
        'weight' => -210,
        'task' => $this->lintPhp([$package['name']]),
      ],
    ];
  }

  /**
   * @hook on-event marvin:lint
   */
  public function onEventMarvinLint(InputInterface $input): array {
    return [
      'marvin.lint.phpcs' => [
        'weight' => -210,
        'task' => $this->lintPhp($input->getArgument('packages')),
      ],
    ];
  }

  /**
   * Runs PHP lint.
   *
   * @command marvin:lint:php
   * @bootstrap none
   *
   * @marvinArgPackages packages
   */
  public function lintPhp(array $packages): CollectionBuilder {
    $managedDrupalExtensions = $this->getManagedDrupalExtensions();
    $cb = $this->collectionBuilder();

    $phpVariants = $this->getPhpVariants();
    foreach ($packages as $packageName) {
      $packagePath = $managedDrupalExtensions[$packageName];
      $cb->addTask($this->getTaskLintPhpExtension($packagePath, $phpVariants));
    }

    return $cb;
  }

  protected function getTaskLintPhpExtension(string $packagePath, array $phpVariants): TaskInterface {
    $fileListerCommand = $this->getFileListerCommand($packagePath);

    $cb = $this->collectionBuilder();
    foreach ($phpVariants as $phpVariant) {
      $phpExecutable = $phpVariant['binDir'] ?: '/usr/bin';
      $phpExecutable .= '/' . ($phpVariant['phpExecutable'] ?: 'php');

      $cb->addTask(
        $this
          ->taskPhpLintFiles()
          ->setWorkingDirectory($packagePath)
          ->setFileListerCommand($fileListerCommand)
          ->setPhpExecutable($phpExecutable)
      );
    }

    return $cb;
  }

  protected function getFileListerCommand(string $packagePath): string {
    $fileListerCommand = sprintf('cd %s && git ls-files -z --', escapeshellarg($packagePath));
    foreach ($this->getPhpFileNamePatterns() as $fileNamePattern) {
      $fileListerCommand .= ' ' . escapeshellarg($fileNamePattern);
    }

    return $fileListerCommand;
  }

  protected function getPhpVariants(): array {
    $phpVariants = (array) $this->getConfig()->get('command.marvin.settings.php.variant');

    return array_filter($phpVariants, $this->getPhpVariantFilter());
  }

  protected function getPhpVariantFilter(): ArrayFilterInterface {
    return new ArrayFilterEnabled();
  }

  /**
   * @return string[]
   */
  protected function getPhpExtensions(): array {
    $extensions = (array) $this->getConfig()->get('command.marvin.settings.php.extension');

    return array_keys($extensions, TRUE, FALSE);
  }

  /**
   * @return string[]
   */
  protected function getPhpFileNamePatterns(): array {
    $patterns = [];

    foreach ($this->getPhpExtensions() as $extension) {
      $patterns[] = "*.$extension";
    }

    return $patterns;
  }

}
