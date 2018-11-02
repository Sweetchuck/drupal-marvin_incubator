<?php

declare(strict_types = 1);

namespace Drupal\marvin_incubator\Robo\Task;

use Drupal\marvin\Robo\Task\BaseTask;
use Drupal\marvin_incubator\Utils as MarvinIncubatorUtils;
use Webmozart\PathUtil\Path;

class CollectSiteNamesTask extends BaseTask {

  /**
   * {@inheritdoc}
   */
  protected $taskName = 'Marvin - Collect site names';

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
  public function setDrupalRoot(string $drupalRoot) {
    $this->drupalRoot = $drupalRoot;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions(array $options) {
    parent::setOptions($options);

    if (array_key_exists('drupalRoot', $options)) {
      $this->setDrupalRoot($options['drupalRoot']);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function runAction() {
    $sitesDir = Path::join($this->getDrupalRoot(), 'sites');
    $siteDirs = MarvinIncubatorUtils::getSiteDirs($sitesDir);
    $this->assets['siteNames'] = MarvinIncubatorUtils::getSiteNames($siteDirs);

    return $this;
  }

}
