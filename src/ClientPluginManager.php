<?php

namespace Drupal\remotedb;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\remotedb\Plugin\Factory\RemotedbPluginFactory;

/**
 * Manages clients for the remote database.
 *
 * @see plugin_api
 */
class ClientPluginManager extends DefaultPluginManager {

  /**
   * Constructs a ClientPluginManager object.
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
    parent::__construct('Plugin/RemotedbClient', $namespaces, $module_handler, 'Drupal\remotedb\Plugin\ClientInterface', 'Drupal\remotedb\Annotation\RemotedbClient');
    //$this->factory = new RemotedbPluginFactory($this, 'Drupal\remotedb\Plugin\ClientInterface');
    $this->alterInfo('remotedb_client_info');
    $this->setCacheBackend($cache_backend, 'remotedb_client_plugins');
  }

}
