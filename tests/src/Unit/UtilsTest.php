<?php

namespace Drupal\Tests\marvin_incubator\Unit;

use Drupal\marvin_incubator\Utils as MarvinIncubatorUtils;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\marvin_incubator\Utils
 */
class UtilsTest extends TestCase {

  public function casesCollectManagedDrupalExtensions(): array {
    $vfsRoot = 'vfs://testCollectManagedDrupalExtensions';

    return [
      'empty' => [
        [],
        "$vfsRoot/dir/inside",
        [],
        [],
        [],
      ],
      'basic' => [
        [
          'v1/profile_01_outside_git' => "$vfsRoot/dir/outside/v1/profile_01",
          'v1/module_01_outside_git' => "$vfsRoot/dir/outside/v1/module_01",
          'v1/theme_01_outside_git' => "$vfsRoot/dir/outside/v1/theme_01",
          'v1/drush_01_outside_git' => "$vfsRoot/dir/outside/v1/drush_01",
        ],
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
   * @covers ::collectManagedDrupalExtensions
   *
   * @dataProvider casesCollectManagedDrupalExtensions
   */
  public function testCollectManagedDrupalExtensions(
    array $expected,
    string $drupalRootDir,
    array $composerLock,
    array $packagePaths,
    array $vfsStructure
  ): void {
    vfsStream::setup(__FUNCTION__, NULL, $vfsStructure);

    $utils = new MarvinIncubatorUtils();

    $this->assertEquals(
      $expected,
      $utils->collectManagedDrupalExtensions($drupalRootDir, $composerLock, $packagePaths)
    );
  }

}
