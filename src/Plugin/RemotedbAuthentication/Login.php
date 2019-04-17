<?php

namespace Drupal\remotedb\Plugin\RemotedbAuthentication;

use Drupal\remotedb\Plugin\AuthenticationBase;

/**
 * Logs in an user on the remote database.
 */
class Login extends AuthenticationBase {
  /**
   * Implements AuthenticationBase::init().
   */
  protected function init() {
    $this->settings += array(
      'username' => '',
      'password' => '',
    );
  }

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

    $params = array(
      'user.login' => array(
        'username' => $username,
        'password' => $password,
      ),
    );

    $this->remotedb->setHeader('cookie', NULL);
    $session = xmlrpc($this->remotedb->getUrl(), $params, $this->remotedb->getOptions());
    if ($session === FALSE) {
      //self::reportError(xmlrpc_error(), $this->url, 'user.login');
      return FALSE;
    }
    $this->remotedb->setHeader('cookie', $session['session_name'] . '=' . $session['sessid'] . ';');
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, array &$form_state) {
    $form['username'] = array(
      '#type' => 'textfield',
      '#title' => t('Username'),
      '#maxlength' => 255,
      '#default_value' => $this->settings['username'],
    );
    $form['password'] = array(
      '#type' => 'textfield',
      '#title' => t('Password'),
      '#maxlength' => 255,
      '#default_value' => $this->settings['password'],
    );
    return $form;
  }
}
