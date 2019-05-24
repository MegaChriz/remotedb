<?php

namespace Drupal\remotedb_role;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\remotedb\Entity\RemotedbInterface;
use Drupal\remotedb\Exception\RemotedbException;

/**
 * Factory for instantiating subscription service.
 */
class SubscriptionServiceFactory {

  /**
   * The aggregated remote database entity.
   *
   * @var \Drupal\remotedb\Entity\RemotedbInterface|null
   */
  protected $remotedb;

  /**
   * Constructs a new Remotedb object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $remotedb_id = $config_factory->get('remotedb_role.settings')->get('remotedb');
    if ($remotedb_id) {
      $this->remotedb = $entity_type_manager->getStorage('remotedb')->load($remotedb_id);
    }
  }

  /**
   * Returns the subscription service to use.
   *
   * @return \Drupal\remotedb_role\SubscriptionService
   *   The subscription service.
   */
  public function get() {
    if (!$this->remotedb) {
      throw new RemotedbException('Can not perform request to the remote database, because the SubscriptionService did not receive a remote database object.');
    }
    return new SubscriptionService($this->remotedb);
  }

}
