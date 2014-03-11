<?php

/**
 * Contains Drupal\remotedbuser_test\Controller\RemotedbUserController.
 */

namespace Drupal\remotedbuser_test\Controller;

/**
 * Remotedb entity controller class.
 */
class RemotedbUserController extends \Drupal\remotedbuser\Controller\RemotedbUserController {
  // ---------------------------------------------------------------------------
  // CONSTRUCT
  // ---------------------------------------------------------------------------

  /**
   * Overridden.
   */
  public function __construct($entityType) {
    parent::__construct($entityType);

    // Set remotedb mock.
    $remotedb = entity_create('remotedb', array());
    $remotedb->setCallback(array($this, 'remotedbCallback'));
    $this->setRemotedb($remotedb);
  }

  // ---------------------------------------------------------------------------
  // HELPERS
  // ---------------------------------------------------------------------------

  /**
   * Gets all accounts.
   *
   * @return array
   *   An array of accounts.
   */
  public function getRemoteAccounts() {
    $result = db_select('variable')
      ->fields('variable', array())
      ->condition('name', 'remotedbuser_test_accounts')
      ->execute()
      ->fetch();
    if (is_object($result) && !empty($result->value)) {
      return unserialize($result->value);
    }
    return array();
  }

  /**
   * Save accounts.
   *
   * @param array $accounts
   *   The account to save in database.
   *
   * @return void
   */
  private function setRemoteAccounts(array $accounts) {
    return variable_set('remotedbuser_test_accounts', $accounts);
  }

  // ---------------------------------------------------------------------------
  // REMOTE DATABASE
  // ---------------------------------------------------------------------------

  /**
   * Callback for remote database calls.
   *
   * @param string $method
   *   The method being called.
   * @param array $params
   *   An array of parameters.
   *
   * @return mixed
   *   Returns different values depending on the method call.
   */
  public function remotedbCallback($method, $params) {
    $accounts = $this->getRemoteAccounts();
    switch ($method) {
      case 'dbuser.retrieve':
        $id = $params[0];
        $by = $params[1];
        return $this->dbuserRetrieve($id, $by);
      case 'dbuser.save':
        $account = $params[0];
        return $this->dbuserSave($account);
      case 'dbuser.authenticate':
        $name = $params[0];
        $pass = $params[1];
        return $this->dbuserAuthenticate($name, $pass);
    }
    if (module_exists('yvklibrary')) {
      outputlog(get_defined_vars());
    }
  }

  /**
   * Retrieves a single remote user.
   *
   * @param mixed $id
   *   The id of the user.
   * @param string $by
   *   The key to load the user by.
   *
   * @return array
   *   An array of user data if found.
   *   NULL otherwise.
   */
  private function dbuserRetrieve($id, $by) {
    $accounts = $this->getRemoteAccounts();
    foreach ($accounts as $account) {
      if ($account[$by] == $id) {
        return $account;
      }
    }
    return NULL;
  }

  /**
   * Saves a remote user.
   *
   * @param array $account
   *   The user data.
   *
   * @return int
   *   The remote user uid.
   */
  private function dbuserSave($account) {
    $accounts = $this->getRemoteAccounts();
    if (empty($account['uid'])) {
      // Generate uid if it doesn't have one.
      $account['uid'] = count($accounts) + 1000;
    }
    $accounts[$account['uid']] = $account;
    $this->setRemoteAccounts($accounts);
    return $account['uid'];
  }

  /**
   * Authenticates an user.
   *
   * @param string $name
   *   The user's name.
   * @param string $password
   *   The user's password.
   *
   * @return int|boolean
   *   The remote user's ID if authentication was succesful.
   *   FALSE otherwise.
   */
  private function dbuserAuthenticate($name, $password) {
    require_once DRUPAL_ROOT . '/' . variable_get('password_inc', 'includes/password.inc');
    $accounts = $this->getRemoteAccounts();
    $account = $this->dbuserRetrieve($name, 'name');
    if (!empty($account)) {
      $account = (object) $account;
      if (user_check_password($password, $account)) {
        return $account->uid;
      }
    }
    return FALSE;
  }
}
