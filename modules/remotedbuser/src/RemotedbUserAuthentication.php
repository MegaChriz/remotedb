<?php

namespace Drupal\remotedbuser;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\remotedbuser\Entity\RemotedbUserInterface;
use Drupal\remotedbuser\Entity\RemotedbUserStorageInterface;
use Drupal\remotedbuser\Exception\RemotedbExistingUserException;
use Drupal\user\UserAuthInterface;

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
   * @var \Drupal\user\UserAuthInterface
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
  public function __construct(ConfigFactoryInterface $config_factory, RemotedbUserConfigurationInterface $remotedbuser_configuration, UserAuthInterface $user_auth, EntityTypeManagerInterface $entity_type_manager) {
    $this->config = $config_factory->get('remotedbuser.settings');
    $this->remotedbUserConfiguration = $remotedbuser_configuration;
    $this->userAuth = $user_auth;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate($name, $password) {
    switch ($this->config->get('login')) {
      case static::LOCALFIRST:
        // Authenticate local users first. If authentication fails, perform
        // the next case. So this case intentionally does not end with a break.
        $uid = $this->userAuth->authenticate($name, $password);
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
        return $this->userAuth->authenticate($name, $password);
    }

    return FALSE;
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
