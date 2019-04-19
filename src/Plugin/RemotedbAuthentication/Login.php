<?php

namespace Drupal\remotedb\Plugin\RemotedbAuthentication;

use Drupal\Core\Form\FormStateInterface;
use Drupal\remotedb\Plugin\AuthenticationBase;

/**
 * Logs in an user on the remote database.
 *
 * @RemotedbAuthentication(
 *   id = "login",
 *   title = @Translation("Login"),
 *   description = @Translation("Logs in an user on the remote database."),
 *   settings = {
 *     "username" = "",
 *     "password" = ""
 *   }
 * )
 */
class Login extends AuthenticationBase {

  /**
   * Implements AuthenticationInterface::authenticate().
   */
  public function authenticate() {
    $username = $this->settings['username'];
    $password = $this->settings['password'];

    if (!$username) {
      // Logged in as anonymous user.
      $this->remotedb->setHeader('cookie', NULL);
      return TRUE;
    }

    $params = [
      'user.login' => [
        'username' => $username,
        'password' => $password,
      ],
    ];

    $this->remotedb->setHeader('cookie', NULL);
    $session = xmlrpc($this->remotedb->getUrl(), $params, $this->remotedb->getHeaders());
    if ($session === FALSE) {
      return FALSE;
    }
    $this->remotedb->setHeader('cookie', $session['session_name'] . '=' . $session['sessid'] . ';');
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#maxlength' => 255,
      '#default_value' => $this->settings['username'],
    ];
    $form['password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Password'),
      '#maxlength' => 255,
      '#default_value' => $this->settings['password'],
    ];
    return $form;
  }

}
