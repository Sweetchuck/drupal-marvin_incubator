<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin\Unit_incubator\Robo\Task;

use Drupal\marvin_incubator\Robo\Task\SitesPhpGeneratorTask;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Robo\Robo;

class SitesPhpGeneratorTaskTest extends TestCase {

  public function casesRunSuccessString(): iterable {
    return [
      'empty' => [
        [
          'sitesPhp' => implode(PHP_EOL, [
            '<?php',
            '',
            '$sites = [];',
            '',
          ]),
        ],
        [
          'outputDestination' => 'a/b/sites.php',
        ],
      ],
      'db1 sb1' => [
        [
          'sitesPhp' => implode(PHP_EOL, [
            '<?php',
            '',
            '$sites = [',
            "  'd.db1.e.sn1.f' => 'a.sn1.b.db1.c',",
            '];',
            '',
          ]),
        ],
        [
          'outputDestination' => 'a/b/sites.php',
          'siteNames' => ['sn1'],
          'databaseVariantIds' => ['db1'],
          'siteDirPattern' => 'a.{{ siteName }}.b.{{ dbId }}.c',
          'urlPattern' => 'd.{{ dbId }}.e.{{ siteName }}.f',
        ],
      ],
    ];
  }

  /**
   * @dataProvider casesRunSuccessString
   */
  public function testRunSuccessString(array $expected, array $options): void {
    $vfs = vfsStream::setup(__FUNCTION__);
    $vfsUrl = $vfs->url();

    $options['outputDestination'] = "$vfsUrl/{$options['outputDestination']}";
    $container = Robo::createDefaultContainer();

    Robo::setContainer($container);

    $task = new SitesPhpGeneratorTask();
    $task->setLogger($container->get('logger'));
    $task->setOptions($options);
    $result = $task->run();

    $this->assertSame($expected['sitesPhp'], $result['sitesPhp']);
    $this->assertSame($expected['sitesPhp'], file_get_contents($options['outputDestination']));
  }

}
