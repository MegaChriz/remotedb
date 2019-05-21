<?php

namespace Drupal\remotedbuser\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\remotedbuser\Entity\RemotedbUserStorageInterface;
use Drupal\user\Form\UserPasswordForm as UserPasswordFormBase;
use Drupal\user\UserStorageInterface;
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
   * Constructs a UserPasswordForm object.
   *
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\remotedbuser\Entity\RemotedbUserStorageInterface $remote_user_storage
   *   The remote user storage.
   */
  public function __construct(UserStorageInterface $user_storage, LanguageManagerInterface $language_manager, RemotedbUserStorageInterface $remote_user_storage) {
    parent::__construct($user_storage, $language_manager);
    $this->remoteUserStorage = $remote_user_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('user'),
      $container->get('language_manager'),
      $container->get('entity.manager')->getStorage('remotedb_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $name = trim($form_state->getValue('name'));
    // Try to load by email.
    $users = $this->userStorage->loadByProperties(['mail' => $name, 'status' => '1']);
    if (empty($users)) {
      // No success, try to load by name.
      $users = $this->userStorage->loadByProperties(['name' => $name, 'status' => '1']);
    }
    $account = reset($users);
    if ($account && $account->id()) {
      $form_state->setValueForElement(['#parents' => ['account']], $account);
    }
    else {
      // Account not found locally. Search in the remote database.
      // Try to load by email.
      $remote_account = $this->remoteUserStorage->loadBy($name, RemotedbUserStorageInterface::BY_MAIL);
      if (!$remote_account) {
        // No success, try to load by name.
        $remote_account = $this->remoteUserStorage->loadBy($name, RemotedbUserStorageInterface::BY_NAME);
      }
      if (isset($remote_account->uid)) {
        // Copy over account data.
        $account = $remote_account->toAccount();
        $account->save();
      }
      // Follow the usual validation.
      return parent::validateForm($form, $form_state);
    }
  }

}
