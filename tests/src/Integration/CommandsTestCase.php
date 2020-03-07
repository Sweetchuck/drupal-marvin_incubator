<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin_incubator\Integration;

use Drush\TestTraits\DrushTestTrait;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Webmozart\PathUtil\Path;
use weitzman\DrupalTestTraits\ExistingSiteBase;

class CommandsTestCase extends ExistingSiteBase {

  use DrushTestTrait;

  /**
   * @var string
   */
  protected $fixturesDir = 'tests/fixtures';

  /**
   * @var string
   */
  protected $projectName = 'project_01';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    foreach ($this->getExtensionDirs() as $extensionDir) {
      $this->initGitRepo($extensionDir);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown(): void {
    parent::tearDown();

    foreach ($this->getExtensionDirs() as $extensionDir) {
      $this->deleteGitRepo($extensionDir);
    }
  }

  public function casesExecuteDrushCommand(): array {
    return [];
  }

  /**
   * @dataProvider casesExecuteDrushCommand
   */
  public function testExecuteDrushCommand(array $expected, string $command, array $args = [], array $options = [], array $envVars = []) {
    $this->drush(
      $command,
      $args,
      $options,
      NULL,
      NULL,
      $expected['exitCode'] ?? 0,
      NULL,
      $envVars
    );

    if (array_key_exists('stdError', $expected)) {
      static::assertText($expected['stdError'], $this->getErrorOutput(), 'stdError');
    }

    if (array_key_exists('stdOutput', $expected)) {
      static::assertText($expected['stdOutput'], $this->getOutput(), 'stdOutput');
    }
  }

  protected function getExtensionDirs(): array {
    $baseDir = $this->getMarvinIncubatorRootDir();

    return [
      'drupal/dummy_m1' => "$baseDir/{$this->fixturesDir}/extensions/dummy_m1",
      'drupal/dummy_m2' => "$baseDir/{$this->fixturesDir}/extensions/dummy_m2",
    ];
  }

  protected function initGitRepo(string $dir) {
    $this->deleteGitRepo($dir);

    $cmdPattern = [
      // @todo Without local config.
      'git init',
      '&&',
      'git checkout -b %s',
      '&&',
      'git add .',
      '&&',
      'git commit -m %s',
    ];
    $cmdArgs = [
      escapeshellarg('8.x-1.x'),
      escapeshellarg('Initial commit'),
    ];
    $cmd = vsprintf(implode(' ', $cmdPattern), $cmdArgs);

    static::assertSame(
      0,
      (new Process($cmd, $dir))->run(),
      "Initializing Git repository in '$dir' directory"
    );

    return $this;
  }

  /**
   * @return $this
   */
  protected function deleteGitRepo(string $dir) {
    (new Filesystem())->remove("$dir/.git");

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function convertKeyValueToFlag($key, $value) {
    if ($value === NULL) {
      return "--$key";
    }

    $options = [];

    if (!is_iterable($value)) {
      $value = [$value];
    }

    foreach ($value as $item) {
      $options[] = sprintf('--%s=%s', $key, static::escapeshellarg($item));
    }

    return implode(' ', $options);
  }

  protected function getCommonCommandLineOptions() {
    return [
      'config' => [
        Path::join($this->getDrupalRoot(), '..', 'drush'),
      ],
    ];
  }

  protected function getCommonCommandLineEnvVars() {
    return [
      'HOME' => '/dev/null',
    ];
  }

  protected function getProjectRootDir(): string {
    return dirname($this->getDrupalRoot());
  }

  protected function getMarvinIncubatorRootDir(): string {
    return dirname(__DIR__, 3);
  }

  protected function getDrupalRoot(): string {
    return Path::join($this->getMarvinIncubatorRootDir(), "{$this->fixturesDir}/{$this->projectName}/docroot");
  }

  public static function assertText(array $rules, string $text, string $msgPrefix) {
    foreach ($rules as $assertType => $expectations) {
      foreach ($expectations as $message => $expected) {
        $fullMessage = "$msgPrefix $assertType - $message";
        switch ($assertType) {
          case 'same':
            static::assertSame($expected, $text, $fullMessage);
            break;

          case 'contains':
            static::assertContains($expected, $text, $fullMessage);
            break;
        }
      }
    }
  }

}
