<?php

namespace Drupal\Tests\remotedb\Traits;

use Drupal\remotedb\Entity\Remotedb;

/**
 * Provides methods to create remote databases with default settings.
 *
 * This trait is meant to be used only by test classes.
 */
trait RemotedbCreationTrait {

  /**
   * Creates a remote database entity.
   *
   * @param array $settings
   *   (optional) An associative array of settings for the remote database
   *   entity.
   *
   * @return \Drupal\remotedb\Entity\RemotedbInterface
   *   The created remote database entity.
   */
  protected function createRemotedb(array $settings = []) {
    $settings += [
      'name' => mb_strtolower($this->randomMachineName()),
      'label' => $this->randomMachineName(),
      'url' => 'http://www.example.com',
    ];

    $remotedb = Remotedb::create($settings);
    $remotedb->save();

    return $remotedb;
  }

}
