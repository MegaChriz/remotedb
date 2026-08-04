<?php

namespace Drupal\remotedbuser\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\remotedbuser\Entity\RemotedbUserInterface;
use Drupal\remotedbuser\Entity\RemotedbUserStorageInterface;
use Drupal\user\Entity\User;
use Drupal\user\Form\UserPasswordForm as UserPasswordFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Overrides core's user password reset form.
 *
 * When validating, a check if the user's name or user's mail address exists in
 * the remote database is done.
 */
class UserPasswordForm extends UserPasswordFormBase {

  /**
   * The remote user storage.
   *
   * @var \Drupal\remotedbuser\Entity\RemotedbUserStorageInterface
   */
  protected $remoteUserStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $form_object = parent::create($container);
    $remote_user_storage = $container->get('entity_type.manager')->getStorage('remotedb_user');
    if (!$remote_user_storage instanceof RemotedbUserStorageInterface) {
      throw new \LogicException('Expected remotedb_user storage to implement RemotedbUserStorageInterface.');
    }
    $form_object->setRemoteUserStorage($remote_user_storage);

    return $form_object;
  }

  /**
   * Sets the remote user storage.
   *
   * @param \Drupal\remotedbuser\Entity\RemotedbUserStorageInterface $remote_user_storage
   *   The remote user storage.
   */
  protected function setRemoteUserStorage(RemotedbUserStorageInterface $remote_user_storage): void {
    $this->remoteUserStorage = $remote_user_storage;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $name_value = $form_state->getValue('name');
    $name = trim(is_string($name_value) ? $name_value : '');
    // Try to load by email.
    $users = $this->userStorage->loadByProperties(['mail' => $name, 'status' => '1']);
    if ($users === []) {
      // No success, try to load by name.
      $users = $this->userStorage->loadByProperties(['name' => $name, 'status' => '1']);
    }
    $account = reset($users);
    if ($account instanceof User && $account->id() !== NULL) {
      $form_state->setValueForElement(['#parents' => ['account']], $account);
    }
    else {
      // Account not found locally. Search in the remote database.
      // Try to load by email.
      $remote_account = $this->remoteUserStorage->loadBy($name, RemotedbUserStorageInterface::BY_MAIL);
      if (!$remote_account instanceof RemotedbUserInterface) {
        // No success, try to load by name.
        $remote_account = $this->remoteUserStorage->loadBy($name, RemotedbUserStorageInterface::BY_NAME);
      }
      if ($remote_account instanceof RemotedbUserInterface && isset($remote_account->uid)) {
        // Copy over account data.
        $account = $remote_account->toAccount();
        $account->save();
      }
      // Follow the usual validation.
      parent::validateForm($form, $form_state);
    }
  }

}
