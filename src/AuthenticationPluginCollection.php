<?php

namespace Drupal\remotedb;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Plugin\DefaultLazyPluginCollection;
use Drupal\remotedb\Entity\RemotedbInterface;
use Drupal\remotedb\Plugin\AuthenticationInterface;

/**
 * A collection of authentications.
 */
class AuthenticationPluginCollection extends DefaultLazyPluginCollection {

  /**
   * All possible authentication plugin IDs.
   *
   * @var array<string, mixed>|null
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
   * @return \Drupal\remotedb\Plugin\AuthenticationInterface
   *   The authentication plugin instance.
   */
  public function &get($instance_id) {
    $instance =& parent::get($instance_id);
    assert($instance instanceof AuthenticationInterface);
    return $instance;
  }

  /**
   * Retrieves plugin definitions and creates an instance for each one.
   *
   * @return \Drupal\remotedb\Plugin\AuthenticationInterface[]
   *   The plugin instances.
   */
  public function getAll(): array {
    // Retrieve all available authentication plugin definitions.
    if ($this->definitions === NULL) {
      $this->definitions = $this->manager->getDefinitions();
    }

    // Ensure that there is an instance of all available authentication methods.
    // Note that getDefinitions() are keyed by $plugin_id. $instance_id is the
    // $plugin_id for authentications, since a single authentication plugin can
    // only exist once in a remote database.
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
  protected function initializePlugin($instance_id): void {
    // Authentications have a 1:1 relationship to remote databases and can be
    // added and instantiated at any time.
    $definition = $this->manager->getDefinition($instance_id);
    if (!is_array($definition)) {
      throw new \LogicException(sprintf('Expected array plugin definition for "%s".', $instance_id));
    }
    $configuration = $definition;
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
  public function sort(): static {
    $this->getAll();
    return parent::sort();
  }

  /**
   * {@inheritdoc}
   */
  public function sortHelper(mixed $aID, mixed $bID): int {
    assert(is_string($aID) && is_string($bID));
    $a = $this->get($aID);
    $b = $this->get($bID);
    if ($a->getStatus() != $b->getStatus()) {
      return $a->getStatus() ? -1 : 1;
    }
    if ($a->getWeight() != $b->getWeight()) {
      return $a->getWeight() < $b->getWeight() ? -1 : 1;
    }
    if ($a->getProvider() != $b->getProvider()) {
      return strnatcasecmp($a->getProvider(), $b->getProvider());
    }
    return parent::sortHelper($aID, $bID);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration(): array {
    $configuration = parent::getConfiguration();
    // Remove configuration if it matches the defaults. In self::getAll(), we
    // load all available authentications, in addition to the enabled
    // authentications stored in configuration. In order to prevent those from
    // bleeding through to the stored configuration, remove all authentications
    // that match the default values. Because authentications are disabled by
    // default, this will never remove the configuration of an enabled
    // authentication.
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
