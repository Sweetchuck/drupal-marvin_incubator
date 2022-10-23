<?php

declare(strict_types = 1);

namespace Drupal\marvin_incubator\Robo\Task;

use Drupal\marvin\Robo\Task\BaseTask;
use Drupal\marvin_incubator\Utils as MarvinIncubatorUtils;
use Symfony\Component\Filesystem\Path;

class CollectSiteNamesTask extends BaseTask {

  /**
   * {@inheritdoc}
   */
  protected string $taskName = 'Marvin - Collect site names';

  protected string $drupalRoot = '.';

  public function getDrupalRoot(): string {
    return $this->drupalRoot;
  }

  public function setDrupalRoot(string $drupalRoot): static {
    $this->drupalRoot = $drupalRoot;

    return $this;
  }

  public function setOptions(array $options): static {
    parent::setOptions($options);

    if (array_key_exists('drupalRoot', $options)) {
      $this->setDrupalRoot($options['drupalRoot']);
    }

    return $this;
  }

  protected function runAction(): static {
    $sitesDir = Path::join($this->getDrupalRoot(), 'sites');
    $siteDirs = MarvinIncubatorUtils::getSiteDirs($sitesDir);
    $this->assets['siteNames'] = MarvinIncubatorUtils::getSiteNames($siteDirs);

    return $this;
  }

}
