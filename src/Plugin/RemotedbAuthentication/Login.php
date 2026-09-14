<?php

namespace Drupal\remotedb\Plugin\RemotedbAuthentication;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\remotedb\Attribute\RemotedbAuthentication;
use Drupal\remotedb\Plugin\AuthenticationBase;

/**
 * Logs in a user on the remote database.
 */
#[RemotedbAuthentication(
  id: 'login',
  title: new TranslatableMarkup('Login'),
  description: new TranslatableMarkup('Logs in a user on the remote database.'),
  settings: [
    'username' => '',
    'password' => '',
  ]
)]
class Login extends AuthenticationBase {

  /**
   * Implements AuthenticationInterface::authenticate().
   */
  public function authenticate(): bool {
    $username = $this->settings['username'];
    $password = $this->settings['password'];

    if (!$username) {
      // Logged in as anonymous user.
      $this->remotedb->setHeader('cookie', NULL);
      return TRUE;
    }

    $this->remotedb->setHeader('cookie', NULL);
    // Use the transport plugin directly. Remotedb::sendRequest() authenticates
    // first, and this plugin *is* that authentication, so calling sendRequest()
    // here would recurse. New authentication plugins may still call xmlrpc()
    // or HTTP clients themselves.
    $session = $this->remotedb->getTransportPlugin()->sendRequest('user.login', [
      'username' => $username,
      'password' => $password,
    ]);
    if (!is_array($session) || !isset($session['session_name']) || !isset($session['sessid'])) {
      return FALSE;
    }
    $session_name = $session['session_name'];
    $sessid = $session['sessid'];
    if (!is_string($session_name) || !is_string($sessid)) {
      return FALSE;
    }

    $this->remotedb->setHeader('cookie', $session_name . '=' . $sessid . ';');
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
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
