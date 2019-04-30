<?php

namespace Drupal\remotedbuser\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;

/**
 * Provides a form to copy an user from the remote database.
 */
class GetRemoteUserForm extends FormBase {

  /**
   * The minimum number of users to copy over to use a batch for.
   *
   * @var int
   */
  const REMOTEDB_USER_BATCH_MINIMUM = 3;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'remotedbuser_get_remote_user_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['help'] = [
      '#markup' => '<p>' . $this->t('On this page you can copy over users from the remote database. If a specified user already exists on this website, its user name and mail address and eventually other data will be updated.') . '</p>',
    ];
    $form['user'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Remote users (ID, username or mail address)'),
      '#description' => t('Put in the user IDs, usernames or mail addresses of the users to copy over from the remote database. Put one on each line.'),
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Get'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user_ids = explode("\n", $form_state->getValue(['user']));
    if (count($user_ids) >= static::REMOTEDB_USER_BATCH_MINIMUM) {
      // Use batch.
      $this->getRemoteUserBatch($user_ids);
    }
    else {
      foreach ($user_ids as $user_id) {
        // Execute immediately.
        $this->getRemoteUser($user_id);
      }
    }
  }

  /**
   * Gets a single remote user.
   *
   * @param mixed $user_id
   *   The user to import from the remote database.
   */
  public function getRemoteUser($user_id) {
    try {
      $remote_account = \Drupal::entityTypeManager()->getStorage('remotedb_user')->loadByAny($user_id);
      if ($remote_account) {
        // Copy over account data.
        $account = $remote_account->toAccount();
        $account->save();

        \Drupal::messenger()->addStatus($this->t('User account <a href="@url">%name</a> copied over from the remote database.', [
          '@url' => $account->toUrl()->toString(),
          '%name' => $account->getAccountName(),
        ]));
      }
      else {
        \Drupal::messenger()->addStatus($this->t('No remote user found for %user.', [
          '%user' => $user_id,
        ]));
      }
    }
    catch (RemotedbException $e) {
      $e->logError();
      $e->printMessage();
    }
    catch (Exception $e) {
      watchdog_exception('remotedb', $e);
      \Drupal::messenger()->addError($e->getMessage());
    }
  }

  /**
   * Imports users from the remote database using the Batch API.
   *
   * @param array $user_ids
   *   The ids of users to import from the remote database.
   *   Ids may be the remote user id, user names or mail addresses.
   * @param int $limit_per_batch
   *   (optional) How many users should be imported per batch.
   *   Defaults to 10.
   *
   * @return void
   */
  protected function getRemoteUserBatch(array $user_ids, $limit_per_batch = 10) {
    $operations = [];
    $operations[] = [
      [$this, 'getRemoteUserBatchOperation'],
      [
        $user_ids,
        $limit_per_batch,
      ],
    ];

    $batch = [
      'title' => t('Importing users from the remote database...'),
      'operations' => $operations,
      'progress_message' => '',
      'file' => drupal_get_path('module', 'remotedbuser') . '/remotedbuser.admin.inc',
    ];
    batch_set($batch);
  }

  /**
   * Imports the given users from the remote database.
   *
   * @param array $user_ids
   *   The ids of users to import from the remote database.
   * @param int $limit_per_batch
   *   (optional) How many users should be imported per batch.
   *   Defaults to 10.
   *
   * @return void
   */
  public function getRemoteUserBatchOperation(array $user_ids, $limit_per_batch = 10, &$context) {
    if (empty($context['sandbox'])) {
      $context['sandbox'] = [];
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = count($user_ids);
      $context['sandbox']['user_ids'] = $user_ids;
    }

    $group = array_slice($context['sandbox']['user_ids'], 0, $limit_per_batch);
    $context['sandbox']['user_ids'] = array_slice($context['sandbox']['user_ids'], $limit_per_batch);

    $rd_controller = \Drupal::entityTypeManager()->getStorage('remotedb_user');

    $i = 0;
    foreach ($group as $user_id) {
      $i++;

      // Get remote user and save it locally.
      remotedbuser_get_remote_user($user_id, $rd_controller);

      $context['sandbox']['progress']++;
      $context['message'] = t('Imported @current users out of @total.', [
        '@current' => $context['sandbox']['progress'],
        '@total' => $context['sandbox']['max'],
      ]);

      if ($i >= $limit_per_batch) {
        // Stop.
        break;
      }
    }

    // Inform the batch engine that we are not finished,
    // and provide an estimation of the completion level we reached.
    $context['finished'] = ($context['sandbox']['progress'] / $context['sandbox']['max']);
  }

}
