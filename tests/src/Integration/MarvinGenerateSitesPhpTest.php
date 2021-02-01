<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin_incubator\Integration;

use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

/**
 * @group marvin_incubator
 * @group drush-command
 */
class MarvinGenerateSitesPhpTest extends CommandsTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setUpDeleteSitesPhp();
  }

  protected function setUpDeleteSitesPhp() {
    (new Filesystem())->remove($this->getSitesPhpFileName());

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function casesExecuteDrushCommand(): array {
    $options = $this->getCommonCommandLineOptions();

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
                '[Marvin - Collect site names] ',
                ' [Marvin - Generate sites.php]',
              ]),
            ],
          ],
          'content' => implode(PHP_EOL, [
            '<?php',
            '',
            '$sites = [',
            "  'sqlite.default.d8.localhost' => 'default.sqlite',",
            '];',
            '',
          ]),
        ],
        'marvin:generate:sites-php',
        [],
        $options,
      ],
    ];
  }

  protected function getCommonCommandLineOptions() {
    return [
      'config' => [
        Path::join($this->getProjectRootDir(), 'drush'),
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

    static::assertStringEqualsFile(
      $this->getSitesPhpFileName(),
      $expected['content'],
      'sites.php content'
    );

    if (array_key_exists('stdError', $expected)) {
      static::assertText($expected['stdError'], $this->getErrorOutput(), 'stdError');
    }

    if (array_key_exists('stdOutput', $expected)) {
      static::assertText($expected['stdOutput'], $this->getOutput(), 'stdOutput');
    }
  }

  protected function getSitesPhpFileName(): string {
    return Path::join($this->getProjectDocroot(), 'sites', 'sites.php');
  }

}
