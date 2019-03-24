<?php

namespace Drupal\Tests\marvin_incubator\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\marvin_incubator\Helper\ProcessResult;
use Symfony\Component\Process\Process;
use Webmozart\PathUtil\Path;

class CommandsTestBase extends KernelTestBase {

  /**
   * @var string
   */
  protected static $projectRoot = '';

  protected static function getProjectRoot(): string {
    static::initProjectRoot();

    return static::$projectRoot;
  }

  protected static function initProjectRoot() {
    if (!static::$projectRoot) {
      static::$projectRoot = Path::canonicalize(__DIR__ . '/../../..');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected static function getDrupalRoot() {
    return static::getProjectRoot() . '/tests/fixtures/project_01/web';
  }

  protected static function getDrushExecutable(): string {
    return static::getProjectRoot() . '/bin/drush';
  }

  protected function runDrushCommand(string $command): ProcessResult {
    $sutDrupalRoot = static::getDrupalRoot();
    $sutRoot = Path::getDirectory($sutDrupalRoot);

    $process = new Process($this->getFinalDrushCommand($command), $sutRoot);
    $process->run();

    return ProcessResult::createFromProcess($process);
  }

  protected function getFinalDrushCommand(string $command): string {
    $commandPrefix = sprintf(
      '%s --drush-coverage=%s --config=%s',
      escapeshellcmd(static::getDrushExecutable()),
      escapeshellarg('/dev/null'),
      escapeshellarg('drush')
    );

    return $commandPrefix . ' ' . $command;
  }

}
