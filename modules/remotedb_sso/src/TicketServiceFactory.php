<?php

namespace Drupal\remotedb_sso;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\remotedb\RemotedbFactoryBase;

/**
 * Factory for instantiating ticket service.
 */
class TicketServiceFactory extends RemotedbFactoryBase implements TicketServiceFactoryInterface {

  /**
   * {@inheritdoc}
   */
  protected function getRemotedbId(ConfigFactoryInterface $config_factory): ?string {
    return $config_factory->get('remotedbuser.settings')->get('remotedb');
  }

  /**
   * {@inheritdoc}
   */
  public function get(): TicketServiceInterface {
    $this->requireRemotedb();
    return new TicketService($this->remotedb, $this->entityTypeManager->getStorage('remotedb_user'));
  }

}
