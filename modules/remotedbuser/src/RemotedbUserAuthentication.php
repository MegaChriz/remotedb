<?php

namespace Drupal\remotedbuser;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\remotedbuser\Exception\RemotedbExistingUserException;
use Drupal\user\UserAuthInterface;

/**
 * Default implementation of the remotedbuser.authentication service.
 */
class RemotedbUserAuthentication implements RemotedbUserAuthenticationInterface {

  use MessengerTrait;

  /**
   * The remote database user configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The remote database user configuration service
   *
   * @var \Drupal\remotedbuser\RemotedbUserConfigurationInterface
   */
  protected $remotedbUserConfiguration;

  /**
   * The user authentication service.
   *
   * @var \Drupal\user\UserAuthInterface
   */
  protected $userAuth;

  /**
   * The remote database user storage.
   *
   * @var \Drupal\remotedbuser\Entity\RemotedbUserStorageInterface
   */
  protected $remotedbUserStorage;

  /**
   * Constructs a new RemotedbUserConfiguration object.
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
  public function __construct(ConfigFactoryInterface $config_factory, RemotedbUserConfigurationInterface $remotedbuser_configuration, UserAuthInterface $user_auth, EntityTypeManagerInterface $entity_type_manager) {
    $this->config = $config_factory->get('remotedbuser.settings');
    $this->remotedbUserConfiguration = $remotedbuser_configuration;
    $this->userAuth = $user_auth;
    $this->remotedbUserStorage = $entity_type_manager->getStorage('remotedb_user');
  }

  /**
   * {@inheritdoc}
   */
  function authenticate($name, $password) {
    switch ($this->config->get('login')) {
      case static::REMOTEDB_LOCALFIRST:
        // Authenticate local users first.
        $uid = $this->userAuth->authenticate($name, $password);
        if ($uid) {
          return $uid;
        }

      case static::REMOTEDB_REMOTEONLY:
        $uid = $this->remoteAuthenticate($name, $password);
        if ($uid) {
          return $uid;
        }
        break;

      case static::REMOTEDB_REMOTEFIRST:
        $uid = $this->remoteAuthenticate($name, $password);
        if ($uid) {
          return $uid;
        }
        $uid = $this->userAuth->authenticate($name, $password);
        if ($uid) {
          return $uid;
        }
        break;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  function remoteAuthenticate($name, $password) {
    $remotedb_uid = $this->remotedbUserStorage->authenticate($name, $password);
    if (!$remotedb_uid) {
      // Authentication failed.
      return FALSE;
    }

    // Get account details from the remote database.
    $remote_account = $this->remotedbUserStorage->load($remotedb_uid);
    if ($remote_account) {
      // Save user locally.
      try {
        $account = $remote_account->toAccount();
        $account->save();
        return $account->id();
      }
      catch (RemotedbExistingUserException $e) {
        $e->logError();
        $this->messenger->addError($this->t('Another user already exists in the system with the same login name. You should contact the system administrator in order to solve this conflict.'));
      }
    }
    return FALSE;
  }

}
