<?php

namespace Drupal\remotedbuser;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\remotedb\Entity\RemotedbInterface;
use Drupal\remotedb\Entity\RemotedbStorageInterface;

/**
 * Default implementation of the remotedbuser.configuration service.
 */
class RemotedbUserConfiguration implements RemotedbUserConfigurationInterface {

  /**
   * The remote database user configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The remote database storage.
   *
   * @var \Drupal\remotedb\Entity\RemotedbStorageInterface
   */
  protected $remotedbStorage;

  /**
   * Constructs a new RemotedbUserConfiguration object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->config = $config_factory->get('remotedbuser.settings');
    $remotedb_storage = $entity_type_manager->getStorage('remotedb');
    if (!$remotedb_storage instanceof RemotedbStorageInterface) {
      throw new \LogicException('Expected remotedb storage to implement RemotedbStorageInterface.');
    }
    $this->remotedbStorage = $remotedb_storage;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefault(): ?RemotedbInterface {
    $default_remotedb_id = $this->config->get('remotedb');
    if (is_string($default_remotedb_id) && $default_remotedb_id !== '') {
      $remotedb = $this->remotedbStorage->load($default_remotedb_id);
      return $remotedb instanceof RemotedbInterface ? $remotedb : NULL;
    }
    return NULL;
  }

}
