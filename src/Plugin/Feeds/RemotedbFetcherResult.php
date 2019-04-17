<?php

namespace Drupal\remotedb\Plugin\Feeds;

use Drupal\remotedb\Entity\RemotedbInterface;
use Drupal\remotedb\Exception\RemotedbException;
use Drupal\remotedb\Component\StringLib;
use FeedsFetcherResult;

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
   * Overrides FeedsFetcherResult::getRaw();.
   */
  public function getRaw() {
    $string = new StringLib();
    $params = $string->textToArray($this->config['params']);
    try {
      $result = $this->remotedb->sendRequest($this->config['method'], $params);
    }
    catch (RemotedbException $e) {
      $variables = [
        '@message' => $e->getMessage(),
        '@code' => $e->getCode(),
      ];
      throw new RemotedbException(t('An error occured when fetching data from the remote database: @message (@code)', $variables));
    }
    return serialize($result);
  }

}
