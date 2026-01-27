<?php

namespace Drupal\remotedb\Plugin\Factory;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Factory\ContainerFactory;
use Drupal\remotedb\Entity\RemotedbInterface;
use Drupal\remotedb\Plugin\AuthenticationInterface;

/**
 * Plugin factory to pass remote database instance to plugin instances.
 */
class RemotedbPluginFactory extends ContainerFactory {

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []): AuthenticationInterface {
    $plugin_definition = $this->discovery->getDefinition($plugin_id);
    $plugin_class = static::getPluginClass($plugin_id, $plugin_definition, $this->interface);

    assert($configuration['remotedb'] instanceof RemotedbInterface);
    $remotedb = $configuration['remotedb'];
    unset($configuration['remotedb']);

    // If the plugin provides a factory method, pass the container to it.
    if (is_subclass_of($plugin_class, ContainerFactoryPluginInterface::class)) {
      return $plugin_class::create(\Drupal::getContainer(), $configuration, $plugin_id, $plugin_definition, $remotedb);
    }

    // Otherwise, create the plugin directly.
    return new $plugin_class($configuration, $plugin_id, $plugin_definition, $remotedb);
  }

}
