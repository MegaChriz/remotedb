<?php

namespace Drupal\remotedb\Plugin;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Interface for remote database authentication plugins.
 */
interface AuthenticationInterface extends ConfigurableInterface, DependentPluginInterface, PluginInspectionInterface {

  /**
   * Returns the administrative label for this authentication method.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   The method's administrative label.
   */
  public function getLabel(): string|TranslatableMarkup;

  /**
   * Returns the administrative description for this authentication method.
   *
   * @return string|\Drupal\Core\StringTranslation\TranslatableMarkup
   *   The method's description.
   */
  public function getDescription(): string|TranslatableMarkup;

  /**
   * Returns whether this authentication method is enabled.
   *
   * @return bool
   *   TRUE if the method is enabled, FALSE otherwise.
   */
  public function getStatus(): bool;

  /**
   * Returns the weight of this authentication method.
   *
   * @return int
   *   The method weight.
   */
  public function getWeight(): int;

  /**
   * Returns the provider that owns this authentication method.
   *
   * @return string
   *   The provider name.
   */
  public function getProvider(): string;

  /**
   * Generates an authentication method's settings form.
   *
   * @param array $form
   *   A minimally prepopulated form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of the (entire) configuration form.
   *
   * @return array
   *   The form elements for the authentication method settings.
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array;

  /**
   * Executes authentication method.
   *
   * @return bool
   *   TRUE if authentication was succesful.
   *   FALSE otherwise.
   */
  public function authenticate(): bool;

}
