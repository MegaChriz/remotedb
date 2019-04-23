<?php

namespace Drupal\remotedbuser;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

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
    $this->remotedbStorage = $entity_type_manager->getStorage('remotedb');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefault() {
    $default_remotedb_id = $this->config->get('remotedb');
    if ($default_remotedb_id) {
      return $this->remotedbStorage->load($default_remotedb_id);
    }
  }

}
