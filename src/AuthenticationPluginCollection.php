<?php

namespace Drupal\remotedb;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Plugin\DefaultLazyPluginCollection;
use Drupal\remotedb\Entity\RemotedbInterface;

/**
 * A collection of authentications.
 */
class AuthenticationPluginCollection extends DefaultLazyPluginCollection {

  /**
   * All possible authentication plugin IDs.
   *
   * @var array
   */
  protected $definitions;

  /**
   * The remote database.
   *
   * @var \Drupal\remotedb\Entity\RemotedbInterface
   */
  protected $remotedb;

  /**
   * Constructs a AuthenticationPluginCollection.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating plugins.
   * @param array $configuration
   *   An array of configuration.
   * @param \Drupal\remotedb\Entity\RemotedbInterface $remotedb
   *   The remote database this plugin belongs to.
   */
  public function __construct(PluginManagerInterface $manager, array $configuration, RemotedbInterface $remotedb) {
    $this->remotedb = $remotedb;
    parent::__construct($manager, $configuration);
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\authentication\Plugin\authenticationInterface
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

  /**
   * Retrieves authentication method definitions and creates an instance for each one.
   */
  public function getAll() {
    // Retrieve all available authentication plugin definitions.
    if (!$this->definitions) {
      $this->definitions = $this->manager->getDefinitions();
    }

    // Ensure that there is an instance of all available authentication methods.
    // Note that getDefinitions() are keyed by $plugin_id. $instance_id is the
    // $plugin_id for authentications, since a single authentication plugin can only exist once
    // in a remote database.
    foreach ($this->definitions as $plugin_id => $definition) {
      if (!isset($this->pluginInstances[$plugin_id])) {
        $this->initializePlugin($plugin_id);
      }
    }
    return $this->pluginInstances;
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    // Authentications have a 1:1 relationship to remote databases and can be added and
    // instantiated at any time.
    $configuration = $this->manager->getDefinition($instance_id);
    // Merge the actual configuration into the default configuration.
    if (isset($this->configurations[$instance_id])) {
      $configuration = NestedArray::mergeDeep($configuration, $this->configurations[$instance_id]);
    }

    // Add remotedb reference.
    $configuration['remotedb'] = $this->remotedb;

    $this->configurations[$instance_id] = $configuration;
    parent::initializePlugin($instance_id);
  }

  /**
   * {@inheritdoc}
   */
  public function sort() {
    $this->getAll();
    return parent::sort();
  }

  /**
   * {@inheritdoc}
   */
  public function sortHelper($aID, $bID) {
    $a = $this->get($aID);
    $b = $this->get($bID);
    if ($a->status != $b->status) {
      return !empty($a->status) ? -1 : 1;
    }
    if ($a->weight != $b->weight) {
      return $a->weight < $b->weight ? -1 : 1;
    }
    if ($a->provider != $b->provider) {
      return strnatcasecmp($a->provider, $b->provider);
    }
    return parent::sortHelper($aID, $bID);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $configuration = parent::getConfiguration();
    // Remove configuration if it matches the defaults. In self::getAll(), we
    // load all available authentications, in addition to the enabled authentications stored in
    // configuration. In order to prevent those from bleeding through to the
    // stored configuration, remove all authentications that match the default values.
    // Because authentications are disabled by default, this will never remove the
    // configuration of an enabled authentication.
    foreach ($configuration as $instance_id => $instance_config) {
      $default_config = [];
      $default_config['id'] = $instance_id;
      $default_config += $this->get($instance_id)->defaultConfiguration();
      if ($default_config === $instance_config) {
        unset($configuration[$instance_id]);
      }
    }
    return $configuration;
  }

}
