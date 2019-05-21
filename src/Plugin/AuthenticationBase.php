<?php

namespace Drupal\remotedb\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\remotedb\Entity\RemotedbInterface;
use Drupal\remotedb\Plugin\AuthenticationInterface;

/**
 * Base class for remote database authentication plugins.
 */
abstract class AuthenticationBase extends PluginBase implements AuthenticationInterface {

  // ---------------------------------------------------------------------------
  // PROPERTIES
  // ---------------------------------------------------------------------------

  /**
   * A remote database.
   *
   * @var \Drupal\remotedb\Entity\RemotedbInterface
   */
  protected $remotedb;

  /**
   * The name of the provider that owns this method.
   *
   * @var string
   */
  public $provider;

  /**
   * A boolean indicating whether this method is enabled.
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
  public $settings = [];

  // ---------------------------------------------------------------------------
  // CONSTRUCT
  // ---------------------------------------------------------------------------

  /**
   * Constructs a new AuthenticationBase instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\remotedb\Entity\RemotedbInterface $remotedb
   *   A remote database object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RemotedbInterface $remotedb) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->remotedb = $remotedb;
    $this->provider = $this->pluginDefinition['provider'];
    $this->setConfiguration($configuration);
  }

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
    return [
      'id' => $this->getPluginId(),
      'provider' => $this->pluginDefinition['provider'],
      'status' => $this->status,
      'weight' => $this->weight,
      'settings' => $this->settings,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'provider' => $this->pluginDefinition['provider'],
      'status' => FALSE,
      'weight' => $this->pluginDefinition['weight'] ?: 0,
      'settings' => $this->pluginDefinition['settings'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
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
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // Implementations should work with and return $form. Returning an empty
    // array here if there are no additional settings needed.
    return [];
  }
}
