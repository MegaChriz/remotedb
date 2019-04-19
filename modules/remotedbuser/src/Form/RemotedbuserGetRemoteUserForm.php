<?php

namespace Drupal\remotedbuser\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;

/**
 *
 */
class RemotedbuserGetRemoteUserForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'remotedbuser_get_remote_user_form';
  }

  /**
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['help'] = [
      '#markup' => '<p>' . t('On this page you can copy over users from the remote database. If a specified user already exists on this website, its user name and mail address and eventually other data will be updated.') . '</p>',
    ];
    $form['user'] = [
      '#type' => 'textarea',
      '#title' => t('Remote users (ID, username or mail address)'),
      '#description' => t('Put in the user IDs, usernames or mail addresses of the users to copy over from the remote database. Put one on each line.'),
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Get'),
    ];

    return $form;
  }

  /**
   *
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user_ids = explode("\n", $form_state->getValue(['user']));
    if (count($user_ids) >= REMOTEDB_USER_BATCH_MINIMUM) {
      // Use batch.
      remotedbuser_get_remote_users_batch($user_ids);
    }
    else {
      foreach ($user_ids as $user_id) {
        // Execute immediately.
        remotedbuser_get_remote_user($user_id);
      }
    }
  }

}
