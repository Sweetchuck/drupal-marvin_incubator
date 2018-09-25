<?php

declare(strict_types = 1);

namespace Drush\Commands\marvin_incubator\Test;

use Drush\Commands\marvin\Test\PhpunitCommandsBase;
use Drupal\marvin\Utils as MarvinUtils;
use Drupal\marvin_incubator\CommandsBaseTrait;
use Robo\Collection\CollectionBuilder;
use Sweetchuck\CliCmdBuilder\CommandBuilder;
use Sweetchuck\Utils\Filter\ArrayFilterEnabled;
use Symfony\Component\Filesystem\Filesystem;

class PhpunitCommands extends PhpunitCommandsBase {

  use CommandsBaseTrait;

  /**
   * @var \Symfony\Component\Filesystem\Filesystem
   */
  protected $fs;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    parent::__construct();

    $this->fs = new Filesystem();
  }

  /**
   * @command marvin:test:phpunit
   * @bootstrap none
   *
   * @marvinArgPackages packages
   * @marvinOptionPhpVariants phpVariants
   */
  public function phpunit(
    array $packages,
    array $options = [
      'phpVariants' => [],
    ]
  ): CollectionBuilder {
    $cb = $this->collectionBuilder();

    // @todo CLI option for testSuiteNames.
    $testSuiteNames = $this->getTestSuiteNamesByEnvironmentVariant();
    if ($testSuiteNames === NULL || !$packages) {
      return $cb;
    }

    $groups = [];
    foreach ($packages as $packageName) {
      $groups[] = MarvinUtils::splitPackageName($packageName)['name'];
    }

    $phpVariants = array_filter($options['phpVariants'], new ArrayFilterEnabled());
    $dbVariants = [
      'my0506' => ['id' => 'my0506'],
    ];

    foreach ($phpVariants as $phpVariant) {
      foreach ($dbVariants as $dbVariant) {
        $cb->addTask($this->getTaskPhpUnit($testSuiteNames, $groups, $phpVariant, $dbVariant));
      }
    }

    return $cb;
  }

  protected function getTaskPhpUnit(
    array $testSuiteNames,
    array $groupNames,
    array $phpVariant,
    array $dbVariant = []
  ): CollectionBuilder {
    $phpUnitTask = parent::getTaskPhpUnit($testSuiteNames, $groupNames, $phpVariant);

    $onlyUnitTestSuite = $testSuiteNames == ['unit'];
    if ($onlyUnitTestSuite) {
      return $phpUnitTask;
    }

    $phpUnitConfigFileName = $this->getPhpUnitConfigFileName($phpVariant, $dbVariant);
    if ($this->fs->exists($phpUnitConfigFileName)) {
      $phpUnitTask->setConfiguration($phpUnitConfigFileName);

      return $phpUnitTask;
    }

    $simpleTestBaseUrlEnv = getenv('SIMPLETEST_BASE_URL');
    $simpleTestBaseUrlInput = $this->input()->getOption('uri');
    if (!$simpleTestBaseUrlEnv && $simpleTestBaseUrlInput) {
      $phpExecutable = (new CommandBuilder())
        ->addEnvVar('SIMPLETEST_BASE_URL', $simpleTestBaseUrlInput)
        ->setExecutable($phpVariant['phpdbgExecutable'])
        ->addOption('-qrr');

      $phpUnitTask->setPhpExecutable($phpExecutable);
    }

    return $phpUnitTask;
  }

  protected function getPhpUnitConfigFileName(
    array $phpVariant,
    array $dbVariant
  ): string {
    $projectRootDir = $this->getProjectRootDir();

    return "$projectRootDir/phpunit.{$dbVariant['id']}.{$phpVariant['version']['majorMinor']}.xml";
  }

}
