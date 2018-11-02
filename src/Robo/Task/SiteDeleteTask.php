<?php

declare(strict_types = 1);

namespace Drupal\marvin_incubator\Robo\Task;

use Drupal\marvin\Robo\Task\BaseTask;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Common\BuilderAwareTrait;
use Robo\Common\IO;
use Robo\Contract\BuilderAwareInterface;
use Robo\Contract\OutputAwareInterface;
use Robo\Task\File\loadTasks as FileTaskLoader;
use Robo\Task\Filesystem\loadTasks as FilesystemTaskLoader;

class SiteDeleteTask extends BaseTask implements
    BuilderAwareInterface,
    ContainerAwareInterface,
    OutputAwareInterface {

  use BuilderAwareTrait;
  use ContainerAwareTrait;
  use IO;
  use FilesystemTaskLoader;
  use FileTaskLoader;

  /**
   * @var string
   */
  protected $drupalRoot = '.';

  public function getDrupalRoot(): string {
    return $this->drupalRoot;
  }

  /**
   * @return $this
   */
  public function setDrupalRoot(string $value) {
    $this->drupalRoot = $value;

    return $this;
  }

  /**
   * @var string
   */
  protected $siteName = '';

  public function getSiteName(): string {
    return $this->siteName;
  }

  /**
   * @return $this
   */
  public function setSiteName(string $value) {
    $this->siteName = $value;

    return $this;
  }

  /**
   * @var \Robo\Collection\CollectionBuilder
   */
  protected $cb;

  public function setOptions(array $options) {
    parent::setOptions($options);

    if (array_key_exists('drupalRoot', $options)) {
      $this->setDrupalRoot($options['drupalRoot']);
    }

    if (array_key_exists('siteName', $options)) {
      $this->setSiteName($options['siteName']);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function runAction() {
    $this->cb = $this->collectionBuilder();
    $this
      ->addTaskDeleteDirectories()
      ->addTaskDeleteDrushSiteAliases();

    $this
      ->cb
      ->run()
      ->stopOnFail();

    return $this;
  }

  /**
   * @return $this
   */
  protected function addTaskDeleteDirectories() {
    $drupalRoot = $this->getDrupalRoot();

    $innerSiteDirs = $this->getSiteDirs("$drupalRoot/sites");
    $outerSiteDirs = $this->getSiteDirs($this->getOuterSitePath());

    $this->cb
      ->addTask($this
        ->taskFilesystemStack()
        ->chmod($innerSiteDirs, 0700, 0000, TRUE)
        ->remove($innerSiteDirs)
        ->chmod($outerSiteDirs, 0700, 0000, TRUE)
        ->remove($outerSiteDirs)
      );

    return $this;
  }

  /**
   * @return $this
   */
  protected function addTaskDeleteDrushSiteAliases() {
    $this
      ->cb
      ->addTask($this
        ->taskFilesystemStack()
        ->remove($this->getDrushSiteAliasFileNames())
      );

    return $this;
  }

  protected function getOuterSitePath(): string {
    $drupalRoot = $this->getDrupalRoot();

    return "$drupalRoot/../sites";
  }

  /**
   * @return string[]
   */
  protected function getDrushSiteAliasFileNames(): array {
    $drupalRoot = $this->getDrupalRoot();
    $siteName = $this->getSiteName();

    return glob("$drupalRoot/../drush/sites/$siteName-*.site.yml");
  }

  protected function getSiteDirs(string $parentDir): array {
    $siteDirs = [];

    $siteName = $this->getSiteName();
    $entries = new \DirectoryIterator($parentDir);
    foreach ($entries as $entry) {
      if (!$entry->isDir()
        || $entry->isDot()
        || mb_strpos($entry->getBasename(), "$siteName.") !== 0
      ) {
        continue;
      }

      $siteDirs[] = $entry->getPathname();
    }

    return $siteDirs;
  }

}
