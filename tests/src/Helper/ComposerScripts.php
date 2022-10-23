<?php

declare(strict_types = 1);

namespace Drupal\Tests\marvin_incubator\Helper;

use Composer\Script\Event;
use Psr\Log\LoggerInterface;
use Sweetchuck\Utils\Filesystem as FilesystemUtils;
use Sweetchuck\Utils\Filter\ArrayFilterFileSystemExists;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

class ComposerScripts {

  /**
   * Composer event callback.
   */
  public static function onPostInstallCmd(Event $event): int {
    if (!$event->isDevMode()) {
      return 0;
    }

    $self = new static($event);

    try {
      $self
        ->preparePhpunitXml()
        ->prepareRepository()
        ->prepareProject();
    }
    catch (\Exception $e) {
      $event->getIO()->error($e->getMessage());

      return max($e->getCode(), 1);
    }

    return 0;
  }

  /**
   * Composer event callback.
   */
  public static function onPostUpdateCmd(Event $event): int {
    return static::onPostInstallCmd($event);
  }

  /**
   * Composer custom command.
   */
  public static function cmdPreparePhpunitXml(Event $event) {
    if (!$event->isDevMode()) {
      return 0;
    }

    $self = new static($event);
    try {
      $self->preparePhpunitXml();
    }
    catch (\Exception $e) {
      $event->getIO()->error($e->getMessage());

      return max($e->getCode(), 1);
    }

    return 0;
  }

  /**
   * Composer custom command.
   */
  public static function cmdPrepareRepository(Event $event): int {
    if (!$event->isDevMode()) {
      return 0;
    }

    $self = new static($event);
    try {
      $self->prepareRepository();
    }
    catch (\Exception $e) {
      $event->getIO()->error($e->getMessage());

      return max($e->getCode(), 1);
    }

    return 0;
  }

  /**
   * Composer custom command.
   */
  public static function cmdPrepareProject(Event $event): int {
    if (!$event->isDevMode()) {
      return 0;
    }

    $self = new static($event);
    try {
      $self->prepareProject();
    }
    catch (\Exception $e) {
      $event->getIO()->error($e->getMessage());

      return max($e->getCode(), 1);
    }

    return 0;
  }

  /**
   * Composer custom command.
   */
  public static function checkJunit(Event $event) {
    $io = $event->getIO();
    $dir = 'reports/machine/junit';

    $fileNames = $event->getArguments();
    if (!$fileNames) {
      $io->warning('at least one file name should be provided');

      return;
    }

    $numOfProblems = 0;
    $report = [];
    foreach ($fileNames as $fileName) {
      $filePath = "$dir/$fileName.xml";
      $doc = new \DOMDocument();
      $doc->loadXML(file_get_contents($filePath));

      $report[$filePath]['error'] = $doc->getElementsByTagName('error')->count();
      $report[$filePath]['failure'] = $doc->getElementsByTagName('failure')->count();

      $numOfProblems += array_sum($report[$filePath]);
    }

    if (!$numOfProblems) {
      return;
    }

    $io->writeError(var_export($report, TRUE));

    throw new \Exception(sprintf(
      'number of problems: %d',
      $numOfProblems,
    ));
  }

  protected Event $event;

  /**
   * @var callable
   */
  protected $processCallbackWrapper;

  protected string $projectRoot = 'tests/fixtures/repository/d10/project_01';

  protected Filesystem $fs;

  protected LoggerInterface $logger;

  /**
   * Current working directory.
   */
  protected string $cwd = '.';

  protected string $gitExecutable = 'git';

  protected string $binDir = './vendor/bin';

  protected int $jsonEncodeFlags = \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE;

  protected function __construct(
    Event $event,
    ?LoggerInterface $logger = NULL,
    ?Filesystem $fs = NULL,
    string $cwd = '.'
  ) {
    $this->cwd = $cwd ?: '.';
    $this->event = $event;
    $this->logger = $logger ?: $this->createLogger();
    $this->fs = $fs ?: $this->createFilesystem();
    $this->initProcessCallbackWrapper();
  }

  protected function createLogger(): LoggerInterface {
    $io = $this->event->getIO();
    $verbosity = OutputInterface::VERBOSITY_NORMAL;
    if ($io->isDebug()) {
      $verbosity = OutputInterface::VERBOSITY_DEBUG;
    }
    elseif ($io->isVeryVerbose()) {
      $verbosity = OutputInterface::VERBOSITY_VERY_VERBOSE;
    }
    elseif ($io->isVerbose()) {
      $verbosity = OutputInterface::VERBOSITY_VERBOSE;
    }

    $output = new ConsoleOutput($verbosity, $io->isDecorated());

    return new ConsoleLogger($output);
  }

  protected function createFilesystem(): Filesystem {
    return new Filesystem();
  }

  /**
   * @return $this
   */
  protected function initProcessCallbackWrapper() {
    if (!isset($this->processCallbackWrapper)) {
      $this->processCallbackWrapper = function (string $type, string $buffer) {
        $this->processCallback($type, $buffer);
      };
    }

    return $this;
  }

  /**
   * @return $this
   */
  protected function preparePhpunitXml() {
    $config = $this->event->getComposer()->getConfig();

    $phpunitExecutable = Path::join($config->get('bin-dir'), 'phpunit');
    if (!$this->fs->exists($phpunitExecutable)) {
      $this->logger->info('PHPUnit configuration file creation is skipped because phpunit/phpunit is not installed');

      return $this;
    }

    $dstFileName = Path::join($this->cwd, 'phpunit.xml');
    $srcFileName = Path::join($this->cwd, 'phpunit.xml.dist');
    $logArgs = [
      'srcFileName' => $srcFileName,
      'dstFileName' => $dstFileName,
    ];

    if ($this->fs->exists($dstFileName)) {
      $this->logger->info(
        'PHPUnit configuration file is already exists: {dstFileName}',
        $logArgs,
      );

      return $this;
    }

    $srcFileName = Path::join($this->cwd, 'phpunit.xml.dist');
    if (!$this->fs->exists($srcFileName)) {
      $this->logger->info(
        'PHPUnit configuration source file does not exists: {srcFileName}',
        $logArgs,
      );

      return $this;
    }

    $this->logger->info(
      'PHPUnit configuration {srcFileName} => {dstFileName}',
      $logArgs,
    );
    $basePattern = '<env name="%s" value="%s"/>';
    $oldPattern = "<!-- $basePattern -->";
    $replacementPairs = [];
    foreach ($this->getPhpunitEnvVars() as $envVarName => $envVarValue) {
      $placeholder = sprintf($oldPattern, $envVarName, '');
      $replacementPairs[$placeholder] = sprintf($basePattern, $envVarName, $this->escapeXmlAttribute($envVarValue));
    }

    $content = FilesystemUtils::fileGetContents($srcFileName);
    $this->fs->dumpFile($dstFileName, strtr($content, $replacementPairs));

    return $this;
  }

  /**
   * @return $this
   */
  protected function prepareRepository() {
    $this->logger->info('Prepare repository');

    $fixturesDir = 'tests/fixtures';
    $repositoryDir = "$fixturesDir/repository";

    $this->prepareRepositoryPackages();
    $this->prepareRepositorySelf();

    $dirs = (new Finder())
      ->in($repositoryDir)
      ->directories()
      ->depth(1);
    foreach ($dirs as $dir) {
      $this->initGitRepo($dir->getPathname());
    }

    return $this;
  }

  protected function prepareRepositoryPackages() {
    $fixturesDir = 'tests/fixtures';
    $packagesDir = "$fixturesDir/packages";
    $repositoryDir = "$fixturesDir/repository";

    // @todo Check that the $packagesDir exists.
    $dirs = (new Finder())
      ->in($packagesDir)
      ->directories()
      ->depth(1);

    $this->fs->mkdir([$repositoryDir]);
    foreach ($dirs as $dir) {
      $name = $dir->getRelativePathname();
      $this->logger->info("Prepare repository - $name");
      $this->fs->mkdir("$repositoryDir/$name");

      $files = (new Finder())
        ->in("$packagesDir/$name")
        ->depth('0');
      foreach ($files as $file) {
        $this->fs->symlink(
          "../../../packages/$name/" . $file->getRelativePathname(),
          "$repositoryDir/$name/" . $file->getRelativePathname(),
        );
      }

      $this->initGitRepo("$repositoryDir/$name");
    }
  }

  /**
   * @return $this
   */
  protected function prepareRepositorySelf() {
    $dstDir = $this->prepareRepositorySelfDestination();
    $this->logger->info('prepare package - {dir}', ['dir' => $dstDir]);

    $this->fs->remove([$dstDir]);
    $relative = implode(
      '/',
      array_fill(
        0,
        substr_count($dstDir, '/') + 1,
        '..'
      )
    );

    $filesToSymlink = $this->prepareRepositorySelfFilesToSymlink();
    $this->fs->mkdir($dstDir);
    foreach ($filesToSymlink as $fileToSymlink) {
      $this->fs->symlink("$relative/$fileToSymlink", "$dstDir/$fileToSymlink");
    }

    return $this;
  }

  protected function prepareRepositorySelfDestination(): string {
    $selfName = $this->event->getComposer()->getPackage()->getName();

    return "tests/fixtures/repository/$selfName";
  }

  /**
   * @return string[]
   */
  protected function prepareRepositorySelfFilesToSymlink(): array {
    $filesToSymLink = $this->prepareRepositorySelfFilesToSymlinkCustom()
      + $this->prepareRepositorySelfFilesToSymlinkAuto();

    $filesToSymLink = array_keys($filesToSymLink, TRUE, TRUE);

    $filter = new ArrayFilterFileSystemExists();
    $filter->setBaseDir($this->cwd);

    return array_filter($filesToSymLink, $filter);
  }

  /**
   * @return bool[]
   */
  protected function prepareRepositorySelfFilesToSymlinkAuto(): array {
    $name = $this->getComposerPackageName();

    $defaults = [
      'config' => TRUE,
      'Commands' => TRUE,
      'Generators' => TRUE,
      'src' => TRUE,
      'templates' => TRUE,
      'composer.json' => TRUE,
      'drush.services.yml' => TRUE,
      'drush9.services.yml' => TRUE,
      'drush10.services.yml' => TRUE,
    ];

    $files = (new Finder())
      ->in('.')
      ->depth(0)
      ->files()
      ->name("$name.*.yml")
      ->name("$name.module")
      ->name("$name.install")
      ->name("$name.profile")
      ->name("$name.theme");
    foreach ($files as $file) {
      $defaults[$file->getRelativePathname()] = TRUE;
    }

    return $defaults;
  }

  /**
   * @return bool[]
   */
  protected function prepareRepositorySelfFilesToSymlinkCustom(): array {
    return [
      'gitHooks' => TRUE,
    ];
  }

  /**
   * @return $this
   */
  protected function prepareProject() {
    $this
      ->prepareProjectSelf()
      ->prepareProjectComposerJson()
      ->prepareProjectDirs()
      ->prepareProjectSettingsPhp()
      ->prepareProjectDrush()
      ->prepareProjectSite()
      ->prepareProjectGit();

    return $this;
  }

  protected function prepareProjectSelf() {
    $name = $this->event->getComposer()->getPackage()->getName();

    $this->fs->symlink(
      "../../../../../$name",
      Path::join(
        $this->projectRoot,
        'drush',
        'Commands',
        'contrib',
        $this->getComposerPackageName(),
      ),
    );

    return $this;
  }

  /**
   * @return $this
   */
  protected function prepareProjectComposerJson() {
    $selfName = $this->event->getComposer()->getPackage()->getName();

    $fileName = Path::join($this->cwd, $this->projectRoot, 'composer.json');
    $fileContent = [
      'name' => "{$selfName}-project_01",
      'description' => "{$selfName}-tests-project_01",
      "license" => "proprietary",
      'type' => 'drupal-project',
      'minimum-stability' => 'dev',
      'prefer-stable' => TRUE,
      'config' => [
        'optimize-autoloader' => TRUE,
        'preferred-install' => [
          '*' => 'dist',
        ],
        'process-timeout' => 0,
        'sort-packages' => TRUE,
      ],
      'repositories' => [
        'drupal/marvin' => [
          'type' => 'git',
          'url' => 'https://github.com/Sweetchuck/drupal-marvin.git',
        ],
        $selfName => [
          'type' => 'path',
          'url' => "../packages/$selfName",
        ],
        'drupal/dummy_m1' => [
          'type' => 'path',
          'url' => '../../drupal/dummy_m1',
        ],
        'drupal/dummy_m2' => [
          'type' => 'path',
          'url' => '../../drupal/dummy_m2',
        ],
        'drupal' => [
          'type' => 'composer',
          'url' => 'https://packages.drupal.org/8',
        ],
        'assets' => [
          'type' => 'composer',
          'url' => 'https://asset-packagist.org',
        ],
      ],
      'require' => [
        'cweagans/composer-patches' => '^1.6',
        'drupal/core-composer-scaffold' => '~9.4',
        'drupal/core-recommended' => '~9.4',
        $selfName => '*',
        'drupal/dummy_m1' => '*',
        'drupal/dummy_m2' => '*',
        'drush/drush' => '^10.0',
        'oomphinc/composer-installers-extender' => '^2.0',
        'phpunit/phpunit' => '^9.0',
      ],
      'extra' => [
        'installer-types' => [
          'bower-asset',
          'npm-asset',
        ],
        'installer-paths' => [
          'docroot/core' => [
            'type:drupal-core',
          ],
          'docroot/libraries/{$name}' => [
            'type:drupal-library',
            'type:bower-asset',
            'type:npm-asset',
          ],
          'docroot/modules/contrib/{$name}' => [
            'type:drupal-module',
          ],
          'docroot/profiles/contrib/{$name}' => [
            'type:drupal-profile',
          ],
          'docroot/themes/contrib/{$name}' => [
            'type:drupal-theme',
          ],
          'drush/Commands/contrib/{$name}' => [
            'type:drupal-drush',
          ],
        ],
        'enable-patching' => TRUE,
        'composer-exit-on-patch-failure' => TRUE,
        'patches' => [
          'drupal/core' => [
            'https://www.drupal.org/project/drupal/issues/3049087 - SQLite database maxlength' => 'https://www.drupal.org/files/issues/2019-05-07/3049087-15.patch',
          ],
        ],
        'drupal-scaffold' => [
          'locations' => [
            'web-root' => 'docroot',
          ],
          'file-mapping' => [
            '[web-root]/modules/.gitignore' => [
              'mode' => 'skip',
            ],
            '[web-root]/modules/README.txt' => [
              'mode' => 'skip',
            ],
            '[web-root]/profiles/.gitignore' => [
              'mode' => 'skip',
            ],
            '[web-root]/profiles/README.txt' => [
              'mode' => 'skip',
            ],
            '[web-root]/themes/.gitignore' => [
              'mode' => 'skip',
            ],
            '[web-root]/themes/README.txt' => [
              'mode' => 'skip',
            ],
            '[web-root]/sites/example.settings.local.php' => [
              'mode' => 'skip',
            ],
            '[web-root]/sites/.gitignore' => [
              'mode' => 'skip',
            ],
            '[web-root]/sites/README.txt' => [
              'mode' => 'skip',
            ],
            '[web-root]/.csslintrc' => [
              'mode' => 'skip',
            ],
            '[web-root]/.editorconfig' => [
              'mode' => 'skip',
            ],
            '[web-root]/.eslintignore' => [
              'mode' => 'skip',
            ],
            '[web-root]/.eslintrc.json' => [
              'mode' => 'skip',
            ],
            '[web-root]/.gitattributes' => [
              'mode' => 'skip',
            ],
            '[web-root]/.gitignore' => [
              'mode' => 'skip',
            ],
            '[web-root]/example.gitignore' => [
              'mode' => 'skip',
            ],
            '[web-root]/INSTALL.txt' => [
              'mode' => 'skip',
            ],
            '[web-root]/README.txt' => [
              'mode' => 'skip',
            ],
            '[web-root]/.htaccess' => [
              'mode' => 'skip',
            ],
            '[web-root]/web.config' => [
              'mode' => 'skip',
            ],
            '[project-root]/.editorconfig' => [
              'mode' => 'skip',
            ],
          ],
          'initial' => [
            'sites/default/default.services.yml' => 'sites/default/services.yml',
            'sites/default/default.settings.php' => 'sites/default/settings.php',
          ],
        ],
      ],
    ];

    $this->logger->info(
      'create file: {fileName}',
      [
        'fileName' => $fileName,
      ],
    );

    $this->fs->mkdir(Path::getDirectory($fileName));
    $this->fs->dumpFile($fileName, json_encode($fileContent, $this->jsonEncodeFlags));

    return $this;
  }

  protected function prepareProjectDirs() {
    $projectRoot = $this->projectRoot;

    $dirs = [
      "$projectRoot/docroot/libraries",
      "$projectRoot/docroot/profiles",
      "$projectRoot/docroot/themes",
      "$projectRoot/sites/default/config/prod",
      "$projectRoot/sites/default/database/",
    ];

    $this->logger->info(
      'create directories: {dirs}',
      [
        'dirs' => implode(', ', $dirs),
      ],
    );

    $this->fs->mkdir($dirs, 0777 - umask());

    return $this;
  }

  protected function prepareProjectSettingsPhp() {
    $src = Path::join($this->projectRoot, 'docroot', 'sites', 'default', 'default.settings.php');
    $dst = Path::join($this->projectRoot, 'docroot', 'sites', 'default', 'settings.php');

    $args = [
      'srcFileName' => $src,
      'dstFileName' => $dst,
    ];

    if (!$this->fs->exists($src)) {
      $this->logger->info("source file does not exists: {srcFileName}", $args);

      return $this;
    }

    if ($this->fs->exists($dst)) {
      $this->logger->info("destination file already exists: {dstFileName}", $args);

      return $this;
    }

    $this->logger->info("copy {srcFileName} => {dstFileName}", $args);

    $replacementPairs = [];
    $replacementPairs['$databases = [];'] = <<<'PHP'
$databases = [
  'default' => [
    'default' => [
      'driver' => 'sqlite',
      'namespace' => '\Drupal\Core\Database\Driver\sqlite',
      'database' => "../$site_path/database/default.default.sqlite",
      'prefix' => '',
    ],
  ],
];
PHP;

    $key = "# \$settings['config_sync_directory'] = '/directory/outside/webroot';";
    $replacementPairs[$key] = "\$settings['config_sync_directory'] = \"../\$site_path/config/prod\";";

    $key = <<< 'PHP'
# $settings['file_chmod_directory'] = 0775;
# $settings['file_chmod_file'] = 0664;
PHP;
    $replacementPairs[$key] = <<< 'PHP'
$settings['file_chmod_directory'] = 0777 - umask();
$settings['file_chmod_file'] = 0666 - umask();
PHP;

    $this->fs->dumpFile($dst, strtr(FilesystemUtils::fileGetContents($src), $replacementPairs));

    return $this;
  }

  protected function prepareProjectDrush() {
    $files = [
      'drush/drush.yml' => implode("\n", [
        'drush:',
        '  paths:',
        '    config:',
        "      - '\${drush.vendor-dir}/../{$this->projectRoot}/drush/Commands/contrib/marvin/Commands/drush.yml'",
        "      - '\${drush.vendor-dir}/../{$this->projectRoot}/drush/Commands/contrib/marvin_incubator/Commands/drush.yml'",
        "      - '\${drush.vendor-dir}/../{$this->projectRoot}/drush/drush.app.yml'",
        "      - '\${drush.vendor-dir}/../{$this->projectRoot}/drush/drush.local.yml'",
        '    include:',
        "      - '\${drush.vendor-dir}/../{$this->projectRoot}/drush/Commands/contrib/marvin'",
        "      - '\${drush.vendor-dir}/../{$this->projectRoot}/drush/Commands/contrib/marvin_incubator'",
      ]),
      'drush/drush.app.yml' => implode("\n", [
        'options:',
        "  uri: 'http://127.0.0.1:8888'",
        '',
      ]),
      'drush/drush.local.yml' => implode("\n", [
        'options:',
        "  uri: 'http://127.0.0.1:8888'",
        '',
      ]),
      'drush/sites/app.site.yml' => implode("\n", [
        'local:',
        "  root: '\${drush.vendor-dir}/{$this->projectRoot}/docroot'",
        '',
      ]),
    ];

    foreach ($files as $fileName => $fileContent) {
      $this->fs->dumpFile("{$this->projectRoot}/$fileName", $fileContent);
    }

    return $this;
  }

  protected function prepareProjectSite() {
    $dbFile = $this->getSqliteFileName();

    if ($this->fs->exists($dbFile)) {
      $this->logger->info(
        'file already exists: {fileName}',
        [
          'fileName' => $dbFile,
        ],
      );

      return $this;
    }

    $this->prepareProjectSiteInstall();
    $this->prepareProjectSiteConfiguration();
    $this->prepareProjectSiteConfigExport();

    return $this;
  }

  protected function prepareProjectSiteInstall() {
    $dbFile = $this->getSqliteFileName();

    $command = [
      'drush',
      '--ansi',
      '--root=docroot',
      '--yes',
      '--sites-subdir=default',
      'site:install',
    ];

    $process = $this->processRun(
      $command,
      $this->projectRoot,
      ['COMPOSER' => 'composer.json'] + getenv(),
    );
    $this->assertProcess(0, $process);

    $this->fs->copy(
      $dbFile,
      Path::join(Path::getDirectory($dbFile), 'original.default.sqlite'),
    );

    return $this;
  }

  protected function prepareProjectSiteConfiguration() {
    // @todo Uninstall: node, shortcut.
    return $this;
  }

  protected function prepareProjectSiteConfigExport() {
    $command = [
      'drush',
      '--ansi',
      '--root=docroot',
      '--yes',
      'config:export',
    ];

    $process = $this->processRun($command, $this->projectRoot);
    $this->assertProcess(0, $process);

    return $this;
  }

  protected function prepareProjectGit() {
    $fileName = "{$this->projectRoot}/.git";
    if ($this->fs->exists($fileName)) {
      $this->logger->info(
        'file already exists: {fileName}',
        [
          'fileName' => $fileName,
        ],
      );

      return $this;
    }

    $this
      ->prepareProjectGitIgnore()
      ->initGitRepo($this->projectRoot);

    return $this;
  }

  protected function prepareProjectGitIgnore() {
    $fileName = Path::join(
      $this->projectRoot,
      '.gitignore',
    );
    $content = implode("\n", [
      '/sites/*/database/',
      '',
    ]);

    $this->logger->info('create file: {fileName}', ['fileName' => $fileName]);
    $this->fs->dumpFile($fileName, $content);

    return $this;
  }

  protected function initGitRepo(string $root) {
    if ($this->fs->exists("$root/.git")) {
      $this->logger->info("Git repository already initialized: $root");

      return $this;
    }

    $shell = getenv('SHELL') ?: '/bin/bash';

    // @todo Old Git version does not support "git init --initial-branch".
    $command = [
      $this->gitExecutable,
      'init',
    ];
    $process = $this->processRun($command, $root);
    $this->assertProcess(0, $process);

    $command = [
      $this->gitExecutable,
      'checkout',
      '-b',
      'main',
    ];
    $process = $this->processRun($command, $root);
    $this->assertProcess(0, $process);

    $command = [
      $shell,
      '-c',
      sprintf(
        '%s add . 1>/dev/null 2>/dev/null',
        escapeshellcmd($this->gitExecutable),
      ),
    ];
    $process = $this->processRun($command, $root);
    $this->assertProcess(0, $process);

    $command = [
      $shell,
      '-c',
      sprintf(
        '%s commit --message="%s" 1>/dev/null 2>/dev/null',
        $this->gitExecutable,
        'Initial commit',
      ),
    ];
    $process = $this->processRun($command, $root);
    $this->assertProcess(0, $process);

    return $this;
  }

  protected function assertProcess(int $expectedExitCode, Process $process) {
    if ($process->getExitCode() === $expectedExitCode) {
      return $this;
    }

    throw new \RuntimeException(sprintf(
      "command failed: %s\nstdError: %s",
      $process->getCommandLine(),
      $process->getErrorOutput(),
    ));
  }

  protected function getComposerPackageName(): string {
    $parts = explode('/', $this->event->getComposer()->getPackage()->getName(), 2);
    if (empty($parts[1])) {
      throw new \Exception('Invalid package name', 1);
    }

    return $parts[1];
  }

  protected function processRun(array $command, string $workingDirectory, ?array $env = NULL): Process {
    $this->logger->info(
      'run {cmd} in {cwd}',
      [
        'cmd' => implode(' ', $command),
        'cwd' => $workingDirectory,
      ],
    );

    $process = new Process($command, NULL, $env, NULL, 0);
    $process->setWorkingDirectory($workingDirectory);
    $process->run($this->processCallbackWrapper);

    return $process;
  }

  protected function processCallback(string $type, string $buffer): void {
    $type === Process::OUT ?
      $this->event->getIO()->write($buffer, FALSE)
      : $this->event->getIO()->writeError($buffer, FALSE);
  }

  protected function escapeXmlAttribute(string $value): string {
    return htmlentities($value, ENT_QUOTES);
  }

  protected function getPhpunitEnvVars(): array {
    return [
      'BROWSERTEST_OUTPUT_DIRECTORY' => realpath($this->cwd) . "/{$this->projectRoot}/docroot/sites/simpletest/browser_output",
      'REAL_NVM_DIR' => getenv('NVM_DIR'),
    ];
  }

  protected function getSqliteFileName(): string {
    return Path::join(
      $this->projectRoot,
      'sites',
      'default',
      'database',
      'default.default.sqlite',
    );
  }

}
