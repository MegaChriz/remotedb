<?php
/**
 * @file
 * RemoteDB class
 */

class RemoteDB {
  // ---------------------------------------------------------------------------
  // STATIC PROPERTIES
  // ---------------------------------------------------------------------------

  /**
   * Instances of RemoteDB
   *
   * @var array
   * @access private
   * @static
   */
  private static $instances = array();

  // ---------------------------------------------------------------------------
  // PROPERTIES
  // ---------------------------------------------------------------------------

  /**
   * The URL of the remote database.
   *
   * @var string
   * @access private
   */
  private $url;

  /**
   * Contains the latest result from a XML-RPC Request.
   *
   * @var string
   * @access private
   */
  private $result;

  // ---------------------------------------------------------------------------
  // CONSTRUCT
  // ---------------------------------------------------------------------------

  /**
   * RemoteDB object constructor
   *
   * @param string $url
   *   The URL of the remote database
   * @access private
   *
   * @return void
   */
  private function __construct($url) {
    $this->url = $url;
    $this->result = '';
    self::$instances[$url] = $this;
  }

  /**
   * Return a RemoteDB instance
   *
   * @param string $url
   *   (Optional) The URL of the remote database to connect to.
   *   Defaults to variable 'remotedb_staging_url' or 'remotedb_live_url'
   *   (depends on 'remotedb_mode').
   * @access private
   * @static
   *
   * @return RemoteDB
   *   An instance of this class
   * @throws RemoteDBInvalidURLException
   */
  public static function get($url = NULL) {
    if (is_null($url)) {
      switch (variable_get('remotedb_mode', 0)) {
        case REMOTEDB_DISABLED:
          throw new RemoteDBDisabledException(t('The RemoteDB service is disabled.'));
          break;
        case REMOTEDB_STAGING:
          $url = variable_get('remotedb_staging_url', '');
          break;
        case REMOTEDB_LIVE:
          $url = variable_get('remotedb_live_url', '');
          break;
      }
    }

    if (!$url) {
      if (user_access('access RemoteDB administration page')) {
        throw new RemoteDBInvalidURLException(
          t('The Remote DB URL is empty or not set.') . ' '  . t('Go to the <a href="!remotedb-settings-page-url">!remotedb-settings-page</a> to configure the server URL.',
            array(
              '!remotedb-settings-page-url' => url('admin/config/system/remotedb'),
              '!remotedb-settings-page' => t('RemoteDB settings'),
            )
          )
        );
      }
      else {
        throw new RemoteDBInvalidURLException(t('The Remote DB URL is empty or not set.') . ' ' .  t('Please contact the site administrator.'));
      }
    }

    // Check if there is already an instance of RemoteDB with this url.
    if (isset(self::$instances[$url])) {
      return self::$instances[$url];
    }
    else {
      // Create a new instance of RemoteDB
      return new self($url);
    }
  }

  // ---------------------------------------------------------------------------
  // GETTERS
  // ---------------------------------------------------------------------------

  /**
   * Returns the URL from the current instance.
   *
   * @return string
   *   The URL of the remote database.
   */
  public function getUrl() {
    return $this->url;
  }

  // ---------------------------------------------------------------------------
  // ACTION
  // ---------------------------------------------------------------------------

  /**
   * Send a request to the XML-RPC server.
   *
   * @param string $method
   *   The method to call on the server.
   * @access public
   *
   * @return $this
   */
  public function sendRequest($method) {
    // Get params
    $params = func_get_args();
    array_shift($params);
    // Call XML-RPC
    $this->result = xmlrpc($this->url, array($method => $params));
    return $this;
  }

  /**
   * Returns the latest result from a XML-RPC server.
   *
   * @return string
   *   The XML-RPC Result
   */
  public function getResult() {
    return $this->result;
  }
}
