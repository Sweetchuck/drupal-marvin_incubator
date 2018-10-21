<?php

namespace Drush\Commands\Tests\marvin_incubator\Unish;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use Unish\CommandUnishTestCase;
use Webmozart\PathUtil\Path;

abstract class CommandsTestBase extends CommandUnishTestCase {

  /**
   * @var string
   */
  protected static $composerRoot = '';

  /**
   * @var string
   */
  protected static $binDir = 'bin';

  /**
   * {@inheritdoc}
   */
  public static function getComposerRoot() {
    if (static::$composerRoot === '') {
      static::$composerRoot = Path::canonicalize(Path::join(__DIR__, '..', '..', '..'));
    }

    return static::$composerRoot;
  }

  /**
   * {@inheritdoc}
   */
  public static function getDrush() {
    return Path::join(static::getComposerRoot(), static::$binDir, 'drush');
  }

  /**
   * @return string
   */
  public static function getSut() {
    return static::getComposerRoot() . '/tests/fixtures/drush-sut';
  }

  /**
   * {@inheritdoc}
   */
  public static function webroot() {
    return static::getSut() . '/web';
  }

  protected static function getExtensionsDir(): string {
    return static::getComposerRoot() . '/tests/fixtures/extensions';
  }

  /**
   * @return \Symfony\Component\Finder\Finder|\Symfony\Component\Finder\SplFileInfo[]
   */
  protected static function getExtensionDirs(): Finder {
    return (new Finder())
      ->in(static::getExtensionsDir())
      ->directories()
      ->depth('== 0');
  }

  /**
   * {@inheritdoc}
   *
   * Replace self::getDrush() with static::getDrush().
   * Support array values for --include.
   * Use the same PHP executable.
   */
  public function drush(
    $command,
    array $args = [],
    array $options = [],
    $site_specification = NULL,
    $cd = NULL,
    $expected_return = self::EXIT_SUCCESS,
    $suffix = NULL,
    $env = [],
    $withCoverage = TRUE
  ) {
    $sites = static::getSites();

    // Cd is added for the benefit of siteSshTest which tests a strict command.
    $global_option_list = [
      'simulate',
      'root',
      'uri',
      'include',
      'config',
      'alias-path',
      'ssh-options',
      'backend',
      'cd',
    ];

    $options += ['uri' => 'http://' . key($sites)];
    $hide_stderr = FALSE;
    $cmd = [
      $this->getPhpExecutable(),
      static::getDrush(),
    ];

    // Insert global options.
    foreach ($options as $key => $value) {
      if (in_array($key, $global_option_list)) {
        unset($options[$key]);
        if ($key == 'backend') {
          $hide_stderr = TRUE;
          $value = NULL;
        }

        if ($key == 'uri' && $value == 'OMIT') {
          continue;
        }

        if (!isset($value)) {
          $cmd[] = "--$key";
        }
        else {
          if (!is_array($value)) {
            $value = [$value];
          }

          foreach ($value as $v) {
            $cmd[] = "--$key=" . static::escapeshellarg($v);
          }
        }
      }
    }

    if ($level = $this->logLevel()) {
      $cmd[] = '--' . $level;
    }
    $cmd[] = "--no-interaction";

    // Insert code coverage argument before command, in order for it to be
    // parsed as a global option. This matters for commands like ssh and rsync
    // where options after the command are passed along to external commands.
    $result = $this->getTestResultObject();
    if ($withCoverage && $result->getCollectCodeCoverageInformation()) {
      $coverage_file = tempnam($this->getSandbox(), 'drush_coverage');
      if ($coverage_file) {
        $cmd[] = "--drush-coverage=" . $coverage_file;
      }
    }

    // Insert site specification and drush command.
    $cmd[] = empty($site_specification)
      ? NULL
      : static::escapeshellarg($site_specification);

    $cmd[] = $command;

    // Insert drush command arguments.
    foreach ($args as $arg) {
      $cmd[] = static::escapeshellarg($arg);
    }

    // Insert drush command options.
    foreach ($options as $key => $value) {
      if (!isset($value)) {
        $cmd[] = "--$key";
      }
      else {
        $cmd[] = "--$key=" . static::escapeshellarg($value);
      }
    }

    $cmd[] = $suffix;
    if ($hide_stderr) {
      $cmd[] = '2>' . $this->bitBucket();
    }

    // Remove NULLs.
    $exec = array_filter($cmd, 'strlen');

    // Set sendmail_path to 'true' to disable any outgoing emails
    // that tests might cause Drupal to send.
    $php_options = (array_key_exists('PHP_OPTIONS', $env)) ?
      $env['PHP_OPTIONS'] . ' ' : '';

    // @todo The PHP Options below are not yet honored by execute().
    // See .travis.yml for an alternative way.
    $env['PHP_OPTIONS'] = "{$php_options}-d sendmail_path='true'";
    $cmd = implode(' ', $exec);
    $return = $this->execute($cmd, $expected_return, $cd, $env);

    // Save code coverage information.
    if (!empty($coverage_file)) {
      $data = unserialize(file_get_contents($coverage_file));
      unlink($coverage_file);
      // Save for appending after the test finishes.
      $this->coverage_data[] = $data;
    }

    return $return;
  }

  public function installDrupal($env = 'dev', $install = FALSE, $withCoverage = FALSE) {
    $root = $this->webroot();
    $uri = $env;
    $site = "$root/sites/$uri";

    // If specified, install Drupal as a multi-site.
    if ($install) {
      $options = [
        'root' => $root,
        'db-url' => $this->dbUrl($env),
        'sites-subdir' => $uri,
        'yes' => NULL,
        'quiet' => NULL,
      ];
      $this->drush(
        'site-install',
        ['testing', 'install_configure_form.enable_update_status_emails=NULL'],
        $options,
        NULL,
        NULL,
        self::EXIT_SUCCESS,
        NULL,
        [],
        $withCoverage
      );
      // Give us our write perms back.
      chmod($site, 0777);
    }
    else {
      $this->mkdir($site);
      touch("$site/settings.php");
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    if (!$this->getSites()) {
      $this->setUpDrupal(1, $this->setUpDrupalNeedsToBeInstalled());
    }

    parent::setUp();
    $this->deleteTestArtifacts();
    $this->initGitRepoForDummyExtensions();
  }

  protected function setUpDrupalNeedsToBeInstalled(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    $this->deleteTestArtifacts();
    $this->deleteGitHistoryOfDummyExtensions();
    parent::tearDown();
  }

  /**
   * Clean .phpstorm.meta.php directory.
   */
  protected function deleteTestArtifacts() {
    return $this;
  }

  /**
   * @return $this
   */
  protected function initGitRepoForDummyExtensions() {
    foreach (static::getExtensionDirs() as $extensionDir) {
      $this->initGitRepoWithInitialCommit($extensionDir->getPathname());
    }

    return $this;
  }

  protected function deleteGitHistoryOfDummyExtensions() {
    $fs = new Filesystem();
    foreach (static::getExtensionDirs() as $extensionDir) {
      $fs->remove("$extensionDir/.git");
    }

    return $this;
  }

  protected function initGitRepoWithInitialCommit(string $dir) {
    $command = implode(' && ', [
      'git init',
      "git config user.name 'Unish Drush'",
      "git config user.email 'unish.drush@example.com'",
      'git add .',
      "git commit -m 'Initial commit'",
      'git clean --force -d -X',
      'git clean --force -d -x',
    ]);

    $process = new Process($command, $dir);
    $this->assertSame(0, $process->run(), $process->getErrorOutput());

    return $this;
  }

  protected function getDefaultDrushCommandOptions(): array {
    $projectRootDir = static::getSut();

    return [
      'root' => $this->webroot(),
      'uri' => 'http://' . key(static::getSites()),
      'yes' => NULL,
      'no-ansi' => NULL,
      'config' => [
        "$projectRootDir/drush/unish/marvin/drush.yml",
        "$projectRootDir/drush/custom/marvin_incubator/drush.yml",
        "$projectRootDir/drush",
        "$projectRootDir/drush/drush.local.yml",
      ],
      'include' => [
        "$projectRootDir/drush/unish/marvin",
        "$projectRootDir/drush/custom/marvin_incubator",
      ],
    ];
  }

  protected function getPhpExecutable(): string {
    // @todo Make it configurable through environment variable.
    return PHP_BINDIR . '/php';
  }

  /**
   * @var string
   */
  protected $drushCommand = 'marvin';

  protected function getDrushCommand(): string {
    return $this->drushCommand;
  }

  abstract public function casesExecuteDrushCommand(): array;

  /**
   * @dataProvider casesExecuteDrushCommand
   */
  public function testExecuteDrushCommand(array $expected, array $args, array $options): void {
    $this->assertExecuteDrushCommand($expected, $this->getDrushCommand(), $args, $options);
  }

  protected function assertExecuteDrushCommand(
    array $expected,
    string $command,
    array $args = [],
    array $options = []
  ) {
    $expected += [
      'exitCode' => 0,
      'stdOutput' => [
        'same' => [
          'default' => '',
        ],
      ],
      'stdError' => [
        'same' => [
          'default' => '',
        ],
      ],
    ];

    $this->drush(
      $command,
      $args,
      $options,
      NULL,
      static::getSut(),
      $expected['exitCode']
    );

    if (array_key_exists('stdOutput', $expected)) {
      $this->assertText($expected['stdOutput'], $this->getOutput(), 'stdOutput');
    }

    if (array_key_exists('stdError', $expected)) {
      $this->assertText($expected['stdError'], $this->getErrorOutput(), 'stdError');
    }
  }

  protected function assertText(array $rules, $text, string $msgPrefix) {
    foreach ($rules as $assertType => $expectations) {
      foreach ($expectations as $message => $expected) {
        $fullMessage = "$msgPrefix $assertType - $message";
        switch ($assertType) {
          case 'same':
            $this->assertSame($expected, $text, $fullMessage);
            break;

          case 'contains':
            $this->assertContains($expected, $text, $fullMessage);
            break;
        }
      }
    }
  }

}
