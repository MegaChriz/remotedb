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

  /**
   * An array of option to send along with the HTTP Request.
   *
   * @var array
   * @access private
   */
  private $options;

  /**
   * Whether or not the authentication process has run.
   *
   * @var boolean
   * @access private
   */
  private $authenticated;

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
    $this->options = array(
      //'timeout' => 120.0,
    );
    $this->authenticated = FALSE;
    self::$instances[$url] = $this;
  }

  /**
   * Return a RemoteDB instance.
   *
   * @param string $url
   *   (Optional) The URL of the remote database to connect to.
   *   Defaults to variable 'remotedb_staging_url' or 'remotedb_live_url'
   *   (depends on 'remotedb_mode').
   * @access private
   * @static
   *
   * @return RemoteDB
   *   An instance of this class.
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
   * Authenticate to the XML-RPC server.
   */
  public function authenticate() {
    // Get token.
    $this->remoteGetCSRFToken();
    
    // Login on the remote database.
    $this->authenticated = $this->remoteLogin();

    // Get new token.
    $this->remoteGetCSRFToken();
  }

  /**
   * Tries to retrieve the CSRF token from the remote database.
   */
  protected function remoteGetCSRFToken() {
    $params = array(
      'user.token' => array(),
    );
    unset($this->options['headers']['X-CSRF-Token']);
    $token = xmlrpc($this->url, $params, $this->options);
    if (!empty($token) && isset($token['token'])) {
      $this->options['headers']['X-CSRF-Token'] = $token['token'];
    }
  }

  /**
   * Logs in an user on the remote database.
   *
   * @param string $username
   *   (optional) The name of the user to log in.
   *   Defaults to variable 'remotedb_login_username'.
   * @param string $password
   *   (optional) The user's password.
   *   Defaults to variable 'remotedb_login_password'.
   *
   * @return boolean
   *   TRUE if:
   *   - the user was succesfully logged in;
   *   - no username was specified ('anonymous' login).
   *   FALSE if login failed.
   */
  protected function remoteLogin($username = NULL, $password = NULL) {
    if (is_null($username)) {
      $username = variable_get('remotedb_login_username', NULL); 
    }
    if (is_null($password)) {
      $password = variable_get('remotedb_login_password', NULL); 
    }

    if (!$username) {
      // Logged in as anonymous user.
      unset($this->options['headers']['cookie']);
      return TRUE;
    }

    $params = array(
      'user.login' => array(
        'username' => $username,
        'password' => $password,
      ),
    );

    unset($this->options['headers']['cookie']);
    $session = xmlrpc($this->url, $params, $this->options);
    if ($session === FALSE) {
      self::reportError(xmlrpc_error(), $this->url, 'user.login');
      return FALSE;
    }
    $this->options['headers']['cookie'] = $session['session_name'] . '=' . $session['sessid'] . ';';
    return TRUE;
  }

  /**
   * Send a request to the XML-RPC server.
   *
   * @param string $method
   *   The method to call on the server.
   * @access public
   *
   * @return $this
   */
  public function sendRequest($method, $params = array()) {
    if (!$this->authenticated) {
      $this->authenticate();
    }

    if (!is_array($params)) {
      // Get params.
      $params = func_get_args();
      array_shift($params);
    }
    $args = array($method => $params);
    // Call XML-RPC.
    $this->result = xmlrpc($this->url, $args, $this->options);
    if ($this->result === FALSE) {
      $this->result = xmlrpc_error();
      self::reportError($this->result, $this->url, $method, $params);
    }
    return $this;
  }

  /**
   * Returns the latest result from a XML-RPC server.
   *
   * @return string
   *   The XML-RPC Result.
   */
  public function getResult() {
    return $this->result;
  }

  /**
   * Report an error.
   *
   * @param object $error
   *   The error object.
   * @param string $url
   *   The URL that was used for the remote database.
   * @param string $method
   *   The method of the remote database that was called.
   * @param array $params
   *   (optional) The parameters used for the method.
   */
  protected static function reportError($error, $url, $method, $params = array()) {
    if (is_object($error) && isset($error->is_error) && $error->is_error == TRUE) {
      // This IS an error.
    }
    else {
      // Not an error.
      return;
    }
    // Prepare parameters for message.
    $mess_params = array();
    if (count($params) > 0) {
      foreach ($params as $param) {
        if (is_object($param)) {
          $mess_params[] = get_class($param);
        }
        elseif (is_array($param)) {
          $mess_params[] = 'array()';
        }
        else {
          $mess_params[] = (string) $param;
        }
      }
    }
    if (count($mess_params) > 0) {
      $mess_params = '(' . implode(', ', $mess_params) . ')';
    }
    else {
      $mess_params = t('none');
    }

    $variables = array(
      '@method' => $method,
      '@params' => $mess_params,
      '@message' => $error->message,
      '@code' => $error->code,
      '@url' => $url,
    );
    watchdog('remotedb', 'An error occured when fetching data from the remote database at @url: @message (@code). Method: @method; Params: @params', $variables, WATCHDOG_WARNING);
  }
}
