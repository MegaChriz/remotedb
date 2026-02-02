<?php

namespace Drupal\remotedb_role;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\remotedb\Entity\RemotedbInterface;
use Drupal\remotedb\RemotedbFactoryBase;

/**
 * Factory for instantiating subscription service.
 */
class SubscriptionServiceFactory extends RemotedbFactoryBase implements SubscriptionServiceFactoryInterface {

  /**
   * {@inheritdoc}
   */
  protected function getRemotedbId(ConfigFactoryInterface $config_factory): ?string {
    $remotedb_id = $config_factory->get('remotedb_role.settings')->get('remotedb');
    if (!is_string($remotedb_id)) {
      return NULL;
    }
    return $remotedb_id;
  }

  /**
   * {@inheritdoc}
   */
  public function get(): SubscriptionServiceInterface {
    $this->requireRemotedb();
    if (!$this->remotedb instanceof RemotedbInterface) {
      throw new \LogicException('No remotedb is set but this error should have been catched by RemotedbFactoryBase.');
    }
    return new SubscriptionService($this->remotedb);
  }

}
