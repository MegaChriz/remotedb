<?php

/**
 * @file
 * Contains \Drupal\remotedbuser\Controller\RemotedbUserController.
 */

namespace Drupal\remotedbuser\Controller;

use DatabaseTransaction;
use Drupal\remotedb\Entity\RemotedbInterface;
use Drupal\remotedb\Exception\RemotedbException;
use Drupal\remotedbuser\Entity\RemotedbUserInterface;
use Drupal\remotedbuser\Exception\RemotedbExistingUserException;
use EntityAPIController;

/**
 * Remotedb entity controller class.
 */
class RemotedbUserController extends EntityAPIController {
  // ---------------------------------------------------------------------------
  // CONSTANTS
  // ---------------------------------------------------------------------------

  /**
   * Methods to load an user by in the remote database.
   *
   * @var string
   */
  const BY_ID = 'uid';
  const BY_NAME = 'name';
  const BY_MAIL = 'mail';

  // ---------------------------------------------------------------------------
  // PROPERTIES
  // ---------------------------------------------------------------------------

  /**
   * A remote database.
   *
   * @var \Drupal\remotedb\Entity\RemotedbInterface
   */
  private $remotedb;

  // ---------------------------------------------------------------------------
  // CONSTRUCT
  // ---------------------------------------------------------------------------

  /**
   * Overridden.
   */
  public function __construct($entityType) {
    parent::__construct($entityType);
    // Set default remote database (if defined).
    $remotedb = remotedbuser_get_remotedb();
    if ($remotedb instanceof RemotedbInterface) {
      $this->setRemotedb($remotedb);
    }
  }

  // ---------------------------------------------------------------------------
  // SETTERS
  // ---------------------------------------------------------------------------

  /**
   * Sets remote database to use.
   *
   * @param RemotedbInterface $remotedb
   *   A remote database object.
   */
  public function setRemotedb(RemotedbInterface $remotedb) {
    $this->remotedb = $remotedb;
  }

  // ---------------------------------------------------------------------------
  // GETTERS
  // ---------------------------------------------------------------------------

  /**
   * Returns remote database that is used.
   *
   * @return RemotedbInterface $remotedb
   *   A remote database object.
   */
  public function getRemotedb() {
    return $this->remotedb;
  }

  // ---------------------------------------------------------------------------
  // LOADING/SAVING
  // ---------------------------------------------------------------------------

  /**
   * Overrides EntityAPIController::load().
   */
  public function load($ids = array(), $conditions = array()) {
    $entities = array();
    $conditions += array(
      'load_by' => self::BY_ID,
    );

    foreach ($ids as $id) {
      // The remote database only supports loading one remote user at a time.
      $data = $this->sendRequest('dbuser.retrieve', array($id, $conditions['load_by']));
      if ($data) {
        $data['is_new'] = FALSE;
        $entity = $this->create($data);
        $entities[$entity->uid] = $entity;
      }
    }

    // Pass all entities loaded from the database through $this->attachLoad(),
    // which attaches fields (if supported by the entity type) and calls the
    // entity type specific load callback, for example hook_node_load().
    if (!empty($entities)) {
      $this->attachLoad($entities, FALSE);
    }

    return $entities;
  }

  /**
   * Loads a single entity by a certain property.
   */
  public function loadBy($id, $load_by = NULL) {
    $entities = $this->loadMultipleBy(array($id), $load_by);
    return reset($entities);
  }

  /**
   * Loads a list of entities by a certain property.
   */
  public function loadMultipleBy($ids = array(), $load_by = NULL) {
    switch ($load_by) {
      case self::BY_ID:
      case self::BY_NAME:
      case self::BY_MAIL:
        break;
      default:
        $load_by = self::BY_ID;
    }
    return $this->load($ids, array('load_by' => $load_by));
  }

  /**
   * Overrides EntityAPIController::save().
   *
   * @return bool
   *   TRUE if saving succeeded.
   *   FALSE otherwise.
   */
  public function save($entity, DatabaseTransaction $transaction = NULL) {
    // Invoke presave hook.
    $entity->is_new = !empty($entity->is_new) || empty($entity->{$this->idKey});
    $this->invoke('presave', $entity);

    // Save remote user into the remote database.
    $result = $this->sendRequest('dbuser.save', array($entity->toArray()));
    if (empty($result) || !is_numeric($result)) {
      return FALSE;
    }
    $entity->uid = $result;

    // Invoke postsave hook.
    if ($entity->is_new) {
      $this->invoke('insert', $entity);
    }
    else {
      $this->invoke('update', $entity);
    }

    // We don't call parent::save(), because we don't have anything to save locally.
    return TRUE;
  }

  /**
   * Constructs a RemotedbUser from a user account object.
   *
   * @param object $account
   *   The local user account.
   *
   * @return \Drupal\remotedb\Entity\RemotedbUserInterface
   *   A remotedb user object.
   * @throws RemotedbException
   *   If the passed in account does not have a mail address.
   */
  public function fromAccount($account) {
    if (empty($account->mail)) {
      throw new RemotedbException(t("The account can not be saved in the remote database, because it doesn't have a mail address."));
    }

    $values = array(
      'name' => $account->name,
      'mail' => $account->mail,
      'status' => $account->status,
      'created' => $account->created,
      'timezone' => !empty($account->timezone) ? $account->timezone : NULL,
      'language' => !empty($account->language) ? $account->language : NULL,
      'init' => $account->init,
    );

    if (!empty($account->remotedb_uid)) {
      $values['uid'] = $account->remotedb_uid;
      $values['is_new'] = FALSE;
    }

    // Load password from database.
    $values['pass'] = db_select('users')
      ->fields('users', array('pass'))
      ->condition('uid', $account->uid)
      ->execute()
      ->fetchField();

    // Instantiate a RemotedbUserInterface object.
    $entity = $this->create($values);

    // Cross reference.
    $entity->account = $account;
    $account->remotedb_user = $entity;

    return $entity;
  }

  /**
   * Sets data from a remote account to the local account.
   *
   * @param \Drupal\remotedb\Entity\RemotedbUserInterface $entity
   *   The Remote user.
   *
   * @return object
   *   The unsaved account, filled with values from the remote user.
   */
  public function toAccount(RemotedbUserInterface $entity) {
    // First, get account from local database, if it exists.
    // First find by remotedb_uid, then by name and finally by mail.
    $search = array(
      'remotedb_uid' => $entity->uid,
      'name' => $entity->name,
      'mail' => $entity->mail,
    );
    foreach ($search as $key => $value) {
      $users = user_load_multiple(array(), array($key => $value));
      if (!empty($users)) {
        $account = reset($users);
        break;
      }
    }

    // Check if this account is already linked to a remote account. If so, we should not
    // suddenly link it to an other account.
    if (!empty($account->remotedb_uid) && $account->remotedb_uid != $entity->uid) {
      $vars = array(
        '@uid' => $account->uid,
        '@remotedb_uid' => $entity->uid,
      );
      throw new RemotedbExistingUserException(t('Failed to synchronize the remote user. The remote user @remotedb_uid conflicts with local user @uid.', $vars));
    }

    // Name and mail must be unique. If an account was found, make sure that no other account
    // exists that has either the name or the mail address from the remote account.
    if (!empty($account)) {
      $search = array(
        'name' => $entity->name,
        'mail' => $entity->mail,
      );
      foreach ($search as $key => $value) {
        $users = user_load_multiple(array(), array($key => $value));
        if (!empty($users)) {
          $account2 = reset($users);
          if ($account->uid != $account2->uid) {
            // We have a conflict here.
            $vars = array(
              '@uid' => $account2->uid,
              '@remotedb_uid' => $entity->uid,
            );
            throw new RemotedbExistingUserException(t('Failed to synchronize the remote user. The remote user @remotedb_uid conflicts with local user @uid.', $vars));
          }
        }
      }
    }

    // Construct values to set on the local account.
    $values = $entity->toArray();
    // The remote user's uid should not overwrite the local user's uid,
    // but instead be saved as 'remotedb_uid'.
    $values['remotedb_uid'] = $values['uid'];
    unset($values['uid']);

    if (empty($account)) {
      // No account found, create a new user.
      $account = entity_create('user', $values);
    }
    else {
      // Update user account.
      $update_props = remotedbuser_variable_get('sync_properties');
      foreach ($update_props as $key) {
        if (empty($key)) {
          continue;
        }
        if (isset($values[$key])) {
          $account->$key = $values[$key];
        }
      }

      // Always set remotedb_uid.
      $account->remotedb_uid = $values['remotedb_uid'];
    }

    // Cross reference.
    $entity->account = $account;
    $account->remotedb_user = $entity;

    // Set flag that account should *not* be send back to the remote database again.
    $account->from_remotedb = TRUE;

    return $account;
  }

  // ---------------------------------------------------------------------------
  // ACTION
  // ---------------------------------------------------------------------------

  /**
   * Authenticates an user via the remote database.
   *
   * @param string $name
   *   User name to authenticate.
   * @param string $password
   *   A plain-text password.
   *
   * @return int|bool
   *   The remotedb user's uid on success, or FALSE on failure to authenticate.
   */
  public function authenticate($name, $pass) {
    return $this->sendRequest('dbuser.authenticate', array($name, $pass));
  }

  /**
   * Validates name.
   *
   * @param string $name
   *   The name to check for existence.
   * @param object $account
   *   The user's account.
   *
   * @return bool
   *   TRUE if validation passes.
   *   FALSE otherwise.
   * @todo Remotedb_uid should probably be send along instead.
   */
  public function validateName($name, $account) {
    if (!empty($account->name) && $account->name == $name) {
      // The username did not change. No need to validate.
      return TRUE;
    }

    $remote_account = $this->loadBy($name, self::BY_NAME);
    if (empty($remote_account)) {
      // Name is not taken yet.
      return TRUE;
    }
    elseif (empty($account->remotedb_uid)) {
      // This could be a valid case, but only if user name and mail exactly match.
      if (isset($account->mail)) {
        if ($name == $remote_account->name && $account->mail == $remote_account->mail) {
          return TRUE;
        }
      }
    }
    elseif ($account->remotedb_uid == $remote_account->uid) {
      // Accounts match.
      return TRUE;
    }
    // In all other cases, name is already taken!
    return FALSE;
  }

  /**
   * Validates mail address.
   *
   * @param string $mail
   *   The mail address to check for existence.
   * @param object $account
   *   The user's account.
   *
   * @return bool
   *   TRUE if validation passes.
   *   FALSE otherwise.
   */
  public function validateMail($mail, $account) {
    if (!empty($account->mail) && $account->mail == $mail) {
      // The mail address did not change. No need to validate.
      return TRUE;
    }

    $remote_account = $this->loadBy($mail, self::BY_MAIL);
    if (empty($remote_account)) {
      // Mail address is not taken yet.
      return TRUE;
    }
    elseif (empty($account->remotedb_uid)) {
      // This could be a valid case, but only if user name and mail exactly match.
      if (isset($account->name)) {
        if ($account->name == $remote_account->name && $mail == $remote_account->mail) {
          return TRUE;
        }
      }
    }
    elseif ($account->remotedb_uid == $remote_account->uid) {
      // Accounts match.
      return TRUE;
    }
    // In all other cases, mail address is already taken!
    return FALSE;
  }

  /**
   * Send a request to the remote database.
   *
   * @param string $method
   *   The method to call on the server.
   * @param array $params
   *   The parameters to send.
   *
   * @return mixed
   *   The result of the method call.
   * @throws RemotedbException
   *   In case the remote database object was not set.
   */
  protected function sendRequest($method, array $params = array()) {
    if (!($this->remotedb instanceof RemotedbInterface)) {
      throw new RemotedbException(t('Can not perform request to the remote database, because the RemotedbUserController did not receive a remote database object.'));
    }
    try {
      return $this->remotedb->sendRequest($method, $params);
    }
    catch (RemotedbException $e) {
      $e->logError();
      return FALSE;
    }
  }
}
