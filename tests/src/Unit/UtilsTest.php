<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin_incubator\Unit;

use Drupal\marvin_incubator\Utils as MarvinIncubatorUtils;
use Drupal\marvin_incubator\Utils;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Path;

/**
 * @covers \Drupal\marvin_incubator\Utils
 */
class UtilsTest extends TestCase {

  /**
   * @phpstan-return array<string, mixed>
   */
  public function casesCollectManagedDrupalExtensions(): array {
    $vfsRoot = 'vfs://testCollectManagedDrupalExtensions';

    return [
      'empty' => [
        [],
        $vfsRoot,
        "$vfsRoot/dir/inside",
        [],
        [],
        [],
      ],
      'basic' => [
        [
          'v1/profile_01_outside_git' => [
            'name' => 'v1/profile_01_outside_git',
            'path' => "$vfsRoot/dir/outside/v1/profile_01",
          ],
          'v1/module_01_outside_git' => [
            'name' => 'v1/module_01_outside_git',
            'path' => "$vfsRoot/dir/outside/v1/module_01",
          ],
          'v1/theme_01_outside_git' => [
            'name' => 'v1/theme_01_outside_git',
            'path' => "$vfsRoot/dir/outside/v1/theme_01",
          ],
          'v1/drush_01_outside_git' => [
            'name' => 'v1/drush_01_outside_git',
            'path' => "$vfsRoot/dir/outside/v1/drush_01",
          ],
        ],
        "$vfsRoot/dir/inside",
        "$vfsRoot/dir/inside",
        [
          'packages' => [
            'v1/profile_01_outside_git' => [
              'type' => 'drupal-profile',
            ],
            'v1/module_01_outside_git' => [
              'type' => 'drupal-module',
            ],
            'v1/theme_01_outside_git' => [
              'type' => 'drupal-theme',
            ],
            'v1/drush_01_outside_git' => [
              'type' => 'drupal-drush',
            ],
            'v1/library_01_outside_git' => [
              'type' => 'library',
            ],
            'v1/module_02_inside_git' => [
              'type' => 'drupal-module',
            ],
            'v1/module_03_outside_zip' => [
              'type' => 'drupal-module',
            ],
          ],
        ],
        [
          'v1/profile_01_outside_git' => "$vfsRoot/dir/outside/v1/profile_01",
          'v1/module_01_outside_git' => "$vfsRoot/dir/outside/v1/module_01",
          'v1/theme_01_outside_git' => "$vfsRoot/dir/outside/v1/theme_01",
          'v1/drush_01_outside_git' => "$vfsRoot/dir/outside/v1/drush_01",
          'v1/library_01_outside_git' => "$vfsRoot/dir/outside/v1/library_01",
          'v1/module_02_inside_git' => "$vfsRoot/dir/inside/modules/module_02",
          'v1/module_03_outside_zip' => "$vfsRoot/dir/outside/modules/module_03",
        ],
        [
          'dir' => [
            'inside' => [
              'modules' => [
                'module_02' => [
                  '.git' => [],
                ],
              ],
            ],
            'outside' => [
              'v1' => [
                'profile_01' => [
                  '.git' => [],
                ],
                'module_01' => [
                  '.git' => [],
                ],
                'theme_01' => [
                  '.git' => [],
                ],
                'drush_01' => [
                  '.git' => [],
                ],
                'library_01' => [
                  '.git' => [],
                ],
                'module_03' => [],
              ],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * @phpstan-param array<string, mixed> $expected
   * @phpstan-param marvin-composer-lock $composerLock
   * @phpstan-param array<string, string> $packagePaths
   * @phpstan-param array<string, mixed> $vfsStructure
   *
   * @dataProvider casesCollectManagedDrupalExtensions
   */
  public function testCollectManagedDrupalExtensions(
    array $expected,
    string $projectRootDir,
    string $drupalRootDir,
    array $composerLock,
    array $packagePaths,
    array $vfsStructure
  ): void {
    vfsStream::setup(__FUNCTION__, NULL, $vfsStructure);

    $utils = new MarvinIncubatorUtils();

    static::assertEquals(
      $expected,
      $utils->collectManagedDrupalExtensions($projectRootDir, $drupalRootDir, $composerLock, $packagePaths)
    );
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public function casesGetSiteDirs(): array {
    return [
      'basic' => [
        [
          'vfs://testGetSiteDirs/docroot/sites/a.b.c',
          'vfs://testGetSiteDirs/docroot/sites/d.e.f',
        ],
        'docroot/sites',
        [
          'docroot' => [
            'sites' => [
              'default' => [
                'example.settings.php' => '<?php',
              ],
              'a.b.c' => [
                'settings.php' => '<?php',
              ],
              'd.e.f' => [
                'settings.php' => '<?php',
              ],
              'simpletest' => [
                'settings.php' => '<?php',
              ],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * @param string[] $expected
   * @param string $sitesDir
   * @param array<string, mixed> $vfsStructure
   *
   * @dataProvider casesGetSiteDirs
   */
  public function testGetSiteDirs(array $expected, string $sitesDir, array $vfsStructure): void {
    $vfs = vfsStream::setup(__FUNCTION__, NULL, $vfsStructure);
    $sitesDir = Path::join($vfs->url(), $sitesDir);

    static::assertTrue(is_dir($sitesDir));

    static::assertEquals($expected, Utils::getSiteDirs($sitesDir));
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public function casesGetSiteNames(): array {
    return [
      'empty' => [
        [],
        [],
      ],
      'basic' => [
        [
          'c',
          'd',
        ],
        [
          '/a/b/c',
          '/a/b/d.my',
          '/a/b/d.pg',
        ],
      ],
    ];
  }

  /**
   * @param string[] $expected
   * @param string[] $siteDirs
   *
   * @dataProvider casesGetSiteNames
   */
  public function testGetSiteNames(array $expected, array $siteDirs): void {
    static::assertSame($expected, Utils::getSiteNames($siteDirs));
  }

  /**
   * @phpstan-return array<string, mixed>
   */
  public function casesGetPhpUnitConfigFileName(): array {
    return [
      'basic' => [
        'a/phpunit.my0507.0702.xml',
        'a',
        ['id' => 'my0507'],
        ['version' => ['majorMinor' => '0702']],
      ],
    ];
  }

}
