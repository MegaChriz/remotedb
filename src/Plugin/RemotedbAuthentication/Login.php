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

    $params = [
      'user.login' => [
        'username' => $username,
        'password' => $password,
      ],
    ];

    $this->remotedb->setHeader('cookie', NULL);
    $url = $this->remotedb->getUrl();
    if (!is_string($url)) {
      return FALSE;
    }
    $session = xmlrpc($url, $params, $this->remotedb->getHeaders());
    if (!is_array($session) || !isset($session['session_name']) || !isset($session['sessid'])) {
      return FALSE;
    }

    $this->remotedb->setHeader('cookie', $session['session_name'] . '=' . $session['sessid'] . ';');
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
