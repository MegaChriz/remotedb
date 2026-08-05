<?php

namespace Drupal\remotedb_sso;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\remotedb\Entity\RemotedbInterface;
use Drupal\remotedb\RemotedbFactoryBase;

/**
 * Factory for instantiating ticket service.
 */
class TicketServiceFactory extends RemotedbFactoryBase implements TicketServiceFactoryInterface {

  /**
   * {@inheritdoc}
   */
  protected function getRemotedbId(ConfigFactoryInterface $config_factory): ?string {
    $remotedb_id = $config_factory->get('remotedbuser.settings')->get('remotedb');
    if (!is_string($remotedb_id)) {
      return NULL;
    }
    return $remotedb_id;
  }

  /**
   * {@inheritdoc}
   */
  public function get(): TicketServiceInterface {
    $this->requireRemotedb();
    if (!$this->remotedb instanceof RemotedbInterface) {
      throw new \LogicException('No remotedb is set but this error should have been catched by RemotedbFactoryBase.');
    }
    return new TicketService($this->remotedb, $this->entityTypeManager);
  }

}
