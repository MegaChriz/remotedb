<?php

namespace Drupal\remotedb\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\remotedb\Entity\RemotedbInterface;

/**
 * Base class for remote database transport plugins.
 */
abstract class TransportBase extends PluginBase implements RemotedbTransportInterface {

  /**
   * A remote database.
   *
   * @var \Drupal\remotedb\Entity\RemotedbInterface
   */
  protected $remotedb;

  /**
   * Constructs a new TransportBase instance.
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
    $this->mergeDefaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    return [];
  }

  /**
   * Merges default settings from the plugin definition into configuration.
   */
  protected function mergeDefaultSettings(): void {
    $definition = $this->pluginDefinition;
    if (!is_array($definition)) {
      return;
    }
    $defaults = $definition['settings'] ?? [];
    if (!is_array($defaults)) {
      return;
    }
    $this->configuration += $defaults;
  }

  /**
   * Returns the administrative label for this transport.
   */
  public function getLabel(): string|TranslatableMarkup {
    $definition = $this->pluginDefinition;
    if (!is_array($definition)) {
      return '';
    }
    $title = $definition['title'] ?? '';
    if ($title instanceof TranslatableMarkup || is_string($title)) {
      return $title;
    }
    return '';
  }

}
