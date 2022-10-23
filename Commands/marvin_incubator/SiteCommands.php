<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_incubator;

use Consolidation\AnnotatedCommand\AnnotationData;
use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\CommandResult;
use Drupal\marvin\DatabaseVariantTrait;
use Drupal\marvin\PhpVariantTrait;
use Drupal\marvin\Utils as MarvinUtils;
use Drupal\marvin_incubator\Attributes as MarvinIncubatorCLI;
use Drupal\marvin_incubator\Robo\CollectSiteNamesTaskLoader;
use Drupal\marvin_incubator\Robo\SitesPhpGeneratorTaskLoader;
use Drupal\marvin_incubator\Robo\SiteTaskLoader;
use Drupal\marvin_incubator\SiteGenerateSitesPhpTrait;
use Drupal\marvin_incubator\Utils as MarvinIncubatorUtils;
use Drush\Attributes as CLI;
use Drush\Boot\DrupalBootLevels;
use Drush\Commands\marvin\CommandsBase;
use Robo\Collection\CollectionBuilder;
use Robo\State\Data as RoboState;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Yaml\Yaml;

class SiteCommands extends CommandsBase {

  use DatabaseVariantTrait;
  use PhpVariantTrait;
  use SiteTaskLoader;
  use CollectSiteNamesTaskLoader;
  use SitesPhpGeneratorTaskLoader;
  use SiteGenerateSitesPhpTrait;

  protected Filesystem $fs;

  protected array $protectedSiteNames = [
    'list' => ['simpletest'],
    'create' => ['default', 'simpletest'],
    'delete' => ['default'],
  ];

  public function __construct() {
    parent::__construct();

    $this->fs = new Filesystem();
  }

  protected function getCustomEventNamePrefix(): string {
    return parent::getCustomEventNamePrefix() . ':site';
  }

  /**
   * Lists all sites.
   *
   * @param array $options
   *   List of available CLI options.
   *
   * @option string $format
   *   Default: yaml.
   */
  #[CLI\Command(name: 'marvin:site:list')]
  #[CLI\Bootstrap(level: DrupalBootLevels::ROOT)]
  public function cmdListExecute(
    array $options = [
      'format' => 'yaml',
    ],
  ): CommandResult {
    return CommandResult::data(MarvinIncubatorUtils::getSiteDirs('sites'));
  }

  /**
   * Shows information about one specific site.
   *
   * @command marvin:site:info
   *
   * @bootstrap root
   */
  #[CLI\Command(name: 'marvin:site:info')]
  #[CLI\Bootstrap(level: DrupalBootLevels::ROOT)]
  public function cmdInfoExecute(): CommandResult {
    // @todo Implement.
    return CommandResult::dataWithExitCode(
      'Not implemented yet',
      1,
    );
  }

  /**
   * @param \Symfony\Component\Console\Input\InputInterface $input
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   * @param \Consolidation\AnnotatedCommand\AnnotationData<string, mixed> $annotationData
   *
   * @hook interact marvin:site:create
   *
   * @link https://github.com/consolidation/annotated-command#interact-hook
   */
  public function cmdMarvinSiteCreateInteract(
    InputInterface $input,
    OutputInterface $output,
    AnnotationData $annotationData,
  ): void {
    $databaseId = $input->getArgument('databaseId');
    if (empty($databaseId)) {
      $io = new SymfonyStyle($input, $output);
      $databaseVariants = $this->getConfigDatabaseVariants();
      $databaseId = $io->choice(
        'Database ID',
        array_combine(array_keys($databaseVariants), array_keys($databaseVariants)),
      );
      $input->setArgument('databaseId', $databaseId);
    }
  }

  /**
   * @hook validate marvin:site:create
   */
  public function cmdCreateValidate(CommandData $commandData): void {
    $siteName = $commandData->input()->getArgument('siteName');
    if (in_array($siteName, $this->protectedSiteNames['create'])) {
      throw new \Exception("Site name '$siteName' is protected", 1);
    }
  }

  /**
   * Creates a new site under the DRUPAL_ROOT/sites/.
   *
   * @param string $siteName
   *   Site name. For example: "sandbox01".
   * @param string $databaseId
   *   Example: my0800, pg15, sqlite.
   */
  #[CLI\Command(name: 'marvin:site:create')]
  #[CLI\Bootstrap(level: DrupalBootLevels::NONE)]
  #[CLI\Argument(
    name: 'siteName',
    description: 'Machine name of the new site.',
  )]
  #[CLI\Argument(
    name: 'databaseId',
    description: 'Database ID.',
  )]
  #[MarvinIncubatorCLI\ValidateRegexp(
    locators: [
      'argument:siteName',
    ],
    pattern: 'machineNameStrict',
  )]
  #[MarvinIncubatorCLI\ValidateDatabaseId(
    locators: [
      'argument:databaseId',
    ],
  )]
  public function cmdCreateExecute(
    string $siteName,
    string $databaseId,
  ): CollectionBuilder {
    return $this->delegate('create', $siteName, $databaseId);
  }

  /**
   * @hook on-event marvin:site:create
   *
   * @link https://github.com/consolidation/annotated-command#on-event-hook
   */
  public function onEventMarvinSiteCreate(
    InputInterface $input,
    OutputInterface $output,
    string $site,
    string $databaseId,
  ): array {
    $phpId = '0802';

    $tasks = $this->getTaskDefsSiteCreateInit($site, $databaseId, $phpId);
    $tasks += $this->getTaskDefsSiteCreateValidate();
    $tasks += $this->getTaskDefsSiteCreateDirectories();
    $tasks += $this->getTaskDefsSiteCreateHashSalt();
    $tasks += $this->getTaskDefsSiteCreateSettingsPhp();
    $tasks += $this->getTaskDefsSiteCreateDrush();
    $tasks += $this->getTaskDefsSiteCreateInstall();
    $tasks += $this->getTaskDefsSiteCreateFavicon();

    return $tasks;
  }

  protected function getTaskDefsSiteCreateInit(
    string $site,
    string $databaseId,
    string $phpId,
  ): array {
    return [
      'marvin_incubator.init' => [
        'weight' => -999,
        'task' => function (RoboState $state) use ($site, $databaseId, $phpId): int {
          $composerInfo = $this->getComposerInfo();

          $state['site'] = $site;
          $state['databaseId'] = $databaseId;
          $state['phpId'] = $phpId;
          $state['database'] = $this->getConfigDatabaseVariants()[$databaseId];
          $state['projectVendor'] = $composerInfo->packageVendor;
          foreach (MarvinUtils::stringVariants($state['projectVendor'], 'projectVendor') as $key => $value) {
            $state[$key] = $value;
          }

          $state['projectName'] = $composerInfo->packageName;
          foreach (MarvinUtils::stringVariants($state['projectName'], 'projectName') as $key => $value) {
            $state[$key] = $value;
          }

          $state['drupalRootDir'] = $composerInfo->getDrupalRootDir();
          $state['primaryColor'] = '#' . $this->rgb2hex($this->randomRgbByBrightness(22, 46));

          return 0;
        },
      ],
    ];
  }

  protected function getTaskDefsSiteCreateValidate(): array {
    return [
      'marvin_incubator.validate' => [
        'weight' => -900,
        'task' => function (RoboState $state): int {
          $replacements = [
            '{{ siteName }}' => $state['site'],
            '{{ dbId }}' => $state['databaseId'],
          ];

          $siteDir = strtr(
            $this->getSiteDirPattern(),
            $replacements,
          );

          $dir = Path::join(
            $state['drupalRootDir'],
            'sites',
            $siteDir,
          );

          if ($this->fs->exists($dir)) {
            $this->getLogger()->error(
              'directory {dir} already exists',
              [
                'dir' => $dir,
              ],
            );

            return 1;
          }

          return 0;
        },
      ],
    ];
  }

  protected function getTaskDefsSiteCreateDirectories(): array {
    return [
      'marvin_incubator.directories.create' => [
        'weight' => 10,
        'task' => function (RoboState $state): int {
          $docroot = $state['drupalRootDir'];
          $outerSitesDir = 'sites';
          $siteFull = "{$state['site']}.{$state['databaseId']}";
          $result = $this
            ->taskFilesystemStack()
            ->mkdir([
              "$docroot/sites/$siteFull/files",
              "$outerSitesDir/$siteFull/backup",
              "$outerSitesDir/$siteFull/config/prod",
              "$outerSitesDir/$siteFull/php_storage/twig",
              "$outerSitesDir/$siteFull/private",
              "$outerSitesDir/$siteFull/temporary",
            ])
            ->run();

          if ($result->wasSuccessful()) {
            return 0;
          }

          $this->getLogger()->error($result->getMessage());

          return 1;
        },
      ],
    ];
  }

  protected function getTaskDefsSiteCreateHashSalt(): array {
    return [
      'marvin_incubator.hash_salt_txt.create' => [
        'weight' => 20,
        'task' => function (RoboState $state): int {
          $outerSitesDir = 'sites';
          $siteFull = "{$state['site']}.{$state['databaseId']}";

          $dst = "$outerSitesDir/$siteFull/hash_salt.txt";
          if ($this->fs->exists($dst)) {
            // @todo Log entry - dst already exists.
            return 0;
          }

          $result = $this
            ->taskWriteToFile($dst)
            ->text('todo-generate-a-hash')
            ->run();

          if ($result->wasSuccessful()) {
            return 0;
          }

          $this->getLogger()->error($result->getMessage());

          return 1;
        },
      ],
    ];
  }

  protected function getTaskDefsSiteCreateSettingsPhp(): array {
    return [
      'marvin_incubator.settings_php.create' => [
        'weight' => 30,
        'task' => function (RoboState $state): int {
          $docroot = $state['drupalRootDir'];
          $siteFull = "{$state['site']}.{$state['databaseId']}";

          $src = "$docroot/sites/settings.example.php";
          if (!$this->fs->exists($src)) {
            // @todo Log entry - src not exists.
            return 0;
          }

          $dst = "$docroot/sites/$siteFull/settings.php";
          if ($this->fs->exists($dst)) {
            // @todo Log entry - dst already exists.
            return 0;
          }

          $result = $this
            ->taskFilesystemStack()
            ->copy($src, $dst)
            ->run();

          return $result->wasSuccessful() ? 0 : 1;
        },
      ],
      'marvin_incubator.settings_php.primary_color' => [
        'weight' => 31,
        'task' => function (RoboState $state): int {
          $docroot = $state['drupalRootDir'];
          $siteFull = "{$state['site']}.{$state['databaseId']}";
          $filePath = "$docroot/sites/$siteFull/settings.php";
          $fileContent = file_get_contents($filePath);

          $prefix = "\$config['olivero.settings']['base_primary_color']";
          $fileContent = preg_replace(
            '/^' . preg_quote($prefix) . " = '#000000';$/um",
            "$prefix = '{$state['primaryColor']}';",
            $fileContent,
          );

          $this->fs->dumpFile($filePath, $fileContent);

          return 0;
        },
      ],
    ];
  }

  protected function getTaskDefsSiteCreateDrush(): array {
    return [
      'marvin_incubator.drush_site.create' => [
        'weight' => 40,
        'task' => function (RoboState $state): int {
          $dst = "drush/sites/{$state['site']}.site.yml";
          $aliasDefinitions = $this->fs->exists($dst) ?
            Yaml::parseFile($dst)
            : [];

          $phpIds = [
            '0802',
          ];

          $composerInfo = $this->getComposerInfo();

          $replacements = [
            '{{ dbId }}' => $state['databaseId'],
            '{{ siteName }}' => $state['site'],
            '{{ projectName }}' => $composerInfo->packageName,
            '{{ projectVendor }}' => $composerInfo->packageVendor,
          ];
          $uriPattern = $this->getConfig()->get('marvin.urlPattern');
          $siteNamePattern = '{{ siteName }} {{ dbId }}';
          $siteDirPattern = $this->getSiteDirPattern();

          foreach ($phpIds as $phpId) {
            $replacements['{{ phpId }}'] = $phpId;

            $aliasId = "{$state['databaseId']}-$phpId";
            $aliasDefinitions += [$aliasId => []];

            $aliasDefinitions[$aliasId] = array_replace_recursive(
              [
                'root' => '${drush.vendor-dir}/../docroot',
                'uri' => strtr($uriPattern, $replacements),
                'command' => [
                  'site' => [
                    'install' => [
                      'options' => [
                        'site-name' => strtr($siteNamePattern, $replacements),
                        'sites-subdir' => strtr($siteDirPattern, $replacements),
                      ],
                    ],
                  ],
                ],
              ],
              $aliasDefinitions[$aliasId],
            );
          }

          $this->fs->mkdir(Path::getDirectory($dst));
          $this->fs->dumpFile($dst, Yaml::dump($aliasDefinitions, 99, 2));

          return 0;
        },
      ],
    ];
  }

  protected function getTaskDefsSiteCreateInstall(): array {
    return [
      'marvin_incubator.install' => [
        'weight' => 50,
        'task' => function (RoboState $state): int {
          $drushAlias = sprintf(
            '@%s.%s-%s',
            $state['site'],
            $state['databaseId'],
            $state['phpId'],
          );
          // @todo Get vendor-dir from $this->>composerInfo.
          $drush = "vendor/bin/drush --config='drush' $drushAlias";

          $modulesToEnable = [
            'devel',
            'module_filter',
            'environment_indicator_simple',
            'admin_toolbar',
            'admin_toolbar_tools',
          ];
          $result = $this
            ->taskExecStack()
            ->exec("$drush --yes site:install")
            ->exec("$drush --yes entity:delete shortcut")
            ->exec("$drush --yes pm:uninstall shortcut")
            ->exec("$drush --yes pm:uninstall update")
            ->exec("$drush --yes pm:enable " . implode(' ', $modulesToEnable))
            ->exec("$drush --yes config:export")
            ->run();

          if (!$result->wasSuccessful()) {
            $this->getLogger()->error($result->getMessage());

            return 2;
          }

          $siteFull = "{$state['site']}.{$state['databaseId']}";
          $filePath = "sites/$siteFull/config/prod/core.extension.yml";
          $fileData = Yaml::parseFile($filePath);
          unset($fileData['module']['standard']);
          $fileData['module']['minimal'] = 1000;
          $fileData['profile'] = 'minimal';
          $this->fs->dumpFile($filePath, Yaml::dump($fileData, 99, 2));

          $result = $this
            ->taskExecStack()
            ->exec("$drush --yes site:install minimal --existing-config")
            ->run();

          if (!$result->wasSuccessful()) {
            $this->getLogger()->error($result->getMessage());

            return 3;
          }

          return 0;
        },
      ],
    ];
  }

  protected function getTaskDefsSiteCreateFavicon(): array {
    return [
      'marvin_incubator.favicon.create' => [
        'weight' => 60,
        'task' => function (RoboState $state): int {
          $docroot = $state['drupalRootDir'];
          $siteFull = "{$state['site']}.{$state['databaseId']}";
          $src = "$docroot/sites/favicon.svg";
          if (!$this->fs->exists($src)) {
            // @todo Log entry.
            return 0;
          }

          $dst = "$docroot/sites/$siteFull/files/favicon.svg";
          if ($this->fs->exists($dst)) {
            // @todo Log entry.
            return 0;
          }

          $content = strtr(
            file_get_contents($src),
            [
              '#009cde' => $state['primaryColor'],
            ],
          );

          $this->fs->dumpFile($dst, $content);

          return 0;
        },
      ],
      'marvin_incubator.favicon.theme_settings' => [
        'weight' => 61,
        'task' => function (RoboState $state): int {
          $siteFull = "{$state['site']}.{$state['databaseId']}";
          $drushAlias = sprintf(
            '@%s.%s-%s',
            $state['site'],
            $state['databaseId'],
            $state['phpId'],
          );
          $drush = "vendor/bin/drush --config='drush' $drushAlias";

          foreach (['olivero', 'claro'] as $theme) {
            $cmdPattern = "$drush --yes config:set --input-format='yaml' $theme.settings favicon %s";
            $value = [
              'mimetype' => 'image/svg+xml',
              'path' => "sites/$siteFull/files/favicon.svg",
              'use_default' => FALSE,
            ];
            $this
              ->taskExecStack()
              ->exec(sprintf($cmdPattern, escapeshellarg(Yaml::dump($value, 0))))
              ->run();
          }

          return 0;
        },
      ],
    ];
  }

  /**
   * @hook validate marvin:site:delete
   */
  public function cmdDeleteValidate(CommandData $commandData): void {
    $siteName = $commandData->input()->getArgument('siteName');
    if (in_array($siteName, $this->protectedSiteNames['delete'])) {
      throw new \Exception("Site name '$siteName' is protected", 1);
    }
  }

  /**
   * Deletes one specific site.
   *
   * @param string $siteName
   *   Site machine-name.
   *
   * @todo Confirmation question.
   * @todo Delete only one specific database variant.
   */
  #[CLI\Command(name: 'marvin:site:delete')]
  #[CLI\Bootstrap(level: DrupalBootLevels::ROOT)]
  #[CLI\Argument(
    name: 'siteName',
    description: 'Machine-name of the site to delete.',
  )]
  public function cmdDeleteExecute(string $siteName): CollectionBuilder {
    // @todo Delete other resources as well. Database, Solr core.
    return $this
      ->collectionBuilder()
      ->addTask(
        $this
          ->taskMarvinSiteDelete()
          ->setDrupalRoot('.')
          ->setSiteName($siteName)
      );
    // Currently sites.php has a dynamic content. No need to delete.
    // ->addTask($this->getTaskMarvinGenerateSitesPhp($this->getConfigDatabaseVariants())).
  }

  protected function getSiteDirPattern(): string {
    return '{{ siteName }}.{{ dbId }}';
  }

  /**
   * @todo Move this method into some utility class.
   */
  protected function rgb2hex(array $color): string {
    return sprintf(
      '%02x%02x%02x',
      $color['r'],
      $color['g'],
      $color['b'],
    );
  }

  /**
   * @todo Move this method into some utility class.
   */
  protected function randomRgbByBrightness(int $brightnessMin, int $brightnessMax): array {
    assert($brightnessMin >= 0 && $brightnessMax <= 100);

    return $this->hsl2rgb(
      rand(0, 359),
      rand(0, 100),
      rand($brightnessMin, $brightnessMax),
    );
  }

  /**
   * @todo Move this method into some utility class.
   */
  protected function hsl2rgb(int $h, int $s, int $l): array {
    assert(0 <= $h && $h <= 359);
    assert(0 <= $s && $s <= 100);
    assert(0 <= $l && $l <= 100);

    $s /= 100;
    $l /= 100;

    if ($s === 0) {
      $gray = floor(($l / 100) * 255);

      return [
        'r' => $gray,
        'g' => $gray,
        'b' => $gray,
      ];
    }

    $hue = (float) $h / 360;

    $v2 = ($l < 0.5) ? ($l * (1 + $s)) : (($l + $s) - ($l * $s));
    $v1 = 2 * $l - $v2;

    return [
      'r' => floor(255 * $this->hue2rgb($v1, $v2, $hue + (1.0 / 3))),
      'g' => floor(255 * $this->hue2rgb($v1, $v2, $hue)),
      'b' => floor(255 * $this->hue2rgb($v1, $v2, $hue - (1.0 / 3))),
    ];
  }

  /**
   * @todo Move this method into some utility class.
   */
  protected function hue2rgb($v1, $v2, $vH): int|float {
    if ($vH < 0) {
      $vH += 1;
    }

    if ($vH > 1) {
      $vH -= 1;
    }

    if ((6 * $vH) < 1) {
      return ($v1 + ($v2 - $v1) * 6 * $vH);
    }

    if ((2 * $vH) < 1) {
      return $v2;
    }

    if ((3 * $vH) < 2) {
      return ($v1 + ($v2 - $v1) * ((2.0 / 3) - $vH) * 6);
    }

    return $v1;
  }

}
