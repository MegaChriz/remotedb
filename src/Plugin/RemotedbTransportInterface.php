<?php

namespace Drupal\remotedb\Plugin;

use Drupal\Core\Form\FormStateInterface;

/**
 * Interface for remote database transport plugins.
 */
interface RemotedbTransportInterface {

  /**
   * Generates a transport plugin's settings form.
   *
   * @param array $form
   *   A minimally prepopulated form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of the (entire) configuration form.
   *
   * @return array
   *   The form elements for the transport settings.
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array;

  /**
   * Sends a request to the remote server.
   *
   * @param string $method
   *   The method to call on the server (e.g. 'dbuser.save').
   * @param array $params
   *   An array of parameters.
   *
   * @return mixed
   *   The result of the request.
   *
   * @throws \Drupal\remotedb\Exception\RemotedbException
   *   In case of errors during the request.
   */
  public function sendRequest(string $method, array $params = []): mixed;

}
