<?php

/**
 * @file
 * Contains \Drupal\remotedb_webhook\Webhook.
 */

namespace Drupal\remotedb_webhook;

use Drupal\remotedb\Entity\RemotedbInterface;
use Drupal\remotedbuser\Controller\RemotedbUserController;

/**
 * General webhook functions.
 */
class Webhook {
  /**
   * Cache ID for index.
   *
   * @var string
   */
  const CACHE_CID = 'kkbservices_webhook_index';

  /**
   * Generates webhook key.
   *
   * @return string
   *   The key.
   */
  public static function getKey() {
    return drupal_hash_base64($GLOBALS['base_url'] . drupal_get_private_key() . drupal_get_hash_salt());
  }

  /**
   * Generate the webhook endpoint URL.
   *
   * @return string
   *   The endpoint URL.
   */
  public static function getUrl() {
    return $GLOBALS['base_url'] . '/remotedb/webhook/' . static::getKey();
  }

  /**
   * Returns if url already exists.
   *
   * @param \Drupal\remotedb\Entity\RemotedbInterface $remotedb
   *   The remote database to register a webhook for.
   * @param string $url
   *   (optional) The webhook url to check.
   *   Defaults to default webhook url.
   *
   * @return bool
   *   TRUE if the url already exists.
   *   FALSE otherwise.
   */
  public static function exists(RemotedbInterface $remotedb, $url = NULL) {
    if (is_null($url)) {
      $url = static::getUrl();
    }
    $webhooks = static::index($remotedb);
    return isset($webhooks[$url]);
  }

  /**
   * Gets existing webhooks from the remote database.
   *
   * @param \Drupal\remotedb\Entity\RemotedbInterface $remotedb
   *   The remote database to get registered webhooks for.
   *
   * @return array
   *   An array of existing webhooks.
   */
  public static function index(RemotedbInterface $remotedb) {
    $cache = cache_get(static::CACHE_CID . $remotedb->name);
    if ($cache) {
      return $cache->data;
    }
    else {
      $index = $remotedb->sendRequest('kkbservices_webhook.index');
      cache_set(static::CACHE_CID . $remotedb->name, $index, 'cache', REQUEST_TIME + 3600);
    }
  }

  /**
   * Registers a webhook to the remote database.
   *
   * @param \Drupal\remotedb\Entity\RemotedbInterface $remotedb
   *   The remote database to register a webhook for.
   * @param string $url
   *   (optional) The webhook url to add.
   *   Defaults to default webhook url.
   *
   * @return bool
   *   TRUE if the webhook was added with success.
   *   FALSE otherwise.
   */
  public static function add(RemotedbInterface $remotedb, $url = NULL) {
    if (is_null($url)) {
      $url = static::getUrl();
    }
    static::cacheClear($remotedb);
    return $remotedb->sendRequest('kkbservices_webhook.create', array($url, array(
      'user__update',
    )));
  }

  /**
   * Removes a webhook from the remote database.
   *
   * @param \Drupal\remotedb\Entity\RemotedbInterface $remotedb
   *   The remote database to register a webhook for.
   * @param string $url
   *   (optional) The webhook url to remove.
   *   Defaults to default webhook url.
   */
  public static function delete(RemotedbInterface $remotedb, $url = NULL) {
    if (is_null($url)) {
      $url = static::getUrl();
    }
    $webhooks = static::index($remotedb);
    if (isset($webhooks[$url])) {
      static::cacheClear($remotedb);
      return $remotedb->sendRequest('kkbservices_webhook.delete', array($webhooks[$url]['webhook_id']));
    }
  }

  /**
   * Clears cache.
   */
  public static function cacheClear(RemotedbInterface $remotedb) {
    cache_clear_all(static::CACHE_CID . $remotedb->name, 'cache');
    variable_set('menu_rebuild_needed', TRUE);
  }

  /**
   * Processes webhook data.
   */
  public static function process($type, $data) {
    list($entity_type, $hook) = explode('__', $type);

    if ($entity_type == 'user') {
      switch ($hook) {
        case 'update':
          // First ensure that this user already exists locally.
          $users = user_load_multiple(array(), array('remotedb_uid' => $data));
          if (empty($users)) {
            return;
          }

          static::createAccount($data);
          break;

        case 'welcome_email':
          // The user should receive a welcome mail.
          // First ensure that this user already exists locally.
          $account = static::createAccount($data);
          if ($account) {
            _user_mail_notify('register_admin_created', $account);
          }
          break;
      }
    }
  }

  /**
   * Creates an account if one doesn't exist.
   *
   * @param int $remotedb_uid
   *   The ID of the user in the remote database.
   */
  protected static function createAccount($remotedb_uid) {
    $rd_controller = entity_get_controller('remotedb_user');
    $remote_account = $rd_controller->loadBy($remotedb_uid, RemotedbUserController::BY_ID);
    if (isset($remote_account->uid)) {
      // Copy over account data.
      $account = $remote_account->toAccount();
      entity_save('user', $account);
      $uri = entity_uri('user', $account);
      $vars = array(
        '@url' => url($uri['path'], $uri['options']),
        '%name' => $account->name,
      );
      watchdog('remotedb', 'User account <a href="@url">%name</a> copied over from the remote database.', $vars, WATCHDOG_INFO);

      return $account;
    }
  }

}
