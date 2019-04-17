<?php

namespace Drupal\remotedb\Plugin;

use Drupal\remotedb\Entity\RemotedbInterface;
use Drupal\remotedb\Plugin\AuthenticationInterface;

/**
 * Base class for remote database authentication plugins.
 */
abstract class AuthenticationBase implements AuthenticationInterface {
  // ---------------------------------------------------------------------------
  // PROPERTIES
  // ---------------------------------------------------------------------------

  /**
   * A remote database.
   *
   * @var RemoteDBInterface
   */
  protected $remotedb;

  /**
   * Info about the authentication method.
   */
  protected $pluginDefinition;

  /**
   * A Boolean indicating whether this method is enabled.
   *
   * @var bool
   */
  public $status = FALSE;

  /**
   * The weight of this method compared to other methods.
   *
   * @var int
   */
  public $weight = 0;

  /**
   * An associative array containing the configured settings of this method.
   *
   * @var array
   */
  public $settings = array();

  // ---------------------------------------------------------------------------
  // CONSTRUCT
  // ---------------------------------------------------------------------------

  /**
   * Constructs a new AuthenticationBase instance.
   *
   * @param array $info
   *   The plugin info.
   * @param RemotedbInterface $remotedb
   *   A remote database object.
   */
  public function __construct(array $configuration, array $info, RemotedbInterface $remotedb) {
    $this->remotedb = $remotedb;
    $this->pluginDefinition = $info;
    $this->setConfiguration($configuration);
    $this->init();
  }

  /**
   * Initializes plugin.
   *
   * Can be used by subclasses to do some initialization upon constructing.
   *
   * @return void
   */
  protected function init() { }

  // ---------------------------------------------------------------------------
  // SETTERS
  // ---------------------------------------------------------------------------

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    if (isset($configuration['status'])) {
      $this->status = (bool) $configuration['status'];
    }
    if (isset($configuration['weight'])) {
      $this->weight = (int) $configuration['weight'];
    }
    if (isset($configuration['settings'])) {
      $this->settings = (array) $configuration['settings'];
    }
    return $this;
  }

  // ---------------------------------------------------------------------------
  // GETTERS
  // ---------------------------------------------------------------------------

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return array(
      'id' => $this->getPluginId(),
      'provider' => $this->pluginDefinition['plugin module'],
      'status' => $this->status,
      'weight' => $this->weight,
      'settings' => $this->settings,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->pluginDefinition['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    // Implementations should work with and return $form. Returning an empty
    // array here if there are no additional settings needed.
    return array();
  }
}
