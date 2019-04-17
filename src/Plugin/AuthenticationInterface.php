<?php

namespace Drupal\remotedb\Plugin;

/**
 * Interface for remote database authentication plugins.
 */
interface AuthenticationInterface {
  /**
   * Returns the id for this authentication method.
   *
   * @return string
   */
  public function getPluginId();

  /**
   * Returns the administrative label for this authentication method.
   *
   * @return string
   */
  public function getLabel();

  /**
   * Returns the administrative description for this authentication method.
   *
   * @return string
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
