<?php

namespace Drupal\remotedb;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\remotedb\Plugin\Factory\RemotedbPluginFactory;

/**
 * Manages transport plugins for the remote database.
 *
 * @see plugin_api
 */
class TransportPluginManager extends DefaultPluginManager {

  /**
   * Constructs a TransportPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/RemotedbTransport',
      $namespaces,
      $module_handler,
      'Drupal\remotedb\Plugin\RemotedbTransportInterface',
      'Drupal\remotedb\Attribute\RemotedbTransport',
      'Drupal\remotedb\Annotation\RemotedbTransport',
    );
    $this->factory = new RemotedbPluginFactory($this, 'Drupal\remotedb\Plugin\RemotedbTransportInterface');
    $this->alterInfo('remotedb_transport_info');
    $this->setCacheBackend($cache_backend, 'remotedb_transport_plugins');
  }

}
