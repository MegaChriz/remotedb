<?php

namespace Drupal\remotedb_role;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\remotedb\RemotedbFactoryBase;

/**
 * Factory for instantiating subscription service.
 */
class SubscriptionServiceFactory extends RemotedbFactoryBase implements SubscriptionServiceFactoryInterface {

  /**
   * {@inheritdoc}
   */
  protected function getRemotedbId(ConfigFactoryInterface $config_factory) {
    return $config_factory->get('remotedb_role.settings')->get('remotedb');
  }

  /**
   * {@inheritdoc}
   */
  public function get() {
    $this->requireRemotedb();
    return new SubscriptionService($this->remotedb);
  }

}
