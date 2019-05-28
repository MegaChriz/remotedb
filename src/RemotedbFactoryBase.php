<?php

namespace Drupal\remotedb;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\remotedb\Exception\RemotedbException;

/**
 * Base class for factories instantiating services requiring a remote database.
 */
abstract class RemotedbFactoryBase {

  /**
   * The aggregated remote database entity.
   *
   * @var \Drupal\remotedb\Entity\RemotedbInterface|null
   */
  protected $remotedb;

  /**
   * Constructs a new RemotedbFactoryBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $remotedb_id = $this->getRemotedbId($config_factory);
    if ($remotedb_id) {
      $this->remotedb = $entity_type_manager->getStorage('remotedb')->load($remotedb_id);
    }
  }

  /**
   * Returns the remotedb ID from config.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  abstract protected function getRemotedbId(ConfigFactoryInterface $config_factory);

  /**
   * Throws an exception in case remotedb property is not set.
   *
   * Methods that require the remotedb property being set, should call this method.
   *
   * @throws \Drupal\remotedb\Exception\RemotedbException
   *   In case the remote database is not set.
   */
  protected function requireRemotedb() {
    if (!$this->remotedb) {
      throw new RemotedbException('Can not perform request to the remote database, because ' . get_class($this) . ' did not receive a remote database object.');
    }
  }

}
