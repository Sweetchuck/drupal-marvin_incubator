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
    $args = [];
    $options = $this->getCommonCommandLineOptions();
    $envVars = $this->getCommonCommandLineEnvVars();

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
        $args,
        [
          'uri' => "http://$phpVersionName.dev.sqlite.d9.localhost",
        ] + $options,
        $envVars,
      ],
    ];
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
    parent::testExecuteDrushCommand(
      $expected,
      $command,
      $args,
      $options,
      $envVars,
    );

    // @todo Assert file content.
    static::assertFileExists($expected['fileName']);
  }

}
