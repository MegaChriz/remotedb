<?php

declare(strict_types=1);

namespace Drupal\remotedbuser\Hook;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\remotedbuser\Access\RemoteUserAccessChecker;
use Drupal\remotedbuser\Entity\RemotedbUserInterface;
use Drupal\remotedbuser\Entity\RemotedbUserStorageInterface;
use Drupal\user\UserInterface;

/**
 * Form hook implementations for remotedbuser.
 */
class RemotedbuserFormHooks {

  use StringTranslationTrait;

  /**
   * Entity type manager service.
   */
  protected readonly EntityTypeManagerInterface $entityTypeManager;

  /**
   * Current user proxy service.
   */
  protected readonly AccountProxyInterface $currentUser;

  /**
   * Remote user access checker service.
   */
  protected readonly RemoteUserAccessChecker $remoteUserAccessChecker;

  /**
   * Constructs form hook implementations.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user proxy service.
   * @param \Drupal\remotedbuser\Access\RemoteUserAccessChecker $remote_user_access_checker
   *   The remote user access checker service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    AccountProxyInterface $current_user,
    RemoteUserAccessChecker $remote_user_access_checker,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->remoteUserAccessChecker = $remote_user_access_checker;
  }

  /**
   * Implements hook_form_FORM_ID_alter() for form user_register_form().
   */
  #[Hook('form_user_register_form_alter')]
  public function formUserRegisterFormAlter(array &$form, FormStateInterface $form_state): void {
    $form['#validate'][] = [$this, 'accountFormValidate'];
  }

  /**
   * Implements hook_form_FORM_ID_alter() for form user_form().
   */
  #[Hook('form_user_form_alter')]
  public function formUserFormAlter(array &$form, FormStateInterface $form_state): void {
    $form_object = $form_state->getFormObject();
    if (!method_exists($form_object, 'getEntity')) {
      return;
    }
    $account = $form_object->getEntity();
    if (!$account instanceof UserInterface) {
      return;
    }

    $form['remotedb'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Remote database'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#access' => $this->currentUser->hasPermission('remotedb.administer'),
    ];
    $form['remotedb']['remotedb_uid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Remote database user ID'),
      '#default_value' => $account->get('remotedb_uid')->value,
    ];

    $form['#validate'][] = [$this, 'accountFormValidate'];
  }

  /**
   * Form validation handler for user account forms.
   */
  public function accountFormValidate(array $form, FormStateInterface $form_state): void {
    $storage = $this->entityTypeManager->getStorage('remotedb_user');
    if (!$storage instanceof RemotedbUserStorageInterface) {
      throw new \LogicException('Expected remotedb_user storage to implement RemotedbUserStorageInterface.');
    }
    $form_object = $form_state->getFormObject();
    if (!method_exists($form_object, 'getEntity')) {
      return;
    }
    $account = $form_object->getEntity();
    if (!$account instanceof UserInterface) {
      return;
    }

    $register = $account->isAnonymous();
    $admin_create = $register && $account->access('create');

    $name = $form_state->getValue('name');
    $mail = $form_state->getValue('mail');

    if ($admin_create && $this->currentUser->hasPermission('remotedbuser.create')) {
      if ($form_state->getErrors() !== []) {
        return;
      }

      if (!is_string($mail) && !is_int($mail)) {
        return;
      }
      $remote_account = $storage->loadBy($mail, RemotedbUserStorageInterface::BY_MAIL);
      if ($remote_account instanceof RemotedbUserInterface) {
        $remote_values = $remote_account->toArray();
        $remote_name = $remote_values['name'] ?? NULL;
        if ($remote_name != $name) {
          if (!is_string($name)) {
            return;
          }
          if (!$storage->validateName($name, $account)) {
            $form_state->setErrorByName('name', $this->t('The name %name is already taken.', ['%name' => $name]));
          }
        }
      }
    }
    else {
      if (is_string($name) && $name !== '') {
        if (!$storage->validateName($name, $account)) {
          $message = $this->t('The name %name is already taken.', ['%name' => $name]);
          if ($this->remoteUserAccessChecker->hasAccess()) {
            $message = $this->t('A user with the name %name already exists on the remote database. You can copy this user over on the <a href="@link">@title</a> page.', [
              '%name' => $name,
              '@link' => Url::fromRoute('remotedbuser.get_remote_user_form')->toString(),
              '@title' => $this->t('Get remote user'),
            ]);
          }
          $form_state->setErrorByName('name', $message);
        }
      }

      if (!is_string($mail)) {
        return;
      }
      if (!$storage->validateMail($mail, $account)) {
        if ($this->currentUser->isAuthenticated()) {
          $message = $this->t('The e-mail address %email is already taken.', ['%email' => $mail]);
          if ($this->remoteUserAccessChecker->hasAccess()) {
            $message = $this->t('a user with the e-mail address %email already exists on the remote database. You can copy this user over on the <a href="@link">@title</a> page.', [
              '%email' => $mail,
              '@link' => Url::fromRoute('remotedbuser.get_remote_user_form')->toString(),
              '@title' => $this->t('Get remote user'),
            ]);
          }
          $form_state->setErrorByName('mail', $message);
        }
        else {
          $form_state->setErrorByName('mail', $this->t('The e-mail address %email is already registered. <a href="@password">Have you forgotten your password?</a>', [
            '%email' => $mail,
            '@password' => Url::fromRoute('user.pass')->toString(),
          ]));
        }
      }
    }
  }

}
