<?php

namespace Drupal\remotedb\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;

/**
 * Interface for remote database authentication plugins.
 */
interface AuthenticationInterface extends ConfigurableInterface, DependentPluginInterface, PluginInspectionInterface {

  /**
   * Returns the administrative label for this authentication method.
   *
   * @return string
   *   The method's administrative label.
   */
  public function getLabel();

  /**
   * Returns the administrative description for this authentication method.
   *
   * @return string
   *   The method's description.
   */
  public function getDescription();

  /**
   * Executes authentication method.
   *
   * @return bool
   *   TRUE if authentication was succesful.
   *   FALSE otherwise.
   */
  public function authenticate();

}
