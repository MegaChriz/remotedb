<?php

namespace Drupal\remotedbuser;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\remotedbuser\Entity\RemotedbUserInterface;
use Drupal\remotedbuser\Entity\RemotedbUserStorageInterface;
use Drupal\remotedbuser\Exception\RemotedbExistingUserException;
use Drupal\user\UserAuthenticationInterface;
use Drupal\user\UserInterface;

/**
 * Default implementation of the remotedbuser.authentication service.
 */
class RemotedbUserAuthentication implements RemotedbUserAuthenticationInterface {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * The remote database user configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The remote database user configuration service.
   *
   * @var \Drupal\remotedbuser\RemotedbUserConfigurationInterface
   */
  protected $remotedbUserConfiguration;

  /**
   * The user authentication service.
   *
   * @var \Drupal\user\UserAuthenticationInterface
   */
  protected $userAuth;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new RemotedbUserAuthentication object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\remotedbuser\RemotedbUserConfigurationInterface $remotedbuser_configuration
   *   The remote database user configuration service.
   * @param \Drupal\user\UserAuthInterface $user_auth
   *   The user authentication service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RemotedbUserConfigurationInterface $remotedbuser_configuration, UserAuthenticationInterface $user_auth, EntityTypeManagerInterface $entity_type_manager) {
    $this->config = $config_factory->get('remotedbuser.settings');
    $this->remotedbUserConfiguration = $remotedbuser_configuration;
    $this->userAuth = $user_auth;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function lookupAccount($identifier): UserInterface|false {
    // First try to find a local account.
    $account = $this->userAuth->lookupAccount($identifier);
    if ($account instanceof UserInterface) {
      return $account;
    }

    // Not found. Try remote database instead.
    $remote_account = $this->getRemotedbUserStorage()->loadBy($identifier, 'name');
    if ($remote_account instanceof RemotedbUserInterface) {
      // Convert it to a local account, but do not save it.
      return $remote_account->toAccount();
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function authenticateAccount(UserInterface $account, #[\SensitiveParameter] string $password): bool {
    switch ($this->config->get('login')) {
      case static::LOCALFIRST:
        // Authenticate local users first. If authentication fails, perform
        // the next case. So this case intentionally does not end with a break.
        if ($this->userAuth->authenticateAccount($account, $password)) {
          return TRUE;
        }

      case static::REMOTEONLY:
        return $this->remoteAuthenticateAccount($account, $password);

      case static::REMOTEFIRST:
        if ($this->remoteAuthenticateAccount($account, $password)) {
          return TRUE;
        }
        return $this->userAuth->authenticateAccount($account, $password);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   *
   * @todo This method got deprecated in drupal:10.3.0. Remove it when support
   * for Drupal 10 will be dropped.
   */
  public function authenticate($name, #[\SensitiveParameter] $password) {
    switch ($this->config->get('login')) {
      case static::LOCALFIRST:
        // Authenticate local users first. If authentication fails, perform
        // the next case. So this case intentionally does not end with a break.
        $uid = $this->localAuthenticate($name, $password);
        if ($uid !== FALSE) {
          return $uid;
        }

      case static::REMOTEONLY:
        return $this->remoteAuthenticate($name, $password);

      case static::REMOTEFIRST:
        $uid = $this->remoteAuthenticate($name, $password);
        if ($uid !== FALSE) {
          return $uid;
        }
        return $this->localAuthenticate($name, $password);
    }

    return FALSE;
  }

  /**
   * Authenticates user against the local database.
   *
   * @param string $name
   *   The username.
   * @param string $password
   *   The password.
   *
   * @return int|false
   *   The user's uid on success, or FALSE on failure to authenticate.
   */
  protected function localAuthenticate(string $name, string $password): int|false {
    $account = $this->userAuth->lookupAccount($name);
    if ($account instanceof UserInterface && $this->userAuth->authenticateAccount($account, $password)) {
      $uid = $account->id();
      return is_numeric($uid) ? (int) $uid : FALSE;
    }
    return FALSE;
  }

  /**
   * Authenticates against the remote database for a looked-up account.
   *
   * Core's UserLoginForm sets the form uid from $account->id() after a
   * successful authenticateAccount(). lookupAccount() may return an unsaved
   * account for remote-only users; remoteAuthenticate() then saves a different
   * entity. Sync the authenticated uid onto $account so login can succeed.
   *
   * @param \Drupal\user\UserInterface $account
   *   The account from lookupAccount().
   * @param string $password
   *   A plain-text password.
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   */
  protected function remoteAuthenticateAccount(UserInterface $account, string $password): bool {
    $uid = $this->remoteAuthenticate($account->getAccountName(), $password);
    if ($uid === FALSE) {
      return FALSE;
    }

    if ((int) $account->id() !== $uid) {
      $account->set('uid', $uid);
      $account->enforceIsNew(FALSE);
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function remoteAuthenticate(string $name, string $password): int|false {
    $storage = $this->getRemotedbUserStorage();
    $remotedb_uid = $storage->authenticate($name, $password);
    if (!is_int($remotedb_uid) || $remotedb_uid === 0) {
      // Authentication failed.
      return FALSE;
    }

    // Get account details from the remote database.
    $remote_account = $storage->load($remotedb_uid);
    if ($remote_account instanceof RemotedbUserInterface) {
      // Save user locally.
      try {
        $account = $remote_account->toAccount();
        $account->save();
        // Entity::id() is string|int|null even when the uid column is numeric.
        $uid = $account->id();
        return is_numeric($uid) ? (int) $uid : FALSE;
      }
      catch (RemotedbExistingUserException $e) {
        $e->logError();
        $this->messenger()->addError($this->t('Another user already exists in the system with the same login name. You should contact the system administrator in order to solve this conflict.'));
      }
    }

    return FALSE;
  }

  /**
   * Gets the remotedb_user storage handler.
   */
  protected function getRemotedbUserStorage(): RemotedbUserStorageInterface {
    $storage = $this->entityTypeManager->getStorage('remotedb_user');
    if (!$storage instanceof RemotedbUserStorageInterface) {
      throw new \LogicException('Expected remotedb_user storage to implement RemotedbUserStorageInterface.');
    }
    return $storage;
  }

}
