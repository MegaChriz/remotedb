<?php

/**
 * @file
 * Contains \Drupal\remotedb\Plugin\Feeds\RemotedbFetcherResult.
 */

namespace Drupal\remotedb\Plugin\Feeds;

use \FeedsFetcherResult;

/**
 * Result of FeedsHTTPFetcher::fetch().
 */
class RemotedbFetcherResult extends FeedsFetcherResult {
  protected $url;
  protected $config;

  /**
   * Constructor.
   */
  public function __construct($url = NULL, $config) {
    $this->url = $url;
    $this->config = $config;
  }

  /**
   * Overrides FeedsFetcherResult::getRaw();
   */
  public function getRaw() {
    $oRemoteDB = RemoteDB::get($this->url);
    $oRemoteDB->sendRequest($this->config['method'], remotedb_text_to_params($this->config['params']));
    $result = $oRemoteDB->getResult();
    if (is_object($result) && isset($result->is_error) && $result->is_error == TRUE) {
      $variables = array(
        '@message' => $result->message,
        '@code' => $result->code,
      );
      throw new RemoteDBException(t('An error occured when fetching data from the remote database: @message (@code)', $variables));
    }
    return serialize($result);
  }
}
