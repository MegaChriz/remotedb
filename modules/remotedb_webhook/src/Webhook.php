<?php

namespace Drupal\remotedb_webhook;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\PrivateKey;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\remotedb\Entity\RemotedbInterface;
use Drupal\remotedb\Exception\RemotedbException;
use Drupal\remotedbuser\Entity\RemotedbUserStorageInterface;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;

/**
 * General webhook functions.
 */
class Webhook implements WebhookInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * The private key service.
   *
   * @var \Drupal\Core\PrivateKey
   */
  protected $privateKey;

  /**
   * Constructs a new Webhook object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Drupal\Core\PrivateKey $private_key
   *   The private key service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CacheBackendInterface $cache, TimeInterface $time, LoggerInterface $logger, PrivateKey $private_key) {
    $this->entityTypeManager = $entity_type_manager;
    $this->cache = $cache;
    $this->time = $time;
    $this->logger = $logger;
    $this->privateKey = $private_key;
  }

  /**
   * {@inheritdoc}
   */
  public function getKey(): string {
    $base_url = Url::fromRoute('<front>', [], ['absolute' => TRUE])
      ->toString();

    return Crypt::hashBase64($base_url . $this->privateKey->get() . Settings::getHashSalt());
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl(): Url {
    return Url::fromRoute('remotedb_webhook.process_webhook', [
      'key' => $this->getKey(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function exists(RemotedbInterface $remotedb, ?Url $url = NULL): bool {
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
  public function index(RemotedbInterface $remotedb): array {
    $cache = $this->cache->get(static::CACHE_CID . $remotedb->id());
    if ($cache !== FALSE) {
      if (!is_array($cache->data)) {
        $this->logger->error('Cached data of @cid is not an array.', [
          '@cid' => static::CACHE_CID . $remotedb->id(),
        ]);
      }
      else {
        return $cache->data;
      }
    }

    try {
      $index = $remotedb->sendRequest('kkbservices_webhook.index');
      if (!is_array($index)) {
        throw new RemotedbException('List of indexed webhooks is not of the correct type.');
      }
      $this->cache->set(static::CACHE_CID . $remotedb->id(), $index, $this->time->getRequestTime() + 3600);
      return $index;
    }
    catch (RemotedbException $e) {
      $e->logError();
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function add(RemotedbInterface $remotedb, ?Url $url = NULL): bool {
    if (is_null($url)) {
      $url = $this->getUrl();
    }
    $url->setOption('absolute', TRUE);
    $url = $url->toString();

    $this->cacheClear($remotedb);
    $result = $remotedb->sendRequest('kkbservices_webhook.create', [
      $url,
      ['user__update'],
    ]);
    return (bool) $result;
  }

  /**
   * {@inheritdoc}
   */
  public function delete(RemotedbInterface $remotedb, ?Url $url = NULL): void {
    if (is_null($url)) {
      $url = $this->getUrl();
    }
    $url->setOption('absolute', TRUE);
    $url = $url->toString();

    $webhooks = $this->index($remotedb);
    if (isset($webhooks[$url])) {
      $this->cacheClear($remotedb);
      $remotedb->sendRequest('kkbservices_webhook.delete', [$webhooks[$url]['webhook_id']]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function cacheClear(RemotedbInterface $remotedb): void {
    $this->cache->delete(static::CACHE_CID . $remotedb->id());
    Cache::invalidateTags(['remotedb_webhook_enabled']);
  }

  /**
   * {@inheritdoc}
   */
  public function process(string $type, mixed $data): void {
    [$entity_type, $hook] = explode('__', $type);
    if (!is_numeric($data)) {
      // Unable to process. Abort.
      return;
    }
    $data = (int) $data;

    if ($entity_type == 'user') {
      switch ($hook) {
        case 'update':
          // First ensure that this user already exists locally.
          $users = $this->getUserStorage()->loadByProperties(['remotedb_uid' => $data]);
          if ($users === []) {
            return;
          }

          $this->createAccount($data);
          break;

        case 'welcome_email':
          // The user should receive a welcome mail.
          // First ensure that this user already exists locally.
          $account = $this->createAccount($data);
          if ($account instanceof UserInterface) {
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
   *
   * @return \Drupal\user\UserInterface|null
   *   A user account, if retrieving remote account was succesfull. Null
   *   otherwise.
   */
  protected function createAccount($remotedb_uid): ?UserInterface {
    $remote_account = $this->getRemotedbUserStorage()->loadBy($remotedb_uid, RemotedbUserStorageInterface::BY_ID);

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

    return NULL;
  }

  /**
   * Gets the remotedb_user storage handler.
   */
  protected function getRemotedbUserStorage(): RemotedbUserStorageInterface {
    $storage = $this->entityTypeManager->getStorage('remotedb_user');
    if (!$storage instanceof RemotedbUserStorageInterface) {
      throw new \LogicException(sprintf('Remote database storage object should be of type %s, but it is %s.', RemotedbUserStorageInterface::class, get_class($storage)));
    }
    return $storage;
  }

  /**
   * Gets the user storage handler.
   *
   * @return \Drupal\user\UserStorageInterface
   *   The user storage.
   */
  protected function getUserStorage() {
    return $this->entityTypeManager->getStorage('user');
  }

}
