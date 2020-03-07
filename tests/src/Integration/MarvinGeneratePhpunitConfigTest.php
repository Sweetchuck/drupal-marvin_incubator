<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin_incubator\Integration;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Webmozart\PathUtil\Path;

/**
 * @group marvin_incubator
 * @group drush-command
 */
class MarvinGeneratePhpunitConfigTest extends CommandsTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setUpDeletePhpunitConfigFiles();
  }

  protected function setUpDeletePhpunitConfigFiles() {
    $files = (new Finder())
      ->in($this->getProjectRootDir())
      ->depth(0)
      ->files()
      ->name('phpunit.*.xml');

    (new Filesystem())->remove($files);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function casesExecuteDrushCommand(): array {
    $baseDir = $this->getMarvinIncubatorRootDir();
    $options = $this->getCommonCommandLineOptions();

    $phpVersionName = PHP_VERSION_ID . '-' . (ZEND_THREAD_SAFE ? 'zts' : 'nts');
    $majorMinor = sprintf(
      '%02d%02d',
      mb_substr((string) PHP_VERSION_ID, 0, -4),
      mb_substr((string) PHP_VERSION_ID, -4, -2)
    );

    return [
      'basic' => [
        [
          'exitCode' => 0,
          'stdOutput' => [
            'same' => [
              'stdOutput same' => '',
            ],
          ],
          'stdError' => [
            'same' => [
              'stdError same' => implode(PHP_EOL, [
                "[Composer - Package paths] cd '$baseDir' && composer show -P",
                ' [Marvin - Managed Drupal extension list] ',
                ' [Marvin - Generate PHPUnit XML]',
              ]),
            ],
          ],
          'fileName' => Path::join($this->getProjectRootDir(), "phpunit.sqlite.$majorMinor.xml"),
        ],
        'marvin:generate:phpunit-config',
        [],
        ['uri' => "http://$phpVersionName.dev.sqlite.d8.localhost"] + $options,
      ],
    ];
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
      $this->getProjectRootDir(),
      $expected['exitCode'] ?? 0,
      NULL,
      $envVars
    );

    // @todo Assert file content.
    static::assertFileExists($expected['fileName']);

    if (array_key_exists('stdError', $expected)) {
      static::assertText($expected['stdError'], $this->getErrorOutput(), 'stdError');
    }

    if (array_key_exists('stdOutput', $expected)) {
      static::assertText($expected['stdOutput'], $this->getOutput(), 'stdOutput');
    }
  }

  protected function getCommonCommandLineOptions() {
    return [
      'config' => [
        Path::join($this->getProjectRootDir(), 'drush'),
      ],
    ];
  }

}
