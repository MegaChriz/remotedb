<?php

namespace Drupal\remotedb\Plugin;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
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
   * Executes authentication method.
   *
   * @return bool
   *   TRUE if authentication was succesful.
   *   FALSE otherwise.
   */
  public function authenticate(): bool;

}
