<?php

namespace Drupal\remotedbuser\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Form\UserLoginForm as UserLoginFormBase;

/**
 * Authenticates users via the remote database.
 */
class UserLoginForm extends UserLoginFormBase {

  /**
   * {@inheritdoc}
   */
  public function validateAuthentication(array &$form, FormStateInterface $form_state) {
    $password = trim($form_state->getValue('pass'));
    if (!$form_state->isValueEmpty('name') && strlen($password) > 0) {
      // @todo use dependency injection.
      // @todo flood check.
      $uid = \Drupal::service('remotedbuser.authentication')->authenticate($form_state->getValue('name'), $password);
      $form_state->set('uid', $uid);
    }
  }

}
