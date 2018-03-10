<?php

namespace Drush\Commands\marvin_incubator;

use Drush\Commands\marvin\CommandsBase;

class ManagedDrupalExtensionCommands extends CommandsBase {

  /**
   * List the managed Drupal extensions.
   *
   * @command marvin:managed-drupal-extension:list
   * @bootstrap none
   * @option $format
   */
  public function managedDrupalExtensionList(
    array $options = [
      'format' => 'yaml',
    ]
  ): array {
    return $this->getManagedDrupalExtensions();
  }

}
