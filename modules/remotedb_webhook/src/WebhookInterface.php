<?php

namespace Drupal\remotedb_webhook;

use Drupal\Core\Url;
use Drupal\remotedb\Entity\RemotedbInterface;

/**
 * Interface for the webhook service.
 */
interface WebhookInterface {

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
  public function getKey();

  /**
   * Generate the webhook endpoint URL.
   *
   * @return \Drupal\Core\Url
   *   The endpoint URL.
   */
  public function getUrl();

  /**
   * Returns if url already exists.
   *
   * @param \Drupal\remotedb\Entity\RemotedbInterface $remotedb
   *   The remote database to register a webhook for.
   * @param \Drupal\Core\Url $url
   *   (optional) The webhook url to check.
   *   Defaults to default webhook url.
   *
   * @return bool
   *   TRUE if the url already exists.
   *   FALSE otherwise.
   */
  public function exists(RemotedbInterface $remotedb, Url $url = NULL);

  /**
   * Gets existing webhooks from the remote database.
   *
   * @param \Drupal\remotedb\Entity\RemotedbInterface $remotedb
   *   The remote database to get registered webhooks for.
   *
   * @return array
   *   An array of existing webhooks.
   */
  public function index(RemotedbInterface $remotedb);

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
  public function add(RemotedbInterface $remotedb, $url = NULL);

  /**
   * Removes a webhook from the remote database.
   *
   * @param \Drupal\remotedb\Entity\RemotedbInterface $remotedb
   *   The remote database to register a webhook for.
   * @param string $url
   *   (optional) The webhook url to remove.
   *   Defaults to default webhook url.
   */
  public function delete(RemotedbInterface $remotedb, $url = NULL);

  /**
   * Clears cache.
   */
  public function cacheClear(RemotedbInterface $remotedb);

  /**
   * Processes webhook data.
   *
   * @param string $type
   *   The type of webhook to process.
   * @param mixed $data
   *   The data contained in the webhook.
   */
  public function process($type, $data);

}
