<?php

declare(strict_types = 1);

namespace Drupal\marvin_incubator\Robo\Task;

use Consolidation\AnnotatedCommand\Output\OutputAwareInterface;
use Drupal\marvin\Robo\Task\BaseTask;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Collection\CollectionBuilder;
use Robo\Common\BuilderAwareTrait;
use Robo\Common\IO;
use Robo\Contract\BuilderAwareInterface;
use Robo\Task\File\Tasks as FileTaskLoader;
use Robo\Task\Filesystem\Tasks as FilesystemTaskLoader;

/**
 * @todo Delete only one specific database variant.
 */
class SiteDeleteTask extends BaseTask implements
  BuilderAwareInterface,
  ContainerAwareInterface,
  OutputAwareInterface {

  use BuilderAwareTrait;
  use ContainerAwareTrait;
  use IO;
  use FilesystemTaskLoader;
  use FileTaskLoader;

  protected string $drupalRoot = '.';

  public function getDrupalRoot(): string {
    return $this->drupalRoot;
  }

  public function setDrupalRoot(string $value): static {
    $this->drupalRoot = $value;

    return $this;
  }

  protected string $siteName = '';

  public function getSiteName(): string {
    return $this->siteName;
  }

  public function setSiteName(string $value): static {
    $this->siteName = $value;

    return $this;
  }

  protected CollectionBuilder $cb;

  public function setOptions(array $options): static {
    parent::setOptions($options);

    if (array_key_exists('drupalRoot', $options)) {
      $this->setDrupalRoot($options['drupalRoot']);
    }

    if (array_key_exists('siteName', $options)) {
      $this->setSiteName($options['siteName']);
    }

    return $this;
  }

  protected function runAction(): static {
    // @todo Drop resources.
    // - Database.
    // - Redis.
    // - SearchAPI Solr.
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

  protected function addTaskDeleteDirectories(): static {
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

  protected function addTaskDeleteDrushSiteAliases(): static {
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

    return [
      "$drupalRoot/../drush/sites/$siteName.site.yml",
    ];
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
