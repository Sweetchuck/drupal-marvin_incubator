<?php

declare(strict_types = 1);

namespace Drupal\marvin_incubator;

use Robo\Contract\TaskInterface;

trait SiteGenerateSitesPhpTrait {

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drupal\marvin_incubator\Robo\Task\CollectSiteNamesTask
   */
  abstract protected function taskMarvinCollectSiteNames();

  /**
   * @return \Robo\Collection\CollectionBuilder|\Drupal\marvin_incubator\Robo\Task\SitesPhpGeneratorTask
   */
  abstract protected function taskMarvinGenerateSitesPhp(array $options = []);

  protected function getTaskMarvinGenerateSitesPhp(array $dbVariants): TaskInterface {
    /** @var \Drush\Boot\BootstrapManager $bootstrapManager */
    $bootstrapManager = $this->getContainer()->get('bootstrap.manager');
    $drupalRootAbs = $bootstrapManager->getRoot();

    $config = $this->getConfig();
    $siteDirPattern = $config->get('marvin.siteDirPattern', '');
    $urlPattern = $config->get('marvin.urlPattern', '');

    return $this
      ->collectionBuilder()
      ->addTask(
        $this
          ->taskMarvinCollectSiteNames()
          ->setDrupalRoot($drupalRootAbs)
      )
      ->addTask(
        $this
          ->taskMarvinGenerateSitesPhp()
          ->setSiteDirPattern($siteDirPattern)
          ->setUrlPattern($urlPattern)
          ->setOutputDestination("$drupalRootAbs/sites/sites.php")
          ->setDatabaseVariantIds(array_keys($dbVariants))
          ->deferTaskConfiguration('setSiteNames', 'siteNames')
      );
  }

}
