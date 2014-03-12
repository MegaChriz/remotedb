<?php

/**
 * @file
 * Contains \Drupal\remotedb\Plugin\Feeds\RemotedbFetcherResult.
 */

namespace Drupal\remotedb\Plugin\Feeds;

use Drupal\remotedb\Entity\RemotedbInterface;
use Drupal\remotedb\Exception\RemotedbException;
use Drupal\remotedb\Component\String;
use \FeedsFetcherResult;

/**
 * Result of FeedsHTTPFetcher::fetch().
 */
class RemotedbFetcherResult extends FeedsFetcherResult {
  /**
   * The remote database to fetch data from.
   *
   * @var \Drupal\remotedb\Entity\RemotedbInterface
   */
  protected $remotedb;

  /**
   * Configuration for the request to send to remote database.
   *
   * @var array
   */
  protected $config;

  /**
   * Constructor.
   */
  public function __construct(RemotedbInterface $remotedb, array $config) {
    $this->remotedb = $remotedb;
    $this->config = $config;
  }

  /**
   * Overrides FeedsFetcherResult::getRaw();
   */
  public function getRaw() {
    $string = new String();
    $params = $string->textToArray($this->config['params']);
    $result = $this->remotedb->sendRequest($this->config['method'], $params);
    if (is_object($result) && isset($result->is_error) && $result->is_error == TRUE) {
      $variables = array(
        '@message' => $result->message,
        '@code' => $result->code,
      );
      throw new RemotedbException(t('An error occured when fetching data from the remote database: @message (@code)', $variables));
    }
    return serialize($result);
  }
}
