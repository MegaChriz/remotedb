<?php

namespace Drupal\remotedb_webhook;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\remotedb\Entity\RemotedbInterface;
use Drupal\remotedb\Exception\RemotedbException;
use Drupal\remotedbuser\Entity\RemotedbUserStorageInterface;
use Psr\Log\LoggerInterface;

/**
 * General webhook functions.
 */
class Webhook implements WebhookInterface {

  /**
   * The remote user storage.
   *
   * @var \Drupal\remotedbuser\Entity\RemotedbUserStorageInterface
   */
  protected $remotedbUserStorage;

  /**
   * The local user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new Webhook object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The remote user storage.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CacheBackendInterface $cache, TimeInterface $time, LoggerInterface $logger) {
    $this->remotedbUserStorage = $entity_type_manager->getStorage('remotedb_user');
    $this->userStorage = $entity_type_manager->getStorage('user');
    $this->cache = $cache;
    $this->time = $time;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function getKey() {
    $base_url = Url::fromRoute('<front>', [], ['absolute' => TRUE])
      ->toString();

    return Crypt::hashBase64($base_url . \Drupal::service('private_key')->get() . Settings::getHashSalt());
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    return Url::fromRoute('remotedb_webhook.process_webhook', [
      'key' => $this->getKey(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function exists(RemotedbInterface $remotedb, Url $url = NULL) {
    if (is_null($url)) {
      $url = $this->getUrl();
    }
    $url->setOption('absolute', TRUE);

    $webhooks = $this->index($remotedb);
    return isset($webhooks[$url->toString()]);
  }

  /**
   * {@inheritdoc}
   */
  public function index(RemotedbInterface $remotedb) {
    $cache = $this->cache->get(static::CACHE_CID . $remotedb->id());
    if ($cache) {
      return $cache->data;
    }
    else {
      try {
        $index = $remotedb->sendRequest('kkbservices_webhook.index');
        $this->cache->set(static::CACHE_CID . $remotedb->id(), $index, $this->time->getRequestTime() + 3600);
      }
      catch (RemotedbException $e) {
        watchdog_exception('remotedb', $e);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function add(RemotedbInterface $remotedb, Url $url = NULL) {
    if (is_null($url)) {
      $url = $this->getUrl();
    }
    $url->setOption('absolute', TRUE);
    $url = $url->toString();

    $this->cacheClear($remotedb);
    return $remotedb->sendRequest('kkbservices_webhook.create', [
      $url,
      ['user__update'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function delete(RemotedbInterface $remotedb, Url $url = NULL) {
    if (is_null($url)) {
      $url = $this->getUrl();
    }
    $url->setOption('absolute', TRUE);
    $url = $url->toString();

    $webhooks = $this->index($remotedb);
    if (isset($webhooks[$url])) {
      $this->cacheClear($remotedb);
      return $remotedb->sendRequest('kkbservices_webhook.delete', [$webhooks[$url]['webhook_id']]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function cacheClear(RemotedbInterface $remotedb) {
    $this->cache->delete(static::CACHE_CID . $remotedb->id());
    Cache::invalidateTags(['remotedb_webhook_enabled']);
  }

  /**
   * {@inheritdoc}
   */
  public function process($type, $data) {
    list($entity_type, $hook) = explode('__', $type);

    if ($entity_type == 'user') {
      switch ($hook) {
        case 'update':
          // First ensure that this user already exists locally.
          $users = $this->userStorage->loadByProperties(['remotedb_uid' => $data]);
          if (empty($users)) {
            return;
          }

          $this->createAccount($data);
          break;

        case 'welcome_email':
          // The user should receive a welcome mail.
          // First ensure that this user already exists locally.
          $account = $this->createAccount($data);
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
  protected function createAccount($remotedb_uid) {
    $remote_account = $this->remotedbUserStorage->loadBy($remotedb_uid, RemotedbUserStorageInterface::BY_ID);

    if (isset($remote_account->uid)) {
      // Copy over account data.
      $account = $remote_account->toAccount();
      $account->save();

      $vars = [
        '@url' => $account->toUrl()->toString(),
        '%name' => $account->getAccountName(),
      ];
      $this->logger->info('User account <a href="@url">%name</a> copied over from the remote database.', $vars);

      return $account;
    }
  }

}
