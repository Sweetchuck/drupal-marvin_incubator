<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin_incubator\Integration;

use Drush\TestTraits\DrushTestTrait;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Process;
use weitzman\DrupalTestTraits\ExistingSiteBase;

class CommandsTestCase extends ExistingSiteBase {

  use DrushTestTrait;

  protected static string $fixturesDir = 'tests/fixtures';

  protected static string $projectName = 'project_01';

  protected static string $defaultDttBaseUrl = 'http://127.0.0.1:8888';

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
  public function testExecuteDrushCommand(
    array $expected,
    string $command,
    array $args = [],
    array $options = [],
    array $envVars = []
  ) {
    $this->drush(
      $command,
      $args,
      $options,
      NULL,
      $this->getProjectRootDir(),
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
    $fixturesDir = static::$fixturesDir;

    return [
      'drupal/dummy_m1' => "$baseDir/{$fixturesDir}/repository/drupal/dummy_m1",
      'drupal/dummy_m2' => "$baseDir/{$fixturesDir}/repository/drupal/dummy_m2",
    ];
  }

  protected function initGitRepo(string $dir) {
    $this->deleteGitRepo($dir);

    $shell = getenv('SHELL');

    $command = [
      $shell,
      '-c',
      // @todo Without local config.
      sprintf(
        'git init && git checkout -b %s && git add . && git commit -m %s',
        escapeshellarg('9.x-1.x'),
        escapeshellarg('Initial commit'),
      ),
    ];

    static::assertSame(
      0,
      (new Process($command, $dir))->run(),
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
      'uri' => $this->getProjectUri(),
      'root' => 'docroot',
      'config' => [
        'drush',
      ],
    ];
  }

  protected function getCommonCommandLineEnvVars() {
    return [
      'HOME' => '/dev/null',
      'COLUMNS' => 120,
      'COMPOSER' => './composer.json',
    ];
  }

  protected function getProjectRootDir(): string {
    return Path::join(
      $this->getMarvinIncubatorRootDir(),
      static::$fixturesDir,
      'repository',
      'd10',
      static::$projectName,
    );
  }

  protected function getMarvinIncubatorRootDir(): string {
    return dirname(__DIR__, 3);
  }

  protected function getProjectUri(): string {
    return getenv('DTT_BASE_URL') ?: static::$defaultDttBaseUrl;
  }

  protected function getProjectDocroot(): string {
    return Path::join(
      $this->getProjectRootDir(),
      'docroot',
    );
  }

  public static function assertText(array $rules, string $text, string $msgPrefix) {
    foreach ($rules as $assertType => $expectations) {
      foreach ($expectations as $message => $expected) {
        $fullMessage = "$msgPrefix $assertType - $message";
        switch ($assertType) {
          case 'same':
            static::assertSame($expected, $text, $fullMessage);
            break;

          case 'stringContainsString':
            static::assertStringContainsString($expected, $text, $fullMessage);
            break;

          default:
            throw new \InvalidArgumentException("\$assterType not exists: $assertType");
        }
      }
    }
  }

}
